<?php

namespace App\Modules\Users\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Users\Models\Leave;
use App\Modules\Users\Models\LeaveBalance;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveController extends Controller
{
    private function isGlobalView(Request $request): bool
    {
        return $request->user() && ($request->user()->hasRole(['Super Admin', 'Admin']) || $request->user()->can('view-all-data'));
    }

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('leave-view'), 403);

        $isGlobalView = $this->isGlobalView($request);
        $request->validate([
            'status' => 'nullable|in:Pending,Approved,Rejected',
            'history' => 'nullable|boolean',
            'per_page' => 'nullable|integer',
            'page' => 'nullable|integer|min:1',
        ]);
        $filterUserId = $request->input('user_id');

        // LOB scoping: non-global users see all leaves for their team members.
        // null = global (no extra restriction), [ids] = LOB team member IDs.
        $lobTeamUserIds = $isGlobalView ? null : $request->user()?->getLobTeamUserIds();

        $query = Leave::with(['user', 'approver', 'applier'])
            ->when(
                ! $isGlobalView && $lobTeamUserIds !== null,
                fn ($q) => $q->whereIn('user_id', ! empty($lobTeamUserIds) ? $lobTeamUserIds : [0])
            )
            ->when($filterUserId, fn ($q) => $q->where('user_id', $filterUserId))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->boolean('history'), fn ($q) => $q->where('status', '!=', 'Pending'))
            ->orderBy('start_date', 'desc')->orderByDesc('id');

        $leaves = $query->paginate(min(max($request->integer('per_page', 15), 1), 100));

        return response()->json($leaves);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('leave-create'), 403);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'leave_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
        ]);

        if (! $this->isGlobalView($request)) {
            $validated['user_id'] = $request->user()->id;
        }

        $validated['status'] = 'Pending';
        $validated['applied_by'] = auth()->id();

        $leave = Leave::create($validated);

        return response()->json(['data' => $leave, 'message' => 'Leave application submitted successfully.'], 201);
    }

    public function show(Request $request, Leave $leave): JsonResponse
    {
        abort_unless($request->user()?->can('leave-view'), 403);

        if (! $this->isGlobalView($request) && (int) $leave->user_id !== (int) $request->user()->id) {
            abort(403, 'Unauthorized to view this leave record.');
        }

        $leave->load(['user', 'approver', 'applier']);

        return response()->json(['data' => $leave]);
    }

    public function update(Request $request, Leave $leave): JsonResponse
    {
        return DB::transaction(function () use ($request, $leave) {
            abort_unless($request->user()?->can('leave-edit'), 403);
            $leave = Leave::query()->lockForUpdate()->findOrFail($leave->id);

            if (! $this->isGlobalView($request) && (int) $leave->user_id !== (int) $request->user()->id) {
                abort(403, 'Unauthorized to modify this leave record.');
            }

            if ($leave->status !== 'Pending') {
                return response()->json(['message' => 'Cannot modify an already processed leave request.'], 422);
            }

            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'leave_type' => 'required|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'reason' => 'nullable|string',
                'status' => 'required|in:Pending,Approved,Rejected',
            ]);

            if (! $this->isGlobalView($request)) {
                $validated['user_id'] = $request->user()->id;
                $validated['status'] = 'Pending';
            }

            if (in_array($validated['status'], ['Approved', 'Rejected'])) {
                $validated['approved_by'] = auth()->id();
                $validated['approved_at'] = now();
            } elseif ($validated['status'] === 'Pending') {
                $validated['approved_by'] = null;
                $validated['approved_at'] = null;
            }

            $originalStatus = $leave->status;
            $originalUserId = $leave->user_id;
            $originalLeaveType = $leave->leave_type;
            $originalDays = Carbon::parse($leave->start_date)->diffInDays(Carbon::parse($leave->end_date)) + 1;

            $leave->update($validated);

            $newStatus = $leave->status;

            // If it was previously approved, refund the original balance first
            if ($originalStatus === 'Approved') {
                $oldBalance = LeaveBalance::where('user_id', $originalUserId)
                    ->where('leave_type', $originalLeaveType)
                    ->first();
                if ($oldBalance) {
                    $oldBalance->decrement('used_leaves', $originalDays);
                }
            }

            // If the new status is approved, deduct the new balance
            if ($newStatus === 'Approved') {
                $newDays = Carbon::parse($leave->start_date)->diffInDays(Carbon::parse($leave->end_date)) + 1;
                $newBalance = LeaveBalance::where('user_id', $leave->user_id)
                    ->where('leave_type', $leave->leave_type)
                    ->first();
                if ($newBalance) {
                    $newBalance->increment('used_leaves', $newDays);
                }
            }

            return response()->json(['data' => $leave, 'message' => 'Leave request updated successfully.']);
        }, 3);
    }

    public function destroy(Request $request, Leave $leave): JsonResponse
    {
        return DB::transaction(function () use ($request, $leave) {
            abort_unless($request->user()?->can('leave-delete'), 403);
            $leave = Leave::query()->lockForUpdate()->findOrFail($leave->id);

            if (! $this->isGlobalView($request) && (int) $leave->user_id !== (int) $request->user()->id) {
                abort(403, 'Unauthorized to delete this leave record.');
            }

            if ($leave->status !== 'Pending') {
                return response()->json(['message' => 'Cannot delete an already processed leave request.'], 422);
            }

            if ($leave->status === 'Approved') {
                $this->adjustLeaveBalance($leave, 'refund');
            }

            $leave->delete();

            return response()->json(['message' => 'Leave request deleted successfully.']);
        }, 3);
    }

    public function updateStatus(Request $request, Leave $leave): JsonResponse
    {
        return DB::transaction(function () use ($request, $leave) {
            abort_unless($request->user()?->can('leave-edit'), 403);
            $leave = Leave::query()->lockForUpdate()->findOrFail($leave->id);

            if (! $this->isGlobalView($request)) {
                abort(403, 'Unauthorized to approve or reject leave requests.');
            }

            if ($leave->status !== 'Pending') {
                return response()->json(['message' => 'Cannot modify an already processed leave request.'], 422);
            }

            $validated = $request->validate([
                'status' => 'required|in:Approved,Rejected',
            ]);

            $originalStatus = $leave->status;

            $leave->update([
                'status' => $validated['status'],
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);

            $newStatus = $leave->status;

            if ($originalStatus !== 'Approved' && $newStatus === 'Approved') {
                $this->adjustLeaveBalance($leave, 'deduct');
            } elseif ($originalStatus === 'Approved' && $newStatus !== 'Approved') {
                $this->adjustLeaveBalance($leave, 'refund');
            }

            return response()->json(['data' => $leave, 'message' => 'Leave status updated successfully.']);
        }, 3);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validate([
                'action' => 'required|in:approve,reject,delete',
                'ids' => 'required|array|min:1',
                'ids.*' => 'integer|distinct|exists:leaves,id',
            ]);

            $action = $validated['action'];
            $ids = $validated['ids'];

            if ($action === 'delete') {
                abort_unless($request->user()?->can('leave-delete'), 403);

                $query = Leave::whereIn('id', $ids)->where('status', 'Pending');
                if (! $this->isGlobalView($request)) {
                    $query->where('user_id', $request->user()->id);
                }
                $leaves = $query->orderBy('id')->lockForUpdate()->get();
                foreach ($leaves as $leave) {
                    // ... refunds aren't needed if they are always Pending, but we can keep it safe
                    if ($leave->status === 'Approved') {
                        $this->adjustLeaveBalance($leave, 'refund');
                    }
                    $leave->delete();
                }

                return response()->json(['message' => 'Selected pending leaves deleted successfully.']);
            }

            abort_unless($request->user()?->can('leave-edit'), 403);

            if (! $this->isGlobalView($request)) {
                abort(403, 'Unauthorized to approve or reject leave requests.');
            }

            $status = $action === 'approve' ? 'Approved' : 'Rejected';

            $leaves = Leave::whereIn('id', $ids)->where('status', 'Pending')->orderBy('id')->lockForUpdate()->get();
            foreach ($leaves as $leave) {
                $originalStatus = $leave->status;

                $leave->update([
                    'status' => $status,
                    'approved_by' => $request->user()->id,
                    'approved_at' => now(),
                ]);

                if ($originalStatus !== 'Approved' && $status === 'Approved') {
                    $this->adjustLeaveBalance($leave, 'deduct');
                } elseif ($originalStatus === 'Approved' && $status !== 'Approved') {
                    $this->adjustLeaveBalance($leave, 'refund');
                }
            }

            return response()->json(['message' => 'Selected leaves '.strtolower($status).' successfully.']);
        }, 3);
    }

    private function adjustLeaveBalance(Leave $leave, $action = 'deduct')
    {
        $days = Carbon::parse($leave->start_date)->diffInDays(Carbon::parse($leave->end_date)) + 1;

        $balance = LeaveBalance::where('user_id', $leave->user_id)
            ->where('leave_type', $leave->leave_type)
            ->first();

        if ($balance) {
            if ($action === 'deduct') {
                $balance->increment('used_leaves', $days);
            } else {
                $balance->decrement('used_leaves', $days);
            }
        }
    }
}
