<?php

namespace App\Modules\Orders\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Orders\Models\Invoice;
use App\Modules\Orders\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\FinancialService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:orders.view', only: ['index', 'show', 'exportSelected', 'downloadPdf']),
            new Middleware('permission:orders.receipt', only: ['bulkStatus', 'recordPayment']),
        ];
    }

    public function index(Request $request)
    {
        $query = Invoice::with(['order' => fn ($query) => $query->withExists([
            'items as is_unfulfillable' => function ($items) {
                $items->leftJoin('stocks', function ($join) {
                    $join->on('order_items.product_id', '=', 'stocks.product_id')
                        ->whereColumn('stocks.warehouse_id', 'orders.warehouse_id');
                })->where(function ($stock) {
                    $stock->whereNull('stocks.id')
                        ->orWhereRaw('order_items.quantity > (stocks.quantity - stocks.reserved_qty)');
                });
            },
        ]), 'order.party', 'order.orderReturns', 'order.shipments', 'payments.recorder'])
            ->withSum(['refunds as refunded_amount_total' => fn ($query) => $query->where('status', 'completed')], 'amount');

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($subQuery) use ($s) {
                $subQuery->where('invoice_no', 'LIKE', "%{$s}%")
                    ->orWhereHas('order', function ($q) use ($s) {
                        $q->where('order_no', 'LIKE', "%{$s}%");
                        if (is_numeric($s)) {
                            $q->orWhere('id', $s);
                        }
                        $q->orWhereHas('party', function ($q2) use ($s) {
                            $q2->where('firstname', 'LIKE', "%{$s}%")
                                ->orWhere('lastname', 'LIKE', "%{$s}%");
                        });
                    });
            });
        }

        // Match the Orders index status filters without changing order or invoice state.
        if ($request->filled('order_status')) {
            $requestedStatuses = array_filter(array_map('trim', explode(',', $request->string('order_status')->toString())));
            $hasFutureOrder = in_array('future_order', $requestedStatuses, true);
            $hasPending = in_array('pending', $requestedStatuses, true);
            $hasUnfulfillable = in_array('unfulfillable', $requestedStatuses, true);
            $hasDeliveryAttempted = in_array('delivery_attempted', $requestedStatuses, true);
            $hasDispatched = in_array('dispatched', $requestedStatuses, true);
            $hasReturnRequested = in_array('return_requested', $requestedStatuses, true);
            $hasReturned = in_array('returned', $requestedStatuses, true);

            $realStatuses = array_values(array_filter($requestedStatuses, fn ($s) => ! in_array($s, ['future_order', 'pending', 'unfulfillable', 'delivery_attempted', 'dispatched', 'return_requested', 'returned'])));

            $query->whereHas('order', function ($q) use ($hasFutureOrder, $hasPending, $hasUnfulfillable, $realStatuses, $hasDispatched, $hasDeliveryAttempted, $hasReturnRequested, $hasReturned) {
                $first = true;

                if ($hasFutureOrder) {
                    $q->where('status', 'future_order');
                    $first = false;
                }

                if ($hasPending) {
                    $method = $first ? 'where' : 'orWhere';
                    $q->$method(function ($sq) {
                        $sq->where('status', 'pending')
                           ->whereDoesntHave('items', function ($itemQuery) {
                               $itemQuery->leftJoin('stocks', function ($join) {
                                   $join->on('order_items.product_id', '=', 'stocks.product_id')
                                        ->whereColumn('stocks.warehouse_id', 'orders.warehouse_id');
                               })->where(function ($sub) {
                                   $sub->whereNull('stocks.id')
                                       ->orWhereRaw('order_items.quantity > (stocks.quantity - stocks.reserved_qty)');
                               });
                           });
                    });
                    $first = false;
                }

                if ($hasUnfulfillable) {
                    $method = $first ? 'where' : 'orWhere';
                    $q->$method(function ($sq) {
                        $sq->where('status', 'pending')
                           ->whereHas('items', function ($itemQuery) {
                               $itemQuery->leftJoin('stocks', function ($join) {
                                   $join->on('order_items.product_id', '=', 'stocks.product_id')
                                        ->whereColumn('stocks.warehouse_id', 'orders.warehouse_id');
                               })->where(function ($sub) {
                                   $sub->whereNull('stocks.id')
                                       ->orWhereRaw('order_items.quantity > (stocks.quantity - stocks.reserved_qty)');
                               });
                           });
                    });
                    $first = false;
                }

                if ($hasDispatched) {
                    $method = $first ? 'where' : 'orWhere';
                    $q->$method(function ($sq) {
                        $sq->whereIn('status', ['dispatched'])
                           ->whereDoesntHave('shipments', function ($ssq) {
                               $ssq->where('delivery_attempts', '>', 0);
                           });
                    });
                    $first = false;
                }

                if ($hasDeliveryAttempted) {
                    $method = $first ? 'where' : 'orWhere';
                    $q->$method(function ($sq) {
                        $sq->whereIn('status', ['dispatched'])
                           ->whereHas('shipments', function ($ssq) {
                               $ssq->where('delivery_attempts', '>', 0);
                           });
                    });
                    $first = false;
                }

                if ($hasReturnRequested) {
                    $method = $first ? 'where' : 'orWhere';
                    $q->$method(function ($sq) {
                        $sq->whereHas('orderReturns', function ($returnQuery) {
                            $returnQuery->whereNotIn('status', ['completed', 'rejected'])
                                ->whereNotExists(function ($newerReturn) {
                                    $newerReturn->select(DB::raw(1))
                                        ->from('order_returns as newer_order_returns')
                                        ->whereColumn('newer_order_returns.order_id', 'order_returns.order_id')
                                        ->whereColumn('newer_order_returns.id', '>', 'order_returns.id');
                                });
                        });
                    });
                    $first = false;
                }

                if ($hasReturned) {
                    $method = $first ? 'where' : 'orWhere';
                    $q->$method(function ($sq) {
                        $sq->whereHas('orderReturns', function ($returnQuery) {
                            $returnQuery->where('status', 'completed')
                                ->whereNotExists(function ($newerReturn) {
                                    $newerReturn->select(DB::raw(1))
                                        ->from('order_returns as newer_order_returns')
                                        ->whereColumn('newer_order_returns.order_id', 'order_returns.order_id')
                                        ->whereColumn('newer_order_returns.id', '>', 'order_returns.id');
                                });
                        })->orWhere(function ($legacyReturned) {
                            $legacyReturned->where('orders.status', 'returned')
                                ->where(function ($noActiveReturn) {
                                    $noActiveReturn->whereDoesntHave('orderReturns')
                                        ->orWhereHas('orderReturns', function ($returnQuery) {
                                            $returnQuery->where('status', 'rejected')
                                                ->whereNotExists(function ($newerReturn) {
                                                    $newerReturn->select(DB::raw(1))
                                                        ->from('order_returns as newer_order_returns')
                                                        ->whereColumn('newer_order_returns.order_id', 'order_returns.order_id')
                                                        ->whereColumn('newer_order_returns.id', '>', 'order_returns.id');
                                                });
                                        });
                                });
                        });
                    });
                    $first = false;
                }

                if (! empty($realStatuses)) {
                    $method = $first ? 'whereIn' : 'orWhereIn';
                    $q->$method('status', $realStatuses);
                }
            });
        }

        // Clone base stats query before paginating or applying status filters
        $baseStatsQuery = clone $query;
        $statsQuery = fn () => clone $baseStatsQuery;

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

        // Preload lifecycle data without adding relationships to the API payload.
        $invoices->getCollection()->each(function ($invoice) {
            $invoice->makeHidden('refunded_amount_total');
            $invoice->order?->makeHidden(['orderReturns', 'shipments', 'is_unfulfillable']);
        });

        if ($request->wantsJson() || $request->ajax()) {
            $amounts = $statsQuery()->toBase()->select([])->selectRaw('SUM(net_amount) as total, AVG(net_amount) as average')->first();
            $totalInvoiced = (float) $amounts->total;

            // Subquery prevents packet limit / bindings explosion compared to pluck('id')
            $collectedAmount = (float) Payment::where('status', 'completed')
                ->joinSub($statsQuery()->select('id'), 'inv', 'payments.invoice_id', '=', 'inv.id')
                ->sum('amount');

            $pendingAmount = max(0, $totalInvoiced - $collectedAmount);

            $avgValue = (float) $amounts->average;

            $stats = [
                'total_invoiced' => $totalInvoiced,
                'collected_amount' => $collectedAmount,
                'pending_amount' => $pendingAmount,
                'avg_value' => $avgValue ?: 0,
            ];

            return response()->json([
                'invoices' => $invoices,
                'stats' => $stats,
            ]);
        }

        return view('orders.invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load([
            'order.party',
            'payments' => fn ($query) => $query->with(['recorder', 'reverter'])->orderByDesc('payment_date')->orderByDesc('id'),
        ]);

        return response()->json(['invoice' => $invoice]);
    }

    public function downloadPdf(Invoice $invoice)
    {
        $invoice->load([
            'order.party',
            'order.items.product.taxRate',
            'order.shippingAddress.village',
            'order.billingAddress.village',
        ]);

        $pdf = Pdf::loadView('orders.pdf.invoice', compact('invoice'))->setPaper('a5', 'portrait');

        return $pdf->download("invoice-{$invoice->invoice_no}.pdf");
    }

    public function bulkStatus(Request $request, FinancialService $financialService)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:invoices,id',
            'status' => 'required|in:paid,unpaid,cancelled',
        ]);

        if (in_array($validated['status'], ['paid', 'unpaid'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice status is automatically derived from payments. You cannot manually force an invoice to be paid or unpaid without recording an actual payment.',
            ], 422);
        }

        $user = $request->user();

        // Scope: prevent users from updating invoices for orders they don't own
        $query = Invoice::whereIn('id', $validated['ids']);

        $invoices = $query->get();

        if ($validated['status'] === 'cancelled') {
            foreach ($invoices as $invoice) {
                if ($invoice->paid_amount > 0) {
                    return response()->json([
                        'success' => false,
                        'message' => "Cannot cancel Invoice #{$invoice->invoice_no} because it has completed payments. Please refund all associated payments first.",
                    ], 422);
                }
            }
        }

        DB::transaction(function () use ($invoices, $validated) {
            foreach ($invoices as $invoice) {
                $invoice->update(['status' => $validated['status']]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Selected invoices updated successfully.',
        ]);
    }

    public function recordPayment(Request $request, Invoice $invoice, FinancialService $financialService)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'transaction_id' => 'nullable|string',
            'payment_date' => 'nullable|date',
        ]);

        try {
            $payment = $financialService->processPayment(
                $invoice,
                (float) $validated['amount'],
                $validated['payment_method'],
                $validated['transaction_id'] ?? null,
                $validated['payment_date'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully.',
                'payment' => $payment,
            ]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to record payment: '.$e->getMessage()], 500);
        }
    }

    public function exportSelected(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:invoices,id',
        ]);

        $user = $request->user();

        $query = Invoice::whereIn('id', $validated['ids'])->with(['order.party', 'payments']);

        $invoices = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="invoices_export.csv"',
        ];

        $columns = [
            'reference_type', 'reference_no', 'amount', 'payment_method', 'transaction_id', 'payment_date',
            'order_no', 'customer_name', 'invoice_date', 'due_date', 'total_amount', 'tax_amount', 'net_amount', 'paid_amount', 'status',
        ];

        $callback = function () use ($columns, $invoices) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($invoices as $invoice) {
                $order = $invoice->order;
                $party = $order ? $order->party : null;
                $customerName = $party ? trim($party->firstname.' '.$party->lastname) : '';

                $latestPayment = $invoice->payments()->latest('payment_date')->first();

                $row = [
                    'invoice',
                    $invoice->invoice_no,
                    number_format($invoice->due_amount, 2, '.', ''),
                    $latestPayment ? $latestPayment->payment_method : '', // payment_method
                    $latestPayment ? $latestPayment->transaction_id : '', // transaction_id
                    $latestPayment && $latestPayment->payment_date ? $latestPayment->payment_date->format('Y-m-d H:i:s') : '', // payment_date
                    $order ? $order->order_no : '',
                    $customerName,
                    $invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d') : '',
                    $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '',
                    number_format($invoice->total_amount, 2, '.', ''),
                    number_format($invoice->tax_amount, 2, '.', ''),
                    number_format($invoice->net_amount, 2, '.', ''),
                    number_format($invoice->paid_amount, 2, '.', ''),
                    $invoice->status,
                ];
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
