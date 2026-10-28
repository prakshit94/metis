<?php

declare(strict_types=1);

namespace App\Modules\Orders\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Customers\Models\Party;
use App\Modules\Customers\Models\WalletTransaction;
use App\Modules\Orders\Models\CreditNote;
use App\Modules\Orders\Models\Invoice;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderReturn;
use App\Modules\Orders\Models\Refund;
use App\Services\EnsureRecordVisibility;
use App\Services\FinancialService;
use App\Services\RecordVisibility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreditNoteController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(EnsureRecordVisibility::class),
            new Middleware('permission:credit-notes.view', only: ['index']),
            new Middleware('permission:credit-notes.create', only: ['store']),
            new Middleware('permission:credit-notes.create|credit-notes.edit', only: ['searchCustomers']),
            new Middleware('permission:credit-notes.edit', only: ['update']),
            new Middleware('permission:credit-notes.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $query = CreditNote::with(['customer', 'invoice.payments', 'invoice.refunds', 'orderReturn'])->latest();
            app(RecordVisibility::class)->creditNotes($query);

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
        $statusCounts = app(RecordVisibility::class)->creditNotes(CreditNote::query())->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $stats = [
            'total' => $statusCounts->sum(),
            'active' => $statusCounts->get('active', 0),
            'used' => $statusCounts->get('used', 0),
        ];

        $refundCustomers = Party::select('parties.id', 'parties.company_name', 'parties.firstname', 'parties.lastname', 'parties.phone', DB::raw('MAX(refunds.amount) as refund_amount'), DB::raw('MAX(refunds.invoice_id) as invoice_id'), DB::raw('MAX(refunds.order_return_id) as order_return_id'))
            ->where('parties.type', 'customer')
            ->whereHas('orders', fn ($q) => app(RecordVisibility::class)->orders($q, auth()->user()))
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('credit_notes')
                    ->whereColumn('credit_notes.customer_id', 'parties.id');
            })
            ->join('orders', 'orders.party_id', '=', 'parties.id')
            ->whereIn('orders.id', app(RecordVisibility::class)->orders(Order::query(), auth()->user())->select('id'))
            ->join('refunds', function ($join) {
                $join->on('refunds.order_id', '=', 'orders.id')
                    ->where('refunds.status', '!=', 'completed');
            })
            ->groupBy('parties.id', 'parties.company_name', 'parties.firstname', 'parties.lastname', 'parties.phone')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => trim(($c->company_name ?: trim($c->firstname.' '.$c->lastname)).' '.($c->phone ? '('.$c->phone.')' : '')),
                'amount' => $c->refund_amount,
                'invoice_id' => $c->invoice_id,
                'order_return_id' => $c->order_return_id,
            ]);

        $customerIds = $refundCustomers->pluck('id');

        $invoices = Invoice::select('invoices.id', 'invoices.invoice_no', 'invoices.net_amount', 'orders.party_id as customer_id')
            ->join('orders', 'orders.id', '=', 'invoices.order_id')
            ->whereIn('orders.party_id', $customerIds)
            ->whereIn('orders.id', app(RecordVisibility::class)->orders(Order::query(), auth()->user())->select('id'))
            ->toBase()->get();

        $returns = OrderReturn::select('order_returns.id', 'order_returns.return_no', 'orders.party_id as customer_id')
            ->join('orders', 'orders.id', '=', 'order_returns.order_id')
            ->whereIn('orders.party_id', $customerIds)
            ->whereIn('orders.id', app(RecordVisibility::class)->orders(Order::query(), auth()->user())->select('id'))
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
            ->whereHas('orders', fn ($q) => app(RecordVisibility::class)->orders($q, auth()->user()))
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('orders')
                    ->join('refunds', 'refunds.order_id', '=', 'orders.id')
                    ->whereColumn('orders.party_id', 'parties.id')
                    ->whereIn('orders.id', app(RecordVisibility::class)->orders(Order::query(), auth()->user())->select('id'));
            })
            ->toBase();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('firstname', 'LIKE', "%{$search}%")
                    ->orWhere('lastname', 'LIKE', "%{$search}%")
                    ->orWhere('company_name', 'LIKE', "%{$search}%");
            });
        }

        $customers = $query->limit(30)->get()->map(fn ($c) => [
            'id' => $c->id,
            'name' => $c->company_name ?: trim($c->firstname.' '.$c->lastname),
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

        $creditNote = DB::transaction(function () use ($validated) {
            $query = Refund::where('status', 'pending')->whereNull('credit_note_id')
                ->whereHas('order', fn ($q) => $q->where('party_id', $validated['customer_id']));
            if (! empty($validated['order_return_id'])) {
                $query->where('order_return_id', $validated['order_return_id']);
            }
            if (! empty($validated['invoice_id'])) {
                $query->where('invoice_id', $validated['invoice_id']);
            }
            $candidate = (clone $query)->orderBy('id')->first();
            if (! $candidate) {
                throw ValidationException::withMessages(['amount' => 'No pending refund matches this customer and references.']);
            }
            Order::lockForUpdate()->findOrFail($candidate->order_id);
            $party = Party::whereKey($validated['customer_id'])->lockForUpdate()->firstOrFail();
            if (CreditNote::where('customer_id', $party->id)->exists()) {
                throw ValidationException::withMessages(['customer_id' => 'This customer already has a credit note.']);
            }
            $refund = (clone $query)->whereKey($candidate->id)->lockForUpdate()->first();
            if (! $refund || abs((float) $refund->amount - (float) $validated['amount']) > 0.001) {
                throw ValidationException::withMessages(['amount' => 'The amount must match the selected pending refund.']);
            }
            app(RecordVisibility::class)->assertOrder($refund->order_id);
            $creditNote = CreditNote::create(['customer_id' => $party->id, 'invoice_id' => $refund->invoice_id,
                'order_return_id' => $refund->order_return_id, 'amount' => $refund->amount, 'balance_remaining' => 0, 'status' => 'used']);
            if ($validated['refund_method'] === 'wallet') {
                $before = (float) $party->wallet_balance;
                $after = $before + (float) $refund->amount;
                $party->update(['wallet_balance' => $after]);
                WalletTransaction::create(['party_id' => $party->id,
                    'amount' => $refund->amount, 'type' => 'credit', 'reference_type' => 'credit_note',
                    'reference_id' => $creditNote->id, 'description' => 'Refund added to wallet',
                    'created_by' => auth()->id(), 'balance_before' => $before, 'balance_after' => $after]);
            }
            $refund->update(['credit_note_id' => $creditNote->id, 'status' => 'completed', 'processed_by' => auth()->id(), 'processed_at' => now()]);
            if ($refund->orderReturn) {
                app(FinancialService::class)->syncReturnFinancials($refund->orderReturn);
            }

            return $creditNote;
        }, 3);

        return response()->json(['message' => 'Credit Note processed successfully', 'data' => $creditNote], 201);
    }

    public function update(Request $request, CreditNote $creditNote): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:active,used,cancelled',
            'balance_remaining' => 'required|numeric|min:0|max:'.$creditNote->amount,
        ]);

        if ($creditNote->status === 'used' && ($validated['status'] !== 'used' || (float) $validated['balance_remaining'] !== 0.0)) {
            throw ValidationException::withMessages(['status' => 'A settled credit note cannot be reopened.']);
        }
        $creditNote->update($validated);

        return response()->json(['message' => 'Credit Note updated successfully', 'data' => $creditNote]);
    }

    public function destroy(CreditNote $creditNote): JsonResponse
    {
        if ($creditNote->status === 'used') {
            throw ValidationException::withMessages(['status' => 'A settled credit note must retain its financial history.']);
        }
        $creditNote->delete();

        return response()->json(['message' => 'Credit Note deleted successfully']);
    }
}
