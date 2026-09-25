<?php

namespace App\Modules\Orders\Controllers;

use App\Modules\Catalog\Models\Service;
use App\Modules\Core\Controllers\Controller;
use App\Modules\Inventory\Models\Stock;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Orders\Models\OrderReturn;
use App\Modules\Orders\Models\OrderReturnItem;
use App\Modules\Orders\Models\Refund;
use App\Services\FinancialService;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class OrderReturnController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:orders.view', only: ['index', 'show']),
            new Middleware('permission:orders.return', only: ['store', 'process', 'processQc', 'processFinancials']),
            new Middleware('permission:orders.bulk_return', only: ['bulkStore']),
        ];
    }

    public function index(Request $request)
    {
        $query = OrderReturn::with(['order.party', 'order.payments', 'order.shipments', 'items.product', 'refunds', 'creditNote']);

        $user = auth()->user();
        $isGlobalView = $user && ($user->hasRole(['Super Admin', 'Admin']) || $user->can('view-all-data'));
        
        if (! $isGlobalView) {
            $allowedStatuses = [];
            if ($user) {
                if ($user->can('orders.view.future_order')) $allowedStatuses[] = 'future_order';
                if ($user->can('orders.view.pending')) {
                    $allowedStatuses[] = 'pending';
                    $allowedStatuses[] = 'pending_confirmation';
                }
                if ($user->can('orders.view.confirmed')) $allowedStatuses[] = 'confirmed';
                if ($user->can('orders.view.processing')) $allowedStatuses[] = 'processing';
                if ($user->can('orders.view.ready_to_ship')) $allowedStatuses[] = 'ready_to_ship';
                if ($user->can('orders.view.dispatched')) $allowedStatuses[] = 'dispatched';
                if ($user->can('orders.view.delivered')) $allowedStatuses[] = 'delivered';
                if ($user->can('orders.view.return_requested')) $allowedStatuses[] = 'return_requested';
                if ($user->can('orders.view.returned')) $allowedStatuses[] = 'returned';
                if ($user->can('orders.view.cancelled')) $allowedStatuses[] = 'cancelled';
            }

            $query->whereHas('order', function ($q) use ($user, $allowedStatuses) {
                if (empty($allowedStatuses)) {
                    $q->whereRaw('1 = 0');
                } else {
                    $q->whereIn('status', $allowedStatuses);
                    if (! ($user && $user->can('view_all_order'))) {
                        $q->where('created_by', $user->id);
                    }
                }
            });
        }

        // LOB/State scoping: restrict to returns for orders in the user's state
        if ($lobStateName = $user?->lob_state_name) {
            $query->whereHas('order', fn ($q) => $q->where('shipping_state', $lobStateName));
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($subQuery) use ($s) {
                $subQuery->where('return_no', 'LIKE', "%{$s}%")
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

        if ($request->filled('financial_status')) {
            $query->where('financial_status', $request->financial_status);
        }

        if ($request->filled('shipping_service')) {
            $query->whereHas('order.shipments', function ($q) use ($request) {
                $q->where('carrier_name', $request->shipping_service);
            });
        }

        $sortField = $request->input('sort_field', 'id');
        $sortDirection = $request->input('sort_direction', 'desc');

        $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');

        $returns = $query->paginate($request->integer('limit', 15));

        if ($request->wantsJson() || $request->ajax()) {
            $stats = [
                'total' => (clone $query)->count(),
                'pending' => (clone $query)->where('order_returns.status', 'pending')->count(),
                'approved' => (clone $query)->where('order_returns.status', 'approved')->count(),
                'received' => (clone $query)->where('order_returns.status', 'received')->count(),
                'qc_in_progress' => (clone $query)->where('order_returns.status', 'qc_in_progress')->count(),
                'pending_qc' => (clone $query)->whereIn('order_returns.status', ['pending', 'approved', 'received', 'qc_in_progress'])->count(),
                'completed' => (clone $query)->where('order_returns.status', 'completed')->count(),
                'rejected' => (clone $query)->where('order_returns.status', 'rejected')->count(),
                'total_refunded' => (clone $query)->sum('order_returns.refund_amount'),
                'total_credited' => (clone $query)->sum('order_returns.credit_note_amount'),
            ];

            return response()->json([
                'returns' => $returns,
                'stats' => $stats,
                'statuses' => ['pending', 'received', 'qc_in_progress', 'completed', 'rejected'],
                'financial_statuses' => ['pending', 'partial_refund', 'fully_refunded', 'credited'],
                'shipping_services' => Service::active()->select('name')->pluck('name'),
            ]);
        }

        return view('orders.returns.index', compact('returns'));
    }

    public function store(Request $request, Order $order)
    {
        // Ensure order items are loaded (used in product ownership validation below)
        $order->loadMissing('items');
        $orderProductIds = $order->items->pluck('product_id')->toArray();

        $validated = $request->validate([
            'reason' => 'required|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                'integer',
                'exists:products,id',
                // Ensure the product actually belongs to this order
                function ($attribute, $value, $fail) use ($orderProductIds) {
                    if (! in_array((int) $value, $orderProductIds)) {
                        $fail('Product ID '.$value.' was not part of this order and cannot be returned.');
                    }
                },
            ],
            'items.*.requested_qty' => 'required|numeric|gt:0',
        ]);

        $return = DB::transaction(function () use ($validated, $order) {
            $baseNo = str_replace('ORD-', 'RET-', $order->order_no);
            if ($baseNo === $order->order_no) {
                $baseNo = 'RET-'.$order->order_no;
            }
            $count = OrderReturn::where('order_id', $order->id)->count();
            $returnNo = $count > 0 ? $baseNo.'-'.($count + 1) : $baseNo;

            $return = OrderReturn::create([
                'order_id' => $order->id,
                'return_no' => $returnNo,
                'status' => 'pending',
                'reason' => $validated['reason'],
                'notes' => $validated['notes'],
            ]);

            $wasInTransit = in_array($order->status, Order::inTransitStatuses(), true);

            foreach ($validated['items'] as $item) {
                OrderReturnItem::create([
                    'order_return_id' => $return->id,
                    'product_id' => $item['product_id'],
                    'requested_qty' => $item['requested_qty'],
                ]);

                if ($wasInTransit && $order->warehouse_id) {
                    $stock = Stock::where('product_id', $item['product_id'])
                        ->where('warehouse_id', $order->warehouse_id)
                        ->lockForUpdate()
                        ->first();

                    if ($stock) {
                        $stock->dispatched_qty = max(0.0, (float) $stock->dispatched_qty - (float) $item['requested_qty']);
                        $stock->save();
                    }
                }
            }

            // Update the main order status and log it
            $order->update([
                'status' => 'return_requested',
                'updated_by' => auth()->id(),
            ]);

            $order->statusLogs()->create([
                'status' => 'return_requested',
                'notes' => 'Return initiated. Reason: '.$validated['reason'],
                'changed_by' => auth()->id(),
            ]);

            return $return;
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Return request initiated.', 'return' => $return]);
        }

        return back()->with('success', 'Return request initiated successfully.');
    }

    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer|exists:orders,id',
            'reason' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $count = 0;
        $skipped = 0;
        $errors = [];

        DB::transaction(function () use ($validated, &$count, &$skipped, &$errors) {
            $orders = Order::with('items')->whereIn('id', $validated['order_ids'])->get();
            
            foreach ($orders as $order) {
                if (!in_array($order->status, ['delivered', 'dispatched'], true)) {
                    $skipped++;
                    continue;
                }

                try {
                    $baseNo = str_replace('ORD-', 'RET-', $order->order_no);
                    if ($baseNo === $order->order_no) {
                        $baseNo = 'RET-'.$order->order_no;
                    }
                    $returnCount = OrderReturn::where('order_id', $order->id)->count();
                    $returnNo = $returnCount > 0 ? $baseNo.'-'.($returnCount + 1) : $baseNo;

                    $return = OrderReturn::create([
                        'order_id' => $order->id,
                        'return_no' => $returnNo,
                        'status' => 'pending',
                        'reason' => $validated['reason'],
                        'notes' => $validated['notes'],
                    ]);

                    $wasInTransit = in_array($order->status, Order::inTransitStatuses(), true);

                    foreach ($order->items as $item) {
                        OrderReturnItem::create([
                            'order_return_id' => $return->id,
                            'product_id' => $item->product_id,
                            'requested_qty' => $item->quantity,
                        ]);

                        if ($wasInTransit && $order->warehouse_id) {
                            $stock = Stock::where('product_id', $item->product_id)
                                ->where('warehouse_id', $order->warehouse_id)
                                ->lockForUpdate()
                                ->first();

                            if ($stock) {
                                $stock->dispatched_qty = max(0.0, (float) $stock->dispatched_qty - (float) $item->quantity);
                                $stock->save();
                            }
                        }
                    }

                    $order->update([
                        'status' => 'return_requested',
                        'updated_by' => auth()->id(),
                    ]);

                    $order->statusLogs()->create([
                        'status' => 'return_requested',
                        'notes' => 'Bulk Return initiated. Reason: '.$validated['reason'],
                        'changed_by' => auth()->id(),
                    ]);

                    $count++;
                } catch (\Exception $e) {
                    $errors[] = "Order #{$order->order_no}: ".$e->getMessage();
                    $skipped++;
                }
            }
        });

        $msg = "Bulk return initiated. Success: {$count}, Skipped: {$skipped}.";
        if (! empty($errors)) {
            $msg .= ' Errors: '.implode(' ', array_slice($errors, 0, 3));
        }

        return response()->json($msg);
    }

    public function show(OrderReturn $return)
    {
        $return->load(['order.party', 'order.invoice', 'order.payments', 'items.product', 'refunds', 'creditNote']);

        return response()->json(['return' => $return]);
    }

    public function approve(OrderReturn $return)
    {
        abort_unless(auth()->user()->can('orders.return'), 403);

        if ($return->status !== 'pending') {
            return response()->json(['message' => 'Only pending returns can be approved.'], 422);
        }

        $return->update(['status' => 'approved']);

        return response()->json(['success' => true, 'message' => 'Return approved successfully.']);
    }

    public function cancel(OrderReturn $return)
    {
        abort_unless(auth()->user()->can('orders.return'), 403);

        if ($return->status !== 'pending') {
            return response()->json(['message' => 'Only pending returns can be cancelled.'], 422);
        }

        \DB::transaction(function () use ($return) {
            $order = $return->order;
            
            // Find the previous status from status logs
            $logs = $order->statusLogs()->orderBy('id', 'desc')->get();
            $prevLog = $logs->firstWhere('status', '!=', 'return_requested');
            $targetStatus = $prevLog ? $prevLog->status : 'delivered'; // Fallback to delivered

            $wasInTransit = in_array($targetStatus, \App\Modules\Orders\Models\Order::inTransitStatuses(), true);
            
            if ($wasInTransit && $order->warehouse_id) {
                foreach ($return->items as $item) {
                    $stock = \App\Modules\Inventory\Models\Stock::where('product_id', $item->product_id)
                        ->where('warehouse_id', $order->warehouse_id)
                        ->lockForUpdate()
                        ->first();

                    if ($stock) {
                        $stock->dispatched_qty = (float) $stock->dispatched_qty + (float) $item->requested_qty;
                        $stock->save();
                    }
                }
            }
            $return->items()->delete();
            $return->delete();

            $order->update([
                'status' => $targetStatus,
                'updated_by' => auth()->id(),
            ]);
            
            $order->statusLogs()->create([
                'status' => $targetStatus,
                'notes' => 'Return request cancelled. Reverted to previous status.',
                'changed_by' => auth()->id(),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Return cancelled successfully.']);
    }

    
    public function importBulkQc(Request $request, InventoryService $inventoryService)
    {
        $request->validate([
            'file' => 'required|max:10240',
            'is_preview' => 'nullable|boolean',
        ]);

        $isPreview = $request->input('is_preview', false);
        $file = $request->file('file');

        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return response()->json(['error' => 'Unable to read uploaded file.'], 400);
        }

        $firstRow = fgetcsv($handle);
        if (isset($firstRow[0])) { $firstRow[0] = preg_replace('/^﻿/', '', $firstRow[0]); }

        if ($firstRow === false) {
            fclose($handle);
            return response()->json(['error' => 'CSV file is empty.'], 400);
        }

        $headers = array_map('trim', array_map('strtolower', $firstRow));
        $required = ['return_no', 'sku', 'received_qty', 'restocked_qty', 'damaged_qty'];
        foreach ($required as $req) {
            if (!in_array($req, $headers, true)) {
                fclose($handle);
                return response()->json(['error' => "Missing required column: {$req}"], 400);
            }
        }

        $idxReturnNo = array_search('return_no', $headers, true);
        $idxSku = array_search('sku', $headers, true);
        $idxReceived = array_search('received_qty', $headers, true);
        $idxRestocked = array_search('restocked_qty', $headers, true);
        $idxDamaged = array_search('damaged_qty', $headers, true);
        $idxNotes = array_search('qc_notes', $headers, true);

        $rowsByReturn = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (!isset($row[$idxReturnNo]) || !isset($row[$idxSku])) continue;

            $returnNo = trim($row[$idxReturnNo]);
            $sku = trim($row[$idxSku]);
            if ($returnNo === '' || $sku === '') continue;

            $received = $idxReceived !== false ? (float)($row[$idxReceived] ?? 0) : 0;
            $restocked = $idxRestocked !== false ? (float)($row[$idxRestocked] ?? 0) : 0;
            $damaged = $idxDamaged !== false ? (float)($row[$idxDamaged] ?? 0) : 0;
            $notes = $idxNotes !== false ? trim($row[$idxNotes] ?? '') : '';

            $rowsByReturn[$returnNo][] = [
                'sku' => $sku,
                'received_qty' => $received,
                'restocked_qty' => $restocked,
                'damaged_qty' => $damaged,
                'qc_notes' => $notes,
            ];
        }
        fclose($handle);

        $previewData = [];
        $updated = 0;
        $skipped = [];

        try {
            DB::beginTransaction();

            foreach ($rowsByReturn as $returnNo => $itemsData) {
                $return = OrderReturn::with(['items.product'])->where('return_no', $returnNo)->first();

                $isValidReturn = $return && in_array($return->status, ['approved', 'received', 'qc_in_progress'], true);
                $returnError = null;
                if (!$return) $returnError = 'Return not found';
                elseif (!$isValidReturn) $returnError = 'Invalid status: ' . $return->status;

                $returnItemsPayload = [];
                $allPassed = true;
                
                foreach ($itemsData as $csvItem) {
                    $itemError = $returnError;
                    $matchedItem = null;

                    if ($return) {
                        $matchedItem = $return->items->first(function($i) use ($csvItem) {
                            return $i->product && $i->product->sku === $csvItem['sku'];
                        });

                        if (!$matchedItem) {
                            $itemError = "SKU not found in return request";
                            $allPassed = false;
                        } else {
                            $totalProcessed = $csvItem['restocked_qty'] + $csvItem['damaged_qty'];
                            if ($totalProcessed > $csvItem['received_qty']) {
                                $itemError = "Restocked + Damaged cannot exceed Received";
                                $allPassed = false;
                            } else {
                                $returnItemsPayload[] = [
                                    'id' => $matchedItem->id,
                                    'received_qty' => $csvItem['received_qty'],
                                    'restocked_qty' => $csvItem['restocked_qty'],
                                    'damaged_qty' => $csvItem['damaged_qty'],
                                    'qc_notes' => $csvItem['qc_notes'],
                                ];
                            }
                        }
                    } else {
                        $allPassed = false;
                    }

                    if ($isPreview) {
                        $previewData[] = [
                            'return_no' => $returnNo,
                            'sku' => $csvItem['sku'],
                            'requested_qty' => $matchedItem ? $matchedItem->requested_qty : 0,
                            'received_qty' => $csvItem['received_qty'],
                            'restocked_qty' => $csvItem['restocked_qty'],
                            'damaged_qty' => $csvItem['damaged_qty'],
                            'error' => $itemError,
                            'is_valid' => empty($itemError)
                        ];
                    }
                } // End foreach CSV item

                if (!$isPreview && $allPassed && $return) {
                    // Simulate Request and invoke processQc core logic
                    $simulatedRequest = new Request();
                    $simulatedRequest->merge(['items' => $returnItemsPayload]);
                    
                    try {
                        $qcResponse = $this->processQc($simulatedRequest, $return, $inventoryService);
                        if ($qcResponse->getStatusCode() === 200) {
                            $updated++;
                        } else {
                            $skipped[] = $returnNo . ' (' . (json_decode($qcResponse->getContent())->message ?? 'Validation failed') . ')';
                        }
                    } catch (\Illuminate\Validation\ValidationException $e) {
                        $skipped[] = $returnNo . ' (Validation Error: ' . collect($e->errors())->flatten()->first() . ')';
                    } catch (\Exception $e) {
                        $skipped[] = $returnNo . ' (Error: ' . $e->getMessage() . ')';
                    }
                }
            } // End foreach Return

            if ($isPreview) {
                DB::rollBack();
                return response()->json(['preview' => $previewData]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error processing CSV: '.$e->getMessage()], 400);
        }

        $message = "Bulk QC completed successfully. Updated {$updated} return(s).";
        if (count($skipped) > 0) {
            $message .= "

Skipped " . count($skipped) . " return(s):
- " . implode("
- ", array_slice($skipped, 0, 5)) . (count($skipped) > 5 ? "
...and more" : "");
        }
        
        return response()->json(['success' => true, 'message' => $message]);
    }

    public function processQc(Request $request, OrderReturn $return, InventoryService $inventoryService)
    {
        // Guard: only allow QC on returns that have not already been completed or rejected
        if (! in_array($return->status, ['approved', 'received', 'qc_in_progress'])) {
            return response()->json(['message' => 'Cannot process QC on a return that is not approved, received, or already in QC.'], 422);
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:order_return_items,id',
            'items.*.received_qty' => 'required|numeric|min:0',
            'items.*.restocked_qty' => 'required|numeric|min:0',
            'items.*.damaged_qty' => 'required|numeric|min:0',
            'items.*.qc_notes' => 'nullable|string',
        ]);

        // Validate that restocked + damaged does not exceed received for each item
        foreach ($validated['items'] as $index => $itemData) {
            $totalProcessed = $itemData['restocked_qty'] + $itemData['damaged_qty'];
            if ($totalProcessed > $itemData['received_qty']) {
                return response()->json([
                    'message' => "Item #{$index}: restocked_qty + damaged_qty cannot exceed received_qty.",
                ], 422);
            }
        }

        DB::transaction(function () use ($validated, $return, $inventoryService) {
            $refundAmount = 0;

            $returnItemIds = collect($validated['items'])->pluck('id');
            $returnItems = OrderReturnItem::where('order_return_id', $return->id)
                ->whereIn('id', $returnItemIds)
                ->get()
                ->keyBy('id');

            $orderItems = OrderItem::where('order_id', $return->order_id)
                ->get()
                ->keyBy('product_id');

            foreach ($validated['items'] as $itemData) {
                $item = $returnItems->get($itemData['id']);
                if (! $item) {
                    continue;
                }

                $item->update([
                    'received_qty' => $itemData['received_qty'],
                    'restocked_qty' => $itemData['restocked_qty'],
                    'damaged_qty' => $itemData['damaged_qty'],
                    'qc_notes' => $itemData['qc_notes'],
                    'qc_status' => 'passed', // simplistically assuming it passed QC if we recorded quantities
                ]);

                // Calculate proportional refund amount based on received qty
                $orderItem = $orderItems->get($item->product_id);
                if ($orderItem && $orderItem->quantity > 0) {
                    $refundAmount += ((float) $orderItem->total_amount / (float) $orderItem->quantity) * (float) $itemData['received_qty'];
                }

                // Inventory Update
                if ($return->order->warehouse_id) {
                    $inventoryService->processReturnItem(
                        $item->product_id,
                        $return->order->warehouse_id,
                        (float) $itemData['restocked_qty'],
                        (float) $itemData['damaged_qty'],
                        $return->id
                    );
                }
            }

            $return->update(['status' => 'completed']);
            $return->load('order.invoice');

            $remainingRefund = $refundAmount;

            if ($remainingRefund > 0 && $return->order->invoice && $return->order->invoice->paid_amount > 0) {
                $alreadyRefundedCash = Refund::where('order_id', $return->order_id)->sum('amount');
                $availableCash = max(0, $return->order->invoice->paid_amount - $alreadyRefundedCash);
                $refundable = min($remainingRefund, $availableCash);

                if ($refundable > 0) {
                    $baseNo = str_replace('ORD-', 'REF-', $return->order->order_no);
                    if ($baseNo === $return->order->order_no) {
                        $baseNo = 'REF-'.$return->order->order_no;
                    }
                    $count = Refund::where('order_id', $return->order_id)->count();
                    $refundNo = $count > 0 ? $baseNo.'-'.($count + 1) : $baseNo;

                    Refund::create([
                        'refund_no' => $refundNo,
                        'order_id' => $return->order_id,
                        'invoice_id' => $return->order->invoice->id,
                        'order_return_id' => $return->id,
                        'amount' => $refundable,
                        'status' => 'pending',
                    ]);

                    $remainingRefund -= $refundable;
                }
            }

            if ($remainingRefund > 0 && $return->order->wallet_amount_used > 0 && $return->order->party_id) {
                $walletRefundable = min($remainingRefund, (float) $return->order->wallet_amount_used);
                
                if ($walletRefundable > 0) {
                    $party = \App\Modules\Customers\Models\Party::find($return->order->party_id);
                    if ($party) {
                        $balanceBefore = (float) $party->wallet_balance;
                        $balanceAfter = $balanceBefore + $walletRefundable;
                        $party->increment('wallet_balance', $walletRefundable);

                        \App\Modules\Customers\Models\WalletTransaction::create([
                            'party_id' => $party->id,
                            'amount' => $walletRefundable,
                            'type' => 'credit',
                            'reference_type' => 'order_return',
                            'reference_id' => $return->order->id,
                            'description' => 'Wallet balance refunded due to order #'.$return->order->order_no.' return',
                            'created_by' => auth()->id() ?? $return->order->created_by,
                            'balance_before' => $balanceBefore,
                            'balance_after' => $balanceAfter,
                        ]);

                        $return->order->wallet_amount_used -= $walletRefundable;
                        $return->order->saveQuietly();
                    }
                }
            }

            // Update main order status to returned and log it
            $return->order->update([
                'status' => 'returned',
                'updated_by' => auth()->id(),
            ]);

            $return->order->statusLogs()->create([
                'status' => 'returned',
                'notes' => 'Return QC completed successfully.',
                'changed_by' => auth()->id(),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Quality Check processed successfully.']);
    }

    public function processFinancials(Request $request, OrderReturn $return, FinancialService $financialService)
    {
        $validated = $request->validate([
            'action' => 'required|in:refund,credit_note',
            'amount' => 'required|numeric|gt:0',
            'payment_method' => 'nullable|string|required_if:action,refund',
            'transaction_id' => 'nullable|string',
        ]);

        try {
            if ($validated['action'] === 'refund') {
                $financialService->processRefund(
                    $return,
                    (float) $validated['amount'],
                    $validated['payment_method'],
                    $validated['transaction_id'] ?? null
                );
                $message = 'Refund processed successfully.';
            } else {
                $financialService->issueCreditNote($return, (float) $validated['amount']);
                $message = 'Credit Note issued successfully.';
            }

            return response()->json(['success' => true, 'message' => $message]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }
}
