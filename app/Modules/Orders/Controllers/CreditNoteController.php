<?php

declare(strict_types=1);

namespace App\Modules\Orders\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Customers\Models\Party;
use App\Modules\Orders\Models\CreditNote;
use App\Modules\Orders\Models\Invoice;
use App\Modules\Orders\Models\OrderReturn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CreditNoteController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:orders.view', only: ['index', 'searchCustomers']),
            new Middleware('permission:orders.receipt', only: ['store', 'update', 'destroy']),
        ];
    }

    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $query = CreditNote::with(['customer', 'invoice.payments', 'invoice.refunds', 'orderReturn'])->latest();

            if ($request->has('search') && ! empty($request->query('search'))) {
                $search = $request->query('search');
                $query->where(function ($q) use ($search) {
                    $q->where('id', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($sq) use ($search) {
                            $sq->where('firstname', 'like', "%{$search}%")
                                ->orWhere('lastname', 'like', "%{$search}%")
                                ->orWhere('company_name', 'like', "%{$search}%");
                        });
                });
            }

            if ($request->has('status') && ! empty($request->query('status'))) {
                $query->where('status', $request->query('status'));
            }

            return response()->json($query->paginate(15));
        }

        // Single grouped query instead of 3 separate COUNT queries
        $statusCounts = CreditNote::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $stats = [
            'total'  => $statusCounts->sum(),
            'active' => $statusCounts->get('active', 0),
            'used'   => $statusCounts->get('used', 0),
        ];

        // No customers preloaded — dropdown uses live search via searchCustomers()
        $invoices = Invoice::select('id', 'invoice_no', 'net_amount')->latest()->limit(100)->toBase()->get();
        $returns = OrderReturn::select('id', 'return_no')->latest()->limit(100)->toBase()->get();

        return view('orders.credit-notes.index', compact('stats', 'invoices', 'returns'));
    }

    /**
     * Live-search endpoint for the customer dropdown.
     * Returns up to 30 matching customers — used by the Alpine component
     * instead of embedding all 78K+ customers in the initial page HTML.
     */
    public function searchCustomers(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('q', ''));

        $query = Party::select('id', 'company_name', 'firstname', 'lastname')
            ->where('type', 'customer')
            ->toBase();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('firstname', 'LIKE', "%{$search}%")
                  ->orWhere('lastname',    'LIKE', "%{$search}%")
                  ->orWhere('company_name','LIKE', "%{$search}%");
            });
        }

        $customers = $query->limit(30)->get()->map(fn ($c) => [
            'id'   => $c->id,
            'name' => $c->company_name ?: trim($c->firstname . ' ' . $c->lastname),
        ]);

        return response()->json($customers);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:parties,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'order_return_id' => 'nullable|exists:order_returns,id',
            'amount' => 'required|numeric|min:0.01',
            'status' => 'required|in:active,used,cancelled',
        ]);

        // Balance remaining should be zero for any non-active status
        $validated['balance_remaining'] = ($validated['status'] === 'active')
            ? $validated['amount']
            : 0;

        $creditNote = CreditNote::create($validated);

        return response()->json(['message' => 'Credit Note created successfully', 'data' => $creditNote], 201);
    }

    public function update(Request $request, CreditNote $creditNote): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:active,used,cancelled',
            'balance_remaining' => 'required|numeric|min:0|max:'.$creditNote->amount,
        ]);

        $creditNote->update($validated);

        return response()->json(['message' => 'Credit Note updated successfully', 'data' => $creditNote]);
    }

    public function destroy(CreditNote $creditNote): JsonResponse
    {
        $creditNote->delete();

        return response()->json(['message' => 'Credit Note deleted successfully']);
    }
}
