<?php

namespace App\Modules\Users\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Users\Models\LeaveBalance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LeaveBalanceController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:leave-view', only: ['index', 'show']),
            new Middleware('permission:leave-create', only: ['store']),
            new Middleware('permission:leave-edit', only: ['update']),
            new Middleware('permission:leave-delete', only: ['destroy', 'bulkAction']),
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $isGlobalView = $request->user() && ($request->user()->hasRole(['Super Admin', 'Admin']) || $request->user()->can('view-all-data'));

        $query = LeaveBalance::with('user:id,name,employee_id');

        if (! $isGlobalView) {
            $query->where('user_id', $request->user()->id);
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $balances = $query->paginate((int) $request->input('per_page', 15));

        return response()->json($balances);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'leave_type' => 'required|string',
            'total_leaves' => 'required|numeric|min:0',
            'used_leaves' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $balance = LeaveBalance::updateOrCreate(
            ['user_id' => $validated['user_id'], 'leave_type' => $validated['leave_type']],
            ['total_leaves' => $validated['total_leaves'], 'used_leaves' => $validated['used_leaves'], 'is_active' => $validated['is_active'] ?? true]
        );

        return response()->json(['data' => $balance, 'message' => 'Leave balance updated successfully.']);
    }

    public function update(Request $request, LeaveBalance $leaveBalance): JsonResponse
    {
        $validated = $request->validate([
            'total_leaves' => 'required|numeric|min:0',
            'used_leaves' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $leaveBalance->update($validated);

        return response()->json(['data' => $leaveBalance, 'message' => 'Leave balance updated successfully.']);
    }

    public function destroy(LeaveBalance $leaveBalance): JsonResponse
    {
        $leaveBalance->delete();

        return response()->json(['message' => 'Leave balance deleted successfully.']);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:delete',
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:leave_balances,id',
        ]);

        if ($validated['action'] === 'delete') {
            LeaveBalance::whereIn('id', $validated['ids'])->delete();

            return response()->json(['message' => 'Selected leave balances deleted successfully.']);
        }

        return response()->json(['message' => 'Invalid action.'], 400);
    }
}
