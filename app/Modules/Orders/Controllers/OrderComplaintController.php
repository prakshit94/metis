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
            new Middleware('permission:complaints.view',             only: ['index', 'stats', 'bulkExport', 'exportSelected']),
            new Middleware('permission:complaints.create',           only: ['store']),
            new Middleware('permission:complaints.edit',             only: ['update', 'bulkAction']),
            new Middleware('permission:complaints.delete',           only: ['destroy']),
            new Middleware('permission:complaints.restore',          only: ['restore']),
            new Middleware('permission:complaints.permanent-delete', only: ['forceDelete']),
            new Middleware('permission:complaints.reply',            only: ['reply']),
        ];
    }
    public function index(Request $request)
    {
        if ($request->wantsJson() || $request->ajax() || $request->is('api/*')) {
            try {
                $query = OrderComplaint::with(['order', 'customer', 'assignee', 'creator', 'statusLogs.user', 'replies.user']);

            // Visibility Logic
            if (!auth()->user()->hasPermissionTo('complaints.view-all')) {
                $query->where('assigned_to', auth()->id());
            }

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

            $stats = [
                'total' => OrderComplaint::count(),
                'open' => OrderComplaint::where('status', 'open')->count(),
                'in_progress' => OrderComplaint::where('status', 'in_progress')->count(),
                'resolved' => OrderComplaint::where('status', 'resolved')->count(),
                'closed' => OrderComplaint::where('status', 'closed')->count(),
            ];

            $assignableUsers = \App\Modules\Users\Models\User::where('is_active', true)->select('id', 'name', 'email')->get();

            return response()->json([
                'complaints' => $complaints,
                'stats' => $stats,
                'statuses' => ['open', 'in_progress', 'resolved', 'closed'],
                'priorities' => ['low', 'medium', 'high', 'urgent'],
                'assignable_users' => $assignableUsers,
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

        $complaint->statusLogs()->create([
            'status' => 'open',
            'notes' => 'Complaint created: ' . $complaint->subject . ' (Priority: ' . $complaint->priority . ')',
            'changed_by' => auth()->id()
        ]);

        $order->statusLogs()->create([
            'status' => $order->lifecycle_status,
            'notes' => 'Complaint logged: ' . $complaint->subject . ' (Priority: ' . $complaint->priority . ')',
            'changed_by' => auth()->id()
        ]);

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
            'subject' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'resolution_notes' => ['nullable', 'string'],
        ]);

        if (isset($validated['status'])) {
            if (in_array($validated['status'], ['resolved', 'closed'], true)) {
                if (!$complaint->resolved_at) {
                    $validated['resolved_at'] = now();
                }
            } else {
                $validated['resolved_at'] = null;
            }
        }

        $complaint->update($validated);

        if ($complaint->wasChanged('status')) {
            $complaint->statusLogs()->create([
                'status' => $complaint->status,
                'notes' => 'Status changed to: ' . $complaint->status,
                'changed_by' => auth()->id()
            ]);

            $complaint->order->statusLogs()->create([
                'status' => $complaint->order->lifecycle_status,
                'notes' => 'Complaint updated to: ' . $complaint->status,
                'changed_by' => auth()->id()
            ]);
        }

        if ($complaint->wasChanged('assigned_to')) {
            $complaint->statusLogs()->create([
                'status' => $complaint->status,
                'notes' => 'Assigned to user ID: ' . ($complaint->assigned_to ?: 'Unassigned'),
                'changed_by' => auth()->id()
            ]);
        }

        if ($complaint->wasChanged('priority')) {
            $complaint->statusLogs()->create([
                'status' => $complaint->status,
                'notes' => 'Priority changed to: ' . $complaint->priority,
                'changed_by' => auth()->id()
            ]);
        }

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
        $query = OrderComplaint::selectRaw('status, COUNT(*) as count')
            ->whereNull('deleted_at');

        if (!auth()->user()->hasPermissionTo('complaints.view-all')) {
            $query->where('assigned_to', auth()->id());
        }

        $counts = $query->groupBy('status')
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
            'action' => ['required', 'string', 'in:delete,resolve,close,assign,change_priority'],
            'ids'    => ['required', 'array', 'min:1'],
            'ids.*'  => ['integer', 'exists:order_complaints,id'],
            'assigned_to' => ['required_if:action,assign', 'nullable', 'integer', 'exists:users,id'],
            'priority' => ['required_if:action,change_priority', 'string', 'in:low,medium,high,urgent'],
        ]);

        $ids = $validated['ids'];

        switch ($validated['action']) {
            case 'delete':
                OrderComplaint::whereIn('id', $ids)->get()->each->delete();
                $message = 'Selected complaints deleted successfully.';
                break;
            case 'resolve':
                $complaints = OrderComplaint::with('order')->whereIn('id', $ids)->get();
                foreach ($complaints as $complaint) {
                    $complaint->update(['status' => 'resolved', 'resolved_at' => now()]);
                    $complaint->statusLogs()->create([
                        'status' => 'resolved',
                        'notes' => 'Bulk action: marked as resolved',
                        'changed_by' => auth()->id()
                    ]);
                    if ($complaint->order) {
                        $complaint->order->statusLogs()->create([
                            'status' => $complaint->order->lifecycle_status,
                            'notes' => 'Complaint bulk updated to: resolved',
                            'changed_by' => auth()->id()
                        ]);
                    }
                }
                $message = 'Selected complaints marked as resolved.';
                break;
            case 'close':
                $complaints = OrderComplaint::with('order')->whereIn('id', $ids)->get();
                foreach ($complaints as $complaint) {
                    $complaint->update([
                        'status' => 'closed',
                        'resolved_at' => $complaint->resolved_at ?? now()
                    ]);
                    $complaint->statusLogs()->create([
                        'status' => 'closed',
                        'notes' => 'Bulk action: marked as closed',
                        'changed_by' => auth()->id()
                    ]);
                    if ($complaint->order) {
                        $complaint->order->statusLogs()->create([
                            'status' => $complaint->order->lifecycle_status,
                            'notes' => 'Complaint bulk updated to: closed',
                            'changed_by' => auth()->id()
                        ]);
                    }
                }
                $message = 'Selected complaints closed.';
                break;
            case 'assign':
                $complaints = OrderComplaint::whereIn('id', $ids)->get();
                foreach ($complaints as $complaint) {
                    $complaint->update(['assigned_to' => $validated['assigned_to']]);
                    $complaint->statusLogs()->create([
                        'status' => $complaint->status,
                        'notes' => 'Bulk action: assigned to user ID ' . $validated['assigned_to'],
                        'changed_by' => auth()->id()
                    ]);
                }
                $message = 'Selected complaints assigned successfully.';
                break;
            case 'change_priority':
                $complaints = OrderComplaint::whereIn('id', $ids)->get();
                foreach ($complaints as $complaint) {
                    $complaint->update(['priority' => $validated['priority']]);
                    $complaint->statusLogs()->create([
                        'status' => $complaint->status,
                        'notes' => 'Bulk action: priority changed to ' . $validated['priority'],
                        'changed_by' => auth()->id()
                    ]);
                }
                $message = 'Selected complaints priority updated successfully.';
                break;
            default:
                return response()->json(['message' => 'Unknown action.'], 422);
        }

        return response()->json(['message' => $message]);
    }
    public function reply(Request $request, OrderComplaint $complaint): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string'],
        ]);

        $reply = $complaint->replies()->create([
            'user_id' => auth()->id(),
            'message' => $validated['message'],
        ]);

        return response()->json([
            'message' => 'Reply posted successfully.',
            'data' => $reply->load('user'),
        ]);
    }

    public function exportSelected(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:order_complaints,id'],
        ]);

        $complaints = OrderComplaint::with(['order', 'customer', 'assignee'])->whereIn('id', $validated['ids'])->get();

        $filename = 'complaints_export_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload($this->generateCsvExportCallback($complaints), $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function bulkExport(Request $request)
    {
        $query = OrderComplaint::with(['order', 'customer', 'assignee']);

        if (!auth()->user()->hasPermissionTo('complaints.view-all')) {
            $query->where('assigned_to', auth()->id());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $complaints = $query->get();

        $filename = 'complaints_bulk_export_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload($this->generateCsvExportCallback($complaints), $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function generateCsvExportCallback($complaints)
    {
        return function () use ($complaints) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Complaint ID', 'Order No', 'Customer', 'Assigned To', 'Category', 'Priority', 'Status', 'Subject', 'Created At', 'Resolved At'
            ]);

            foreach ($complaints as $complaint) {
                fputcsv($file, [
                    $complaint->complaint_number,
                    $complaint->order->order_no ?? 'N/A',
                    $complaint->customer ? trim($complaint->customer->firstname . ' ' . $complaint->customer->lastname) : 'N/A',
                    $complaint->assignee->name ?? 'Unassigned',
                    ucfirst($complaint->category),
                    ucfirst($complaint->priority),
                    ucfirst($complaint->status),
                    $complaint->subject,
                    $complaint->created_at->format('Y-m-d H:i:s'),
                    $complaint->resolved_at ? $complaint->resolved_at->format('Y-m-d H:i:s') : 'N/A',
                ]);
            }
            fclose($file);
        };
    }
}
