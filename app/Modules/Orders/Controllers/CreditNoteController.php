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



        $refundCustomers = Party::select('parties.id', 'parties.company_name', 'parties.firstname', 'parties.lastname', 'parties.phone', \Illuminate\Support\Facades\DB::raw('MAX(refunds.amount) as refund_amount'), \Illuminate\Support\Facades\DB::raw('MAX(refunds.invoice_id) as invoice_id'), \Illuminate\Support\Facades\DB::raw('MAX(refunds.order_return_id) as order_return_id'))
            ->where('parties.type', 'customer')
            ->whereNotExists(function ($q) {
                $q->select(\Illuminate\Support\Facades\DB::raw(1))
                  ->from('credit_notes')
                  ->whereColumn('credit_notes.customer_id', 'parties.id');
            })
            ->join('orders', 'orders.party_id', '=', 'parties.id')
            ->join('refunds', function($join) {
                $join->on('refunds.order_id', '=', 'orders.id')
                     ->where('refunds.status', '!=', 'completed');
            })
            ->groupBy('parties.id', 'parties.company_name', 'parties.firstname', 'parties.lastname', 'parties.phone')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => trim(($c->company_name ?: trim($c->firstname . ' ' . $c->lastname)) . ' ' . ($c->phone ? '('.$c->phone.')' : '')),
                'amount' => $c->refund_amount,
                'invoice_id' => $c->invoice_id,
                'order_return_id' => $c->order_return_id
            ]);

        $customerIds = $refundCustomers->pluck('id');
        
        $invoices = Invoice::select('invoices.id', 'invoices.invoice_no', 'invoices.net_amount', 'orders.party_id as customer_id')
            ->join('orders', 'orders.id', '=', 'invoices.order_id')
            ->whereIn('orders.party_id', $customerIds)
            ->toBase()->get();
            
        $returns = OrderReturn::select('order_returns.id', 'order_returns.return_no', 'orders.party_id as customer_id')
            ->join('orders', 'orders.id', '=', 'order_returns.order_id')
            ->whereIn('orders.party_id', $customerIds)
            ->toBase()->get();

        return view('orders.credit-notes.index', compact('stats', 'invoices', 'returns', 'refundCustomers'));
    }

    /**
     * Live-search endpoint for the customer dropdown.
     * Returns up to 30 matching customers — used by the Alpine component
     * instead of embedding all 78K+ customers in the initial page HTML.
     */
    public function searchCustomers(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('q', ''));

        $query = Party::select('parties.id', 'parties.company_name', 'parties.firstname', 'parties.lastname')
            ->where('parties.type', 'customer')
            ->whereExists(function ($q) {
                $q->select(\Illuminate\Support\Facades\DB::raw(1))
                  ->from('orders')
                  ->join('refunds', 'refunds.order_id', '=', 'orders.id')
                  ->whereColumn('orders.party_id', 'parties.id');
            })
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
            'customer_id' => 'required|exists:parties,id|unique:credit_notes,customer_id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'order_return_id' => 'nullable|exists:order_returns,id',
            'amount' => 'required|numeric|min:0.01',
            'refund_method' => 'required|in:wallet,direct',
            'status' => 'nullable|in:active,used,cancelled',
        ]);

        $creditNote = null;
        
        \Illuminate\Support\Facades\DB::transaction(function () use ($validated, &$creditNote) {
            $validated['status'] = 'used';
            $validated['balance_remaining'] = 0;
            
            $refundMethod = $validated['refund_method'];
            unset($validated['refund_method']);
            
            $creditNote = CreditNote::create($validated);
            
            if ($refundMethod === 'wallet') {
                $party = \App\Modules\Customers\Models\Party::findOrFail($validated['customer_id']);
                $balanceBefore = $party->wallet_balance;
                $balanceAfter = $balanceBefore + $validated['amount'];
                
                \App\Modules\Customers\Models\WalletTransaction::create([
                    'party_id' => $party->id,
                    'amount' => $validated['amount'],
                    'type' => 'credit',
                    'reference_type' => 'credit_note',
                    'reference_id' => $creditNote->id,
                    'description' => 'Refund added to wallet',
                    'created_by' => auth()->id(),
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                ]);
                
                $party->update(['wallet_balance' => $balanceAfter]);
            }
            
            $refund = null;
            if (!empty($validated['order_return_id'])) {
                $refund = \App\Modules\Orders\Models\Refund::where('order_return_id', $validated['order_return_id'])->first();
            } elseif (!empty($validated['invoice_id'])) {
                $refund = \App\Modules\Orders\Models\Refund::where('invoice_id', $validated['invoice_id'])->first();
            } else {
                $refund = \App\Modules\Orders\Models\Refund::whereHas('order', function($q) use ($validated) {
                    $q->where('party_id', $validated['customer_id']);
                })->where('status', '!=', 'completed')->first();
            }
            
            if ($refund) {
                $refund->update([
                    'status' => 'completed',
                    'processed_by' => auth()->id(),
                    'processed_at' => now(),
                ]);
            }
        });

        return response()->json(['message' => 'Credit Note processed successfully', 'data' => $creditNote], 201);
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
