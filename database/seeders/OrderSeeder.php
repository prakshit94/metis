<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Party;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\User;
use App\Models\Coupon;
use App\Models\Offer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\OrderVerificationLog;
use App\Models\ShipmentTrackingEvent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Coupons
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
                'code' => 'SAVE50',
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
        $parties = Party::all();
        $products = Product::all();
        $warehouses = Warehouse::all();
        $user = User::first() ?? User::factory()->create();

        if ($parties->isEmpty() || $products->isEmpty() || $warehouses->isEmpty()) {
            return;
        }

        // Create a test BOGO offer for the first product
        $firstProduct = $products->first();
        Offer::firstOrCreate(
            ['name' => 'BOGO First Product'],
            [
                'type' => 'bogo',
                'discount_type' => null,
                'value' => 0.00,
                'min_spend' => 0.00,
                'product_id' => $firstProduct->id,
                'buy_qty' => 1,
                'get_qty' => 1,
                'starts_at' => Carbon::now()->subDays(5),
                'ends_at' => Carbon::now()->addMonths(2),
                'priority' => 1,
                'is_active' => true,
            ]
        );

        $statuses = [
            'pending',
            'confirmed',
            'processing',
            'ready_to_ship',
            'dispatched',
            'shipped',
            'delivered',
            'cancelled',
            'returned',
        ];

        $carriers = ['FedEx', 'DHL', 'UPS', 'BlueDart', 'Delhivery'];

        foreach ($statuses as $index => $status) {
            // Seed 2 orders for each status
            for ($i = 1; $i <= 2; $i++) {
                $party = $parties->random();
                $warehouse = $warehouses->random();
                $address = DB::table('party_addresses')->where('party_id', $party->id)->first();
                $orderDate = Carbon::now()->subDays(20 - $index * 2)->subHours(rand(1, 12));

                $orderNo = 'ORD-' . strtoupper(Str::random(8));

                $order = Order::create([
                    'order_no' => $orderNo,
                    'type' => 'sale',
                    'party_id' => $party->id,
                    'order_date' => $orderDate,
                    'status' => $status,
                    'is_draft' => false,
                    'warehouse_id' => $warehouse->id,
                    'shipping_address_id' => $address?->id,
                    'billing_address_id' => $address?->id,
                    'shipping_address' => $address ? "{$address->address_line_1}, {$address->city}, {$address->state} - {$address->pincode}" : 'Test Shipping Address',
                    'billing_address' => $address ? "{$address->address_line_1}, {$address->city}, {$address->state} - {$address->pincode}" : 'Test Billing Address',
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                    'coupon_code' => rand(0, 1) ? 'WELCOME10' : null,
                ]);

                // Create Order Items
                $itemCount = rand(1, 3);
                $selectedProducts = $products->random(min($itemCount, $products->count()));
                $totalAmount = 0;
                $taxAmount = 0;
                $discountAmount = 0;

                foreach ($selectedProducts as $prod) {
                    $qty = (float) rand(1, 5);
                    $price = (float) $prod->selling_price;
                    $taxRate = 18.00; // 18% GST
                    $itemTax = ($price * $qty) * ($taxRate / 100);
                    $itemDiscount = $order->coupon_code ? ($price * $qty) * 0.10 : 0;
                    $itemTotal = ($price * $qty) + $itemTax - $itemDiscount;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $prod->id,
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'tax_rate' => $taxRate,
                        'tax_amount' => $itemTax,
                        'discount_amount' => $itemDiscount,
                        'total_amount' => $itemTotal,
                    ]);

                    $totalAmount += ($price * $qty);
                    $taxAmount += $itemTax;
                    $discountAmount += $itemDiscount;
                }

                $netAmount = $totalAmount + $taxAmount - $discountAmount;

                $order->update([
                    'total_amount' => $totalAmount,
                    'tax_amount' => $taxAmount,
                    'discount_amount' => $discountAmount,
                    'net_amount' => $netAmount,
                ]);

                // 3. Create Invoices (for confirmed and later orders)
                if (in_array($status, ['confirmed', 'processing', 'ready_to_ship', 'dispatched', 'shipped', 'delivered'])) {
                    $invoiceStatus = ($status === 'delivered') ? 'paid' : (rand(0, 1) ? 'unpaid' : 'partially_paid');
                    $invoice = Invoice::create([
                        'invoice_no' => 'INV-' . strtoupper(Str::random(8)),
                        'order_id' => $order->id,
                        'invoice_date' => $orderDate->copy()->addMinutes(30),
                        'total_amount' => $totalAmount,
                        'tax_amount' => $taxAmount,
                        'net_amount' => $netAmount,
                        'status' => $invoiceStatus,
                    ]);

                    // 4. Create Payments
                    if ($invoiceStatus === 'paid' || $invoiceStatus === 'partially_paid') {
                        Payment::create([
                            'payment_no' => 'PAY-' . strtoupper(Str::random(8)),
                            'invoice_id' => $invoice->id,
                            'order_id' => $order->id,
                            'amount' => $invoiceStatus === 'paid' ? $netAmount : ($netAmount / 2),
                            'payment_method' => 'bank_transfer',
                            'transaction_id' => 'TXN' . rand(10000000, 99999999),
                            'payment_date' => $orderDate->copy()->addHours(1),
                            'status' => 'completed',
                        ]);
                    }
                }

                // 5. Create Shipments (for ready_to_ship and later orders)
                if (in_array($status, ['ready_to_ship', 'dispatched', 'shipped', 'delivered'])) {
                    $shipmentStatus = match ($status) {
                        'ready_to_ship' => 'pending',
                        'dispatched', 'shipped' => 'in_transit',
                        'delivered' => 'delivered',
                        default => 'pending',
                    };

                    $shipment = Shipment::create([
                        'shipment_no' => 'SHP-' . strtoupper(Str::random(8)),
                        'order_id' => $order->id,
                        'carrier_name' => $carriers[rand(0, count($carriers) - 1)],
                        'tracking_no' => 'TRK' . rand(100000000, 999999999),
                        'status' => $shipmentStatus,
                        'shipped_at' => $orderDate->copy()->addDays(1),
                        'delivered_at' => $status === 'delivered' ? $orderDate->copy()->addDays(3) : null,
                    ]);

                    // 6. Create Tracking Events
                    if (in_array($status, ['dispatched', 'shipped', 'delivered'])) {
                        ShipmentTrackingEvent::create([
                            'shipment_id' => $shipment->id,
                            'event_name' => 'Manifest Created',
                            'location' => 'Main Hub',
                            'description' => 'Shipment information sent to carrier.',
                            'occurred_at' => $orderDate->copy()->addDays(1)->addMinutes(10),
                        ]);

                        if ($status === 'delivered') {
                            ShipmentTrackingEvent::create([
                                'shipment_id' => $shipment->id,
                                'event_name' => 'Delivered',
                                'location' => $party->city,
                                'description' => 'Parcel delivered to customer.',
                                'occurred_at' => $orderDate->copy()->addDays(3),
                            ]);
                        }
                    }
                }

                // 7. Create Verification Logs
                if (rand(0, 1)) {
                    OrderVerificationLog::create([
                        'order_id' => $order->id,
                        'outcome' => 'customer_confirmed',
                        'remark' => 'Customer confirmed delivery address and quantities.',
                        'follow_up_at' => null,
                        'created_by' => $user->id,
                    ]);
                }
            }
        }
    }
}
