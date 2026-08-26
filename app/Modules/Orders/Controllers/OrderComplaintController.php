<?php

declare(strict_types=1);

namespace App\Modules\Orders\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Orders\Models\OrderComplaint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class OrderComplaintController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:complaints.view',             only: ['index', 'stats']),
            new Middleware('permission:complaints.create',           only: ['store']),
            new Middleware('permission:complaints.edit',             only: ['update', 'bulkAction']),
            new Middleware('permission:complaints.delete',           only: ['destroy']),
            new Middleware('permission:complaints.restore',          only: ['restore']),
            new Middleware('permission:complaints.permanent-delete', only: ['forceDelete']),
        ];
    }
    public function index(Request $request)
    {


        if ($request->wantsJson() || $request->ajax() || $request->is('api/*')) {
            try {
                $query = OrderComplaint::with(['order', 'customer', 'assignee', 'creator']);

            // Filtering
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('priority')) {
                $query->where('priority', $request->priority);
            }
            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }
            if ($request->filled('order_id')) {
                $query->where('order_id', $request->order_id);
            }
            if ($request->filled('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('complaint_number', 'like', "%{$search}%")
                      ->orWhere('subject', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhereHas('order', function ($o) use ($search) {
                          $o->where('order_no', 'like', "%{$search}%");
                      });
                });
            }

            // Sorting
            $sortBy = $request->input('sort_by', 'created_at');
            $sortDirection = $request->input('sort_direction', 'desc');
            $allowedSorts = ['created_at', 'status', 'priority', 'category', 'complaint_number'];
            if (in_array($sortBy, $allowedSorts, true)) {
                $query->orderBy($sortBy, $sortDirection === 'asc' ? 'asc' : 'desc');
            } else {
                $query->latest();
            }

            $perPage = (int) $request->input('per_page', 15);
            $complaints = $query->paginate($perPage);

            return response()->json([
                'data' => $complaints->items(),
                'meta' => [
                    'current_page' => $complaints->currentPage(),
                    'last_page' => $complaints->lastPage(),
                    'per_page' => $complaints->perPage(),
                    'total' => $complaints->total(),
                ]
            ]);
            } catch (\Exception $e) {
                \Log::error('OrderComplaint index error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                return response()->json(['error' => $e->getMessage()], 500);
            }
        }

        return view('orders.complaints.index');
    }

    public function store(Request $request): JsonResponse
    {


        $validated = $request->validate([
            'order_no' => ['required', 'string', 'exists:orders,order_no'],
            'customer_id' => ['nullable', 'integer', 'exists:parties,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'category' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'string', 'in:low,medium,high,urgent'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ]);

        $order = \App\Modules\Orders\Models\Order::where('order_no', $validated['order_no'])->firstOrFail();
        $validated['order_id'] = $order->id;

        if (empty($validated['customer_id'])) {
            $validated['customer_id'] = $order->party_id ?? null;
        }

        // Remove order_no so it doesn't cause mass-assignment issues
        unset($validated['order_no']);

        $complaint = OrderComplaint::create(array_merge($validated, [
            'status' => 'open',
        ]));

        return response()->json([
            'message' => 'Complaint created successfully.',
            'data' => $complaint->load(['order', 'customer', 'assignee', 'creator']),
        ], 201);
    }

    public function update(Request $request, OrderComplaint $complaint): JsonResponse
    {


        $validated = $request->validate([
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'category' => ['sometimes', 'string', 'max:255'],
            'priority' => ['sometimes', 'string', 'in:low,medium,high,urgent'],
            'status' => ['sometimes', 'string', 'in:open,in_progress,resolved,closed'],
            'resolution_notes' => ['nullable', 'string'],
        ]);

        if (isset($validated['status']) && in_array($validated['status'], ['resolved', 'closed'], true) && !$complaint->resolved_at) {
            $validated['resolved_at'] = now();
        }

        $complaint->update($validated);

        return response()->json([
            'message' => 'Complaint updated successfully.',
            'data' => $complaint->load(['order', 'customer', 'assignee', 'creator']),
        ]);
    }

    public function destroy(Request $request, OrderComplaint $complaint): JsonResponse
    {

        $complaint->delete();

        return response()->json([
            'message' => 'Complaint deleted successfully.',
        ]);
    }

    public function restore(int $id): JsonResponse
    {
        $complaint = OrderComplaint::withTrashed()->findOrFail($id);
        $complaint->restore();

        return response()->json([
            'message' => 'Complaint restored successfully.',
            'data'    => $complaint->load(['order', 'customer', 'assignee', 'creator']),
        ]);
    }

    public function forceDelete(int $id): JsonResponse
    {
        $complaint = OrderComplaint::withTrashed()->findOrFail($id);
        $complaint->forceDelete();

        return response()->json([
            'message' => 'Complaint permanently deleted.',
        ]);
    }

    public function stats(): JsonResponse
    {
        $counts = OrderComplaint::selectRaw('status, COUNT(*) as count')
            ->whereNull('deleted_at')
            ->groupBy('status')
            ->pluck('count', 'status');

        return response()->json([
            'total'       => $counts->sum(),
            'open'        => $counts->get('open', 0),
            'in_progress' => $counts->get('in_progress', 0),
            'resolved'    => $counts->get('resolved', 0),
            'closed'      => $counts->get('closed', 0),
        ]);
    }

    public function bulkAction(Request $request): JsonResponse
    {


        $validated = $request->validate([
            'action' => ['required', 'string', 'in:delete,resolve,close'],
            'ids'    => ['required', 'array', 'min:1'],
            'ids.*'  => ['integer', 'exists:order_complaints,id'],
        ]);

        $ids = $validated['ids'];

        switch ($validated['action']) {
            case 'delete':
                OrderComplaint::whereIn('id', $ids)->delete();
                $message = 'Selected complaints deleted successfully.';
                break;
            case 'resolve':
                OrderComplaint::whereIn('id', $ids)->update(['status' => 'resolved', 'resolved_at' => now()]);
                $message = 'Selected complaints marked as resolved.';
                break;
            case 'close':
                OrderComplaint::whereIn('id', $ids)->update(['status' => 'closed', 'resolved_at' => \DB::raw('COALESCE(resolved_at, CURRENT_TIMESTAMP)')]);
                $message = 'Selected complaints closed.';
                break;
        }

        return response()->json(['message' => $message]);
    }
}
