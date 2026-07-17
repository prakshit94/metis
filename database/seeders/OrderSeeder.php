<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Customers\Models\Party;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Warehouse;
use App\Modules\Users\Models\User;
use App\Modules\Orders\Models\Coupon;
use App\Modules\Orders\Models\Offer;
use App\Modules\Orders\Models\Invoice;
use App\Modules\Orders\Models\Payment;
use App\Modules\Orders\Models\Shipment;
use App\Modules\Orders\Models\OrderVerificationLog;
use App\Modules\Orders\Models\ShipmentTrackingEvent;
use App\Modules\Orders\Models\OrderReturn;
use App\Modules\Orders\Models\OrderReturnItem;
use App\Modules\Orders\Models\Refund;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Setup Active Coupons
        $coupons = [
            [
                'code' => 'WELCOME10',
                'type' => 'percentage',
                'value' => 10.00,
                'min_spend' => 100.00,
                'expiry_date' => Carbon::now()->addMonths(6),
                'usage_limit' => 500,
                'status' => 'active',
                'is_active' => true,
            ],
            [
                'code' => 'AGRISAVE50',
                'type' => 'fixed',
                'value' => 50.00,
                'min_spend' => 200.00,
                'expiry_date' => Carbon::now()->addMonths(3),
                'usage_limit' => 100,
                'status' => 'active',
                'is_active' => true,
            ]
        ];

        foreach ($coupons as $coupon) {
            Coupon::firstOrCreate(['code' => $coupon['code']], $coupon);
        }

        // 2. Fetch dependencies
        $parties = Party::where('type', 'customer')->get();
        $products = Product::with('taxRate')->get();
        $warehouses = Warehouse::all();
        $user = User::first() ?? User::factory()->create();

        if ($parties->isEmpty() || $products->isEmpty() || $warehouses->isEmpty()) {
            return;
        }

        // Create standard operational workflow statuses
        $statuses = ['pending', 'confirmed', 'processing', 'ready_to_ship', 'dispatched', 'shipped', 'delivered', 'cancelled', 'returned'];
        $carriers = ['FedEx', 'DHL', 'UPS', 'BlueDart', 'Delhivery'];

        $orderCounter = Order::max('id') ?? 0;
        $shipmentCounter = Shipment::max('id') ?? 0;
        $year = now()->format('Y');

        foreach ($statuses as $index => $status) {
            for ($i = 1; $i <= 2; $i++) {
                $orderCounter++;
                $party = $parties->random();
                $warehouse = $warehouses->random();
                $address = DB::table('party_addresses')->where('party_id', $party->id)->first();
                $orderDate = Carbon::now()->subDays(20 - $index * 2)->subHours(rand(1, 12));
                $couponCode = rand(0, 1) ? 'WELCOME10' : null;

                $order = Order::create([
                    'order_no' => 'ORD-' . $year . '-' . str_pad((string) $orderCounter, 5, '0', STR_PAD_LEFT),
                    'type' => 'sale',
                    'party_id' => $party->id,
                    'order_date' => $orderDate,
                    'status' => $status,
                    'is_draft' => false,
                    'warehouse_id' => $warehouse->id,
                    'shipping_address_id' => $address?->id,
                    'billing_address_id' => $address?->id,
                    'shipping_address_line_1' => $address?->address_line_1 ?? 'Default Shipping Address',
                    'shipping_city' => $address?->city,
                    'shipping_state' => $address?->state,
                    'shipping_pincode' => $address?->pincode,
                    'billing_address_line_1' => $address?->address_line_1 ?? 'Default Billing Address',
                    'billing_city' => $address?->city,
                    'billing_state' => $address?->state,
                    'billing_pincode' => $address?->pincode,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                    'coupon_code' => $couponCode,
                ]);

                $itemCount = rand(1, 3);
                $selectedProducts = $products->random(min($itemCount, $products->count()));
                
                $totalAmount = 0.00;
                $taxAmount = 0.00;
                $discountAmount = 0.00;

                foreach ($selectedProducts as $prod) {
                    $qty = (float) rand(1, 4);
                    $price = (float) $prod->selling_price;
                    
                    // Dynamic Tax Assignment extraction 
                    $taxRatePercentage = $prod->taxRate ? (float) $prod->taxRate->rate : 18.00;
                    
                    $subTotal = $price * $qty;
                    $itemDiscount = $couponCode ? round($subTotal * 0.10, 2) : 0.00;
                    $taxableAmount = $subTotal - $itemDiscount;
                    $itemTax = round($taxableAmount * ($taxRatePercentage / 100), 2);
                    $itemTotal = $taxableAmount + $itemTax;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $prod->id,
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'tax_rate' => $taxRatePercentage,
                        'tax_amount' => $itemTax,
                        'discount_amount' => $itemDiscount,
                        'total_amount' => $itemTotal,
                    ]);

                    $totalAmount += $subTotal;
                    $taxAmount += $itemTax;
                    $discountAmount += $itemDiscount;
                }

                $netAmount = ($totalAmount - $discountAmount) + $taxAmount;

                $order->update([
                    'total_amount' => $totalAmount,
                    'tax_amount' => $taxAmount,
                    'discount_amount' => $discountAmount,
                    'net_amount' => $netAmount,
                ]);

                // 3. Invoice Execution
                if (in_array($status, ['confirmed', 'processing', 'ready_to_ship', 'dispatched', 'shipped', 'delivered', 'returned'])) {
                    $invoiceStatus = ($status === 'delivered' || $status === 'returned') ? 'paid' : (rand(0, 1) ? 'unpaid' : 'partially_paid');
                    
                    $invoice = Invoice::create([
                        'invoice_no' => 'INV-' . strtoupper(Str::random(8)),
                        'order_id' => $order->id,
                        'invoice_date' => $orderDate->copy()->addMinutes(30),
                        'total_amount' => $totalAmount,
                        'tax_amount' => $taxAmount,
                        'net_amount' => $netAmount,
                        'status' => $invoiceStatus,
                    ]);

                    // 4. Record Safe Ledger Transaction Payments
                    if ($invoiceStatus === 'paid' || $invoiceStatus === 'partially_paid') {
                        Payment::create([
                            'payment_no' => 'PAY-' . strtoupper(Str::random(8)),
                            'invoice_id' => $invoice->id,
                            'order_id' => $order->id,
                            'amount' => $invoiceStatus === 'paid' ? $netAmount : round($netAmount / 2, 2),
                            'payment_method' => 'bank_transfer',
                            'transaction_id' => 'TXN' . rand(10000000, 99999999),
                            'payment_date' => $orderDate->copy()->addHours(1),
                            'status' => 'captured',
                        ]);
                    }

                    // Seeding Returns and Refunds
                    if ($status === 'returned') {
                        $return = OrderReturn::create([
                            'order_id' => $order->id,
                            'return_no' => 'RET-' . strtoupper(Str::random(8)),
                            'status' => 'completed',
                            'financial_status' => 'fully_refunded',
                            'reason' => 'Defective Item',
                            'notes' => 'Customer requested a refund.',
                            'refund_amount' => $netAmount,
                            'credit_note_amount' => 0.00,
                        ]);

                        foreach ($order->items as $item) {
                            OrderReturnItem::create([
                                'order_return_id' => $return->id,
                                'product_id' => $item->product_id,
                                'requested_qty' => $item->quantity,
                                'received_qty' => $item->quantity,
                                'restocked_qty' => $item->quantity,
                                'damaged_qty' => 0,
                                'qc_status' => 'passed',
                                'qc_notes' => 'Passed inspection.',
                            ]);
                        }

                        Refund::create([
                            'refund_no' => 'REF-' . strtoupper(Str::random(8)),
                            'order_id' => $order->id,
                            'invoice_id' => $invoice->id,
                            'order_return_id' => $return->id,
                            'amount' => $netAmount,
                            'payment_method' => 'bank_transfer',
                            'transaction_id' => 'TXN' . rand(10000000, 99999999),
                            'status' => 'completed',
                            'notes' => 'Processed refund back to customer account.',
                        ]);
                    }
                }

                // 5. Build Logistics & Shipping Events
                if (in_array($status, ['ready_to_ship', 'dispatched', 'shipped', 'delivered'])) {
                    $shipmentStatus = match ($status) {
                        'ready_to_ship' => 'pending',
                        'dispatched', 'shipped' => 'in_transit',
                        'delivered' => 'delivered',
                        default => 'pending',
                    };

                    $shipmentCounter++;
                    $shipment = Shipment::create([
                        'shipment_no' => 'SHP-' . $year . '-' . str_pad((string) $shipmentCounter, 5, '0', STR_PAD_LEFT),
                        'order_id' => $order->id,
                        'carrier_name' => $carriers[rand(0, count($carriers) - 1)],
                        'tracking_no' => 'TRK' . rand(100000000, 999999999),
                        'status' => $shipmentStatus,
                        'shipped_at' => $orderDate->copy()->addDays(1),
                        'delivered_at' => $status === 'delivered' ? $orderDate->copy()->addDays(3) : null,
                    ]);

                    // Tracking events
                    if (in_array($status, ['dispatched', 'shipped', 'delivered'])) {
                        ShipmentTrackingEvent::create([
                            'shipment_id' => $shipment->id,
                            'event_name' => 'Manifest Created',
                            'location' => 'Main Logistics Hub',
                            'description' => 'Shipment packaging processed and routing assigned.',
                            'occurred_at' => $orderDate->copy()->addDays(1)->addMinutes(15),
                        ]);

                        if ($status === 'delivered') {
                            ShipmentTrackingEvent::create([
                                'shipment_id' => $shipment->id,
                                'event_name' => 'Delivered',
                                'location' => $party->city,
                                'description' => 'Consignment handed over to customer.',
                                'occurred_at' => $orderDate->copy()->addDays(3),
                            ]);
                        }
                    }
                }

                // 6. Append Logs
                if (rand(0, 1)) {
                    OrderVerificationLog::create([
                        'order_id' => $order->id,
                        'outcome' => 'customer_confirmed',
                        'remark' => 'Verification checkpoint cleared via phone call.',
                        'follow_up_at' => null,
                        'created_by' => $user->id,
                    ]);
                }
            }
        }
    }
}