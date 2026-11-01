<?php

namespace App\Imports;

use App\Modules\Customers\Models\Party;
use App\Modules\Customers\Models\PartyAddress;
use App\Modules\Core\Models\Village;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Models\Order;
use App\Modules\Catalog\Models\Warehouse;
use App\Services\OrderService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;

class OrdersImport implements ToCollection, WithHeadingRow
{
    public $duplicates = [];
    public $importedCount = 0;

    public function collection(Collection $rows): void
    {
        // Allow using either the new 'Order No' header or the old 'order_reference' header.
        $rows = $rows->map(function ($row) {
            if (!isset($row['order_no']) && isset($row['order_reference'])) {
                $row['order_no'] = $row['order_reference'];
            }
            return $row;
        });

        $grouped = $rows->groupBy('order_no');
        $orderService = app(OrderService::class);

        DB::beginTransaction();
        try {
            foreach ($grouped as $ref => $orderRows) {
                if (empty($ref) || trim($ref) === '-') continue;
                
                if (Order::where('order_no', $ref)->exists()) {
                    $this->duplicates[] = $ref;
                    continue;
                }
                
                $firstRow = $orderRows->first();
                
                $phone = $firstRow['customer_phone'] ?? null;
                $altMobile = $firstRow['alternate_mobile'] ?? null;

                if (trim($phone) === '-') $phone = null;
                if (trim($altMobile) === '-') $altMobile = null;

                if (!$phone && !$altMobile) {
                    continue; // skip if no phone provided
                }

                $query = Party::where('type', 'customer')->where(function($q) use ($phone, $altMobile) {
                    if ($phone) {
                        $q->orWhere('phone', $phone)->orWhere('alternatemobile', $phone);
                    }
                    if ($altMobile) {
                        $q->orWhere('phone', $altMobile)->orWhere('alternatemobile', $altMobile);
                    }
                });
                
                $customer = $query->first();

                $getVal = function($key, $fallback) use ($firstRow) {
                    $val = trim((string)($firstRow[$key] ?? ''));
                    if ($val === '' || $val === '-') return $fallback;
                    return $val;
                };

                $partyData = [
                    'firstname' => $getVal('customer_first_name', $customer->firstname ?? 'Unknown'),
                    'middlename' => $getVal('customer_middle_name', $customer->middlename ?? null),
                    'lastname' => $getVal('customer_last_name', $customer->lastname ?? 'Unknown'),
                    'company_name' => $getVal('company_name', $customer->company_name ?? null),
                    'email' => $getVal('customer_email', $customer->email ?? null),
                    'phone' => $phone ?? ($customer->phone ?? null),
                    'alternatemobile' => $altMobile ?? ($customer->alternatemobile ?? null),
                    'relative_name' => $getVal('relative_name', $customer->relative_name ?? null),
                    'relative_phone' => $getVal('relative_phone', $customer->relative_phone ?? null),
                    'gst_no' => $getVal('gst_number', $customer->gst_no ?? null),
                    'pan_no' => $getVal('pan_number', $customer->pan_no ?? null),
                    'is_active' => true,
                    'type' => 'customer',
                ];

                if ($customer) {
                    $customer->update($partyData);
                } else {
                    $customer = Party::create($partyData);
                }

                // 2. Resolve Address
                $villageName = trim((string)($firstRow['shipping_village'] ?? $firstRow['shipping_village_name'] ?? ''));
                if ($villageName === '' || $villageName === '-') $villageName = null;

                $village = Village::where('village_name', $villageName)->first();
                if (!$village) {
                    throw new \Exception("Village '$villageName' not found for order $ref.");
                }

                $shippingAdd1 = trim((string)($firstRow['shipping_address_1'] ?? $firstRow['shipping_address_line_1'] ?? ''));
                if ($shippingAdd1 === '' || $shippingAdd1 === '-') $shippingAdd1 = 'Default Address';

                $getVal = function($keys, $default = null) use ($firstRow) {
                    foreach ((array)$keys as $k) {
                        if (isset($firstRow[$k])) {
                            $v = trim((string)$firstRow[$k]);
                            if ($v !== '' && $v !== '-') return $v;
                        }
                    }
                    return $default;
                };

                $shippingAdd2 = $getVal(['shipping_address_2', 'shipping_address_line_2']);
                $shippingPO = $getVal(['shipping_pobo', 'shipping_post_office']);
                $shippingTaluka = $getVal(['shipping_taluka']);
                $shippingCity = $getVal(['shipping_city']);
                $shippingPincode = $getVal(['shipping_pincode']);

                $shippingAddress = PartyAddress::firstOrCreate(
                    [
                        'party_id' => $customer->id,
                        'village_id' => $village->id,
                        'address_line_1' => $shippingAdd1,
                    ],
                    [
                        'label' => 'Imported Shipping',
                        'address_line_2' => $shippingAdd2,
                        'village_name' => $village->village_name,
                        'post_office' => $shippingPO,
                        'taluka' => $shippingTaluka,
                        'district' => $village->district_name,
                        'city' => $shippingCity,
                        'state' => $village->state_name,
                        'pincode' => $shippingPincode,
                    ]
                );

                $billingAdd1 = $getVal(['billing_address_1', 'billing_address_line_1']);
                $billingAdd2 = $getVal(['billing_address_2', 'billing_address_line_2']);
                $billingPO = $getVal(['billing_pobo', 'billing_post_office']);
                $billingTaluka = $getVal(['billing_taluka']);
                $billingCity = $getVal(['billing_city']);
                $billingPincode = $getVal(['billing_pincode']);
                
                $billingAddress = $shippingAddress;
                if ($billingAdd1) {
                    // Try to resolve billing village if it differs, or use shipping village
                    $billingVillageName = $getVal(['billing_village', 'billing_village_name'], $village->village_name);
                    $billingVillage = Village::where('village_name', $billingVillageName)->first() ?? $village;
                    
                    $billingAddress = PartyAddress::firstOrCreate(
                        [
                            'party_id' => $customer->id,
                            'village_id' => $billingVillage->id,
                            'address_line_1' => $billingAdd1,
                        ],
                        [
                            'label' => 'Imported Billing',
                            'address_line_2' => $billingAdd2,
                            'village_name' => $billingVillage->village_name,
                            'post_office' => $billingPO,
                            'taluka' => $billingTaluka,
                            'district' => $billingVillage->district_name,
                            'city' => $billingCity,
                            'state' => $billingVillage->state_name,
                            'pincode' => $billingPincode,
                        ]
                    );
                }

                $warehouseName = trim((string)($firstRow['warehouse_name'] ?? ''));
                if ($warehouseName === '' || $warehouseName === '-') $warehouseName = null;
                
                $warehouse = null;
                if ($warehouseName) {
                    $warehouse = Warehouse::where('name', $warehouseName)->first();
                }
                if (!$warehouse) {
                    $warehouse = Warehouse::where('state', $village->state_name)->first() ?? Warehouse::first();
                }

                $orderType = trim((string)($firstRow['order_type'] ?? ''));
                if ($orderType === '' || $orderType === '-') $orderType = 'sale';

                // 3. Collect Items
                $items = [];
                foreach ($orderRows as $row) {
                    $productSku = $row['product_sku'] ?? null;
                    if (empty($productSku) || trim($productSku) === '-') continue;

                    $product = Product::where('sku', $productSku)->orWhere('id', $productSku)->first();
                    if (!$product) {
                        throw new \Exception("Product SKU '$productSku' not found.");
                    }
                    
                    $rawQty = trim((string)($row['quantity'] ?? ''));
                    $qty = ($rawQty === '' || $rawQty === '-') ? 1 : (float)$rawQty;

                    $rawPrice = trim((string)($row['unit_price'] ?? ''));
                    $price = ($rawPrice === '' || $rawPrice === '-') 
                        ? ($orderType === 'purchase' ? (float)$product->purchase_price : (float)$product->selling_price) 
                        : (float)$rawPrice;
                    
                    $rawTaxRate = trim((string)($row['item_tax_rate'] ?? ''));
                    $taxRate = ($rawTaxRate === '' || $rawTaxRate === '-') ? ($product->tax_rate ?? 0) : (float)$rawTaxRate;

                    $rawItemTax = trim((string)($row['item_tax'] ?? ''));
                    $itemTax = ($rawItemTax === '' || $rawItemTax === '-') ? ($qty * $price * $taxRate / 100) : (float)$rawItemTax;

                    $rawItemDiscount = trim((string)($row['item_discount'] ?? ''));
                    $itemDiscount = ($rawItemDiscount === '' || $rawItemDiscount === '-') ? 0 : (float)$rawItemDiscount;

                    $rawItemNet = trim((string)($row['item_net'] ?? ''));
                    $itemNet = ($rawItemNet === '' || $rawItemNet === '-') ? (($qty * $price) - $itemDiscount) : (float)$rawItemNet;

                    $rawBatch = trim((string)($row['batch_number'] ?? ''));
                    $batchNumber = ($rawBatch === '' || $rawBatch === '-') ? null : $rawBatch;

                    $items[] = [
                        'product_id' => $product->id,
                        'batch_number' => $batchNumber,
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'tax_rate' => $taxRate,
                        'tax_amount' => $itemTax,
                        'discount_amount' => $itemDiscount,
                        'total_amount' => $itemNet,
                    ];
                }

                if (empty($items)) {
                    // Skip order if it contains no valid items
                    continue;
                }

                $orderDate = trim((string)($firstRow['order_date'] ?? ''));
                if ($orderDate === '' || $orderDate === '-') $orderDate = now()->toDateString();
                
                $status = trim((string)($firstRow['status'] ?? ''));
                if ($status === '' || $status === '-') $status = 'pending';

                $couponCode = trim((string)($firstRow['coupon_code'] ?? ''));
                if ($couponCode === '-') $couponCode = null;

                $appliedOfferId = trim((string)($firstRow['applied_offer_id'] ?? ''));
                if ($appliedOfferId === '-') $appliedOfferId = null;

                $trackingNo = trim((string)($firstRow['tracking_no'] ?? ''));
                if ($trackingNo === '-') $trackingNo = null;

                $carrierName = trim((string)($firstRow['carrier_name'] ?? ''));
                if ($carrierName === '-') $carrierName = null;

                $orderSubtotal = trim((string)($firstRow['order_subtotal'] ?? ''));
                $orderTax = trim((string)($firstRow['order_tax'] ?? ''));
                $orderDiscount = trim((string)($firstRow['order_discount'] ?? ''));
                $orderTotal = trim((string)($firstRow['order_total'] ?? ''));
                $walletUsed = trim((string)($firstRow['wallet_used'] ?? ''));
                $cashbackEarned = trim((string)($firstRow['cashback_earned'] ?? ''));

                // 4. Validate & Create Payload
                $payload = [
                    'order_no' => $ref,
                    'type' => $orderType,
                    'party_id' => $customer->id,
                    'warehouse_id' => $warehouse->id,
                    'shipping_address_id' => $shippingAddress->id,
                    'billing_address_id' => $billingAddress->id,
                    'order_date' => $orderDate,
                    'items' => $items,
                    'status' => $status,
                    'coupon_code' => $couponCode,
                    'applied_offer_id' => $appliedOfferId,
                    'tracking_no' => $trackingNo,
                    'carrier_name' => $carrierName,
                    'wallet_amount_used' => ($walletUsed !== '' && $walletUsed !== '-') ? (float)$walletUsed : 0,
                    'cashback_earned' => ($cashbackEarned !== '' && $cashbackEarned !== '-') ? (float)$cashbackEarned : 0,
                ];

                if ($orderTotal !== '' && $orderTotal !== '-') {
                    $payload['total_amount'] = (float)$orderSubtotal;
                    $payload['tax_amount'] = (float)$orderTax;
                    $payload['discount_amount'] = (float)$orderDiscount;
                    $payload['net_amount'] = (float)$orderTotal;
                } else {
                    $calc = $orderService->recalculateAndValidate($payload);
                    $payload['items'] = $calc['items'];
                    $payload['total_amount'] = $calc['subtotal'];
                    $payload['tax_amount'] = $calc['tax_amount'];
                    $payload['discount_amount'] = $calc['total_discount'];
                    $payload['net_amount'] = $calc['grand_total'];
                    $payload['coupon_code'] = $calc['coupon_code'] ?? null;
                    $payload['applied_offer_id'] = $calc['applied_offer_id'] ?? null;
                    $payload['applied_bogo_ids'] = $calc['applied_bogo_ids'] ?? [];
                }

                $orderService->createOrder($payload);
                $this->importedCount++;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}