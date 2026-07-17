<?php

namespace App\Modules\Orders\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Orders\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with(['order.party', 'payments']);

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($subQuery) use ($s) {
                $subQuery->where('invoice_no', 'LIKE', "%{$s}%")
                    ->orWhereHas('order', function ($q) use ($s) {
                        $q->where('order_no', 'LIKE', "%{$s}%")
                          ->orWhereHas('party', function ($q2) use ($s) {
                              $q2->where('firstname', 'LIKE', "%{$s}%")
                                 ->orWhere('lastname', 'LIKE', "%{$s}%");
                          });
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $sortField = $request->input('sort_field', 'id');
        $sortDirection = $request->input('sort_direction', 'desc');

        $allowedSorts = ['id', 'invoice_no', 'net_amount', 'due_date', 'created_at'];
        if (in_array($sortField, $allowedSorts, true)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('id', 'desc');
        }

        $invoices = $query->paginate($request->integer('limit', 10));

        if ($request->wantsJson() || $request->ajax()) {
            $stats = [
                'total_invoiced' => (float) Invoice::sum('net_amount'),
                'paid' => Invoice::where('status', 'paid')->count(),
                'unpaid' => Invoice::whereIn('status', ['unpaid', 'partially_paid'])->count(),
                'avg_value' => (float) (Invoice::avg('net_amount') ?? 0),
            ];

            return response()->json([
                'invoices' => $invoices,
                'stats' => $stats,
            ]);
        }

        return view('orders.invoices.index', compact('invoices'));
    }

    public function bulkStatus(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:invoices,id',
            'status' => 'required|in:paid,unpaid,cancelled',
        ]);

        Invoice::whereIn('id', $validated['ids'])->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Selected invoices updated successfully.',
        ]);
    }
}
