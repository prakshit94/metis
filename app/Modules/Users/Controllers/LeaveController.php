<?php

namespace App\Modules\Users\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Users\Models\Leave;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LeaveController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('leave-view'), 403);

        $leaves = Leave::with(['user', 'approver', 'applier'])->orderBy('start_date', 'desc')->paginate(request('per_page', 15));
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

        $validated['status'] = 'Pending';
        $validated['applied_by'] = auth()->id();

        $leave = Leave::create($validated);

        return response()->json(['data' => $leave, 'message' => 'Leave application submitted successfully.'], 201);
    }

    public function show(Request $request, Leave $leave): JsonResponse
    {
        abort_unless($request->user()?->can('leave-view'), 403);

        $leave->load(['user', 'approver', 'applier']);
        return response()->json(['data' => $leave]);
    }
    
    public function update(Request $request, Leave $leave): JsonResponse
    {
        abort_unless($request->user()?->can('leave-edit'), 403);
        
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'leave_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
            'status' => 'required|in:Pending,Approved,Rejected',
        ]);

        if (in_array($validated['status'], ['Approved', 'Rejected'])) {
            $validated['approved_by'] = auth()->id();
            $validated['approved_at'] = now();
        } elseif ($validated['status'] === 'Pending') {
            $validated['approved_by'] = null;
            $validated['approved_at'] = null;
        }

        $leave->update($validated);

        return response()->json(['data' => $leave, 'message' => 'Leave request updated successfully.']);
    }
    
    public function destroy(Request $request, Leave $leave): JsonResponse
    {
        abort_unless($request->user()?->can('leave-delete'), 403);
        
        $leave->delete();
        
        return response()->json(['message' => 'Leave request deleted successfully.']);
    }

    public function updateStatus(Request $request, Leave $leave): JsonResponse
    {
        abort_unless($request->user()?->can('leave-edit'), 403);

        $validated = $request->validate([
            'status' => 'required|in:Approved,Rejected',
        ]);

        $leave->update([
            'status' => $validated['status'],
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json(['data' => $leave, 'message' => 'Leave status updated successfully.']);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,reject,delete',
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:leaves,id',
        ]);

        $action = $validated['action'];
        $ids = $validated['ids'];

        if ($action === 'delete') {
            abort_unless($request->user()?->can('leave-delete'), 403);
            Leave::whereIn('id', $ids)->delete();
            return response()->json(['message' => 'Selected leaves deleted successfully.']);
        }

        abort_unless($request->user()?->can('leave-edit'), 403);

        $status = $action === 'approve' ? 'Approved' : 'Rejected';
        
        Leave::whereIn('id', $ids)->update([
            'status' => $status,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json(['message' => 'Selected leaves ' . strtolower($status) . ' successfully.']);
    }
}
