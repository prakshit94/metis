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

    private function reviewableQuery(Request $request)
    {
        $query = $this->visibleQuery($request);
        if (! $this->isGlobalView($request)) {
            $query->whereHas('user', fn ($user) => $user
                ->where('manager_id', $request->user()->id)
                ->where('id', '!=', $request->user()->id));
        }
        return $query;
    }

    private function canReview(Request $request, Leave $leave): bool
    {
        return $request->user()?->can('leave-approve')
            && $this->reviewableQuery($request)->whereKey($leave->id)->exists();
    }

    /** Build reports from the same visibility rules as the leave directory. */
    private function visibleQuery(Request $request)
    {
        $teamIds = $this->isGlobalView($request) ? null : $request->user()?->getLobTeamUserIds();

        return Leave::query()->when($teamIds !== null,
            fn ($query) => $query->whereIn('user_id', $teamIds ?: [0]));
    }

    private function reportQuery(Request $request)
    {
        abort_unless($request->user()?->can('leave-view'), 403);
        $request->validate([
            'status' => 'nullable|in:Pending,Approved,Rejected',
            'history' => 'nullable|boolean',
            'per_page' => 'nullable|integer',
            'page' => 'nullable|integer|min:1',
            'user_id' => 'nullable|integer',
            'search' => 'nullable|string|max:200',
            'leave_type' => 'nullable|string|max:255',
            'from_date' => 'nullable|date_format:Y-m-d',
            'to_date' => ['nullable', 'date_format:Y-m-d', ...($request->filled('from_date') ? ['after_or_equal:from_date'] : [])],
            'team' => 'nullable|in:direct',
        ]);

        return $this->visibleQuery($request)
            ->with(['user', 'approver', 'applier'])
            ->when($request->input('team') === 'direct', fn ($query) => $query->whereHas('user', fn ($user) => $user->where('manager_id', $request->user()->id)->where('id', '!=', $request->user()->id)))
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->input('user_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->boolean('history'), fn ($query) => $query->where('status', '!=', 'Pending'))
            ->when($request->filled('leave_type'), fn ($query) => $query->where('leave_type', $request->input('leave_type')))
            // Include requests overlapping the selected period, not just requests starting inside it.
            ->when($request->filled('from_date'), fn ($query) => $query->where('end_date', '>=', $request->input('from_date')))
            ->when($request->filled('to_date'), fn ($query) => $query->where('start_date', '<=', $request->input('to_date')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.trim($request->input('search')).'%';
                $query->where(function ($search) use ($term) {
                    $search->where('reason', 'like', $term)
                        ->orWhere('leave_type', 'like', $term)
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $term)->orWhere('employee_id', 'like', $term));
                });
            })
            ->orderByDesc('start_date')->orderByDesc('id');
    }

    public function index(Request $request): JsonResponse
    {
        $query = $this->reportQuery($request);
        $summary = (clone $query)->reorder()->toBase()->select([])->selectRaw(
            "COUNT(*) as requests, COALESCE(SUM(DATEDIFF(end_date, start_date) + 1), 0) as requested_days,
             COALESCE(SUM(status = 'Pending'), 0) as pending,
             COALESCE(SUM(status = 'Approved'), 0) as approved,
             COALESCE(SUM(status = 'Rejected'), 0) as rejected"
        )->first();
        $types = $this->visibleQuery($request)->distinct()->orderBy('leave_type')->pluck('leave_type');
        $employees = $this->visibleQuery($request)->select('user_id')->distinct()
            ->with('user:id,name,employee_id')->orderBy('user_id')->get()
            ->pluck('user')->filter()->values();
        $leaves = $query->paginate(min(max($request->integer('per_page', 15), 1), 100));

        $reviewableIds = $request->user()?->can('leave-approve')
            ? $this->reviewableQuery($request)->whereIn('id', $leaves->getCollection()->pluck('id'))->pluck('id')->all() : [];
        $leaves->getCollection()->each(fn ($leave) => $leave->setAttribute('can_review', in_array($leave->id, $reviewableIds)));

        return response()->json(array_merge($leaves->toArray(), [
            'summary' => $summary,
            'filter_options' => ['employees' => $employees, 'leave_types' => $types],
        ]));
    }

    public function export(Request $request)
    {
        abort_unless($request->user()?->can('leave-export'), 403);
        $query = $this->reportQuery($request);
        return response()->streamDownload(function () use ($query) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Employee', 'Employee ID', 'Leave Type', 'Start Date', 'End Date', 'Days', 'Status', 'Reason', 'Applied By', 'Processed By', 'Processed At']);
            foreach ($query->lazy(250) as $leave) {
                $row = [
                    $leave->user?->name, $leave->user?->employee_id, $leave->leave_type,
                    $leave->start_date->format('Y-m-d'), $leave->end_date->format('Y-m-d'),
                    $leave->start_date->diffInDays($leave->end_date) + 1, $leave->status,
                    $leave->reason, $leave->applier?->name, $leave->approver?->name,
                    $leave->approved_at?->format('Y-m-d H:i:s'),
                ];
                // Employee-entered text must remain text when opened in a spreadsheet.
                fputcsv($file, array_map(fn ($value) => is_string($value) && preg_match('/^[\s]*[=+@-]/u', $value) ? "'".$value : $value, $row));
            }
            fclose($file);
        }, 'leave-report-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
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
                abort_unless($request->user()?->can('leave-approve'), 403);
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
            abort_unless($request->user()?->can('leave-approve'), 403);
            $leave = Leave::query()->lockForUpdate()->findOrFail($leave->id);

            abort_unless($this->canReview($request, $leave), 403, 'Unauthorized to approve or reject this leave request.');

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

            abort_unless($request->user()?->can('leave-approve'), 403);

            $status = $action === 'approve' ? 'Approved' : 'Rejected';

            $leaves = $this->reviewableQuery($request)->whereIn('id', $ids)->where('status', 'Pending')->orderBy('id')->lockForUpdate()->get();
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

            return response()->json(['message' => $leaves->count().' leave request(s) '.strtolower($status).'.', 'processed' => $leaves->count(), 'skipped' => count($ids) - $leaves->count()]);
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
