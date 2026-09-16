<?php
$file = 'app/Imports/OrdersImport.php';
$content = <<<'CONTENT'
<?php

namespace App\Imports;

use App\Modules\Customers\Models\Party;
use App\Modules\Customers\Models\PartyAddress;
use App\Modules\Core\Models\Village;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\Warehouse;
use App\Services\OrderService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;

class OrdersImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
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

                $partyData = [
                    'firstname' => $firstRow['customer_first_name'] ?? ($customer->firstname ?? 'Unknown'),
                    'middlename' => $firstRow['customer_middle_name'] ?? ($customer->middlename ?? null),
                    'lastname' => $firstRow['customer_last_name'] ?? ($customer->lastname ?? 'Unknown'),
                    'company_name' => $firstRow['company_name'] ?? ($customer->company_name ?? null),
                    'email' => $firstRow['customer_email'] ?? ($customer->email ?? null),
                    'phone' => $phone ?? ($customer->phone ?? null),
                    'alternatemobile' => $altMobile ?? ($customer->alternatemobile ?? null),
                    'relative_name' => $firstRow['relative_name'] ?? ($customer->relative_name ?? null),
                    'relative_phone' => $firstRow['relative_phone'] ?? ($customer->relative_phone ?? null),
                    'gst_no' => $firstRow['gst_number'] ?? ($customer->gst_no ?? null),
                    'pan_no' => $firstRow['pan_number'] ?? ($customer->pan_no ?? null),
                    'is_active' => true,
                    'type' => 'customer',
                ];

                foreach ($partyData as $k => $v) {
                    if (trim($v) === '-') {
                        $partyData[$k] = null;
                    }
                }

                if ($customer) {
                    $customer->update($partyData);
                } else {
                    $customer = Party::create($partyData);
                }

                // 2. Resolve Address
                $villageName = $firstRow['shipping_village'] ?? $firstRow['shipping_village_name'] ?? null;
                if (trim($villageName) === '-') $villageName = null;

                $village = Village::where('name', $villageName)->first();
                if (!$village) {
                    throw new \Exception("Village '$villageName' not found for order $ref.");
                }

                $shippingAdd1 = $firstRow['shipping_address_1'] ?? $firstRow['shipping_address_line_1'] ?? 'Default Address';
                if (trim($shippingAdd1) === '-') $shippingAdd1 = 'Default Address';

                $address = PartyAddress::firstOrCreate(
                    [
                        'party_id' => $customer->id,
                        'village_id' => $village->id,
                    ],
                    [
                        'label' => 'Imported',
                        'address_line_1' => $shippingAdd1,
                        'village_name' => $village->name,
                        'district' => $village->district,
                        'state' => $village->state,
                    ]
                );

                $warehouseName = $firstRow['warehouse_name'] ?? null;
                if (trim($warehouseName) === '-') $warehouseName = null;
                
                $warehouse = null;
                if ($warehouseName) {
                    $warehouse = Warehouse::where('name', $warehouseName)->first();
                }
                if (!$warehouse) {
                    $warehouse = Warehouse::where('state', $village->state)->first() ?? Warehouse::first();
                }

                // 3. Collect Items
                $items = [];
                foreach ($orderRows as $row) {
                    $productSku = $row['product_sku'] ?? null;
                    if (empty($productSku) || trim($productSku) === '-') continue;

                    $product = Product::where('sku', $productSku)->orWhere('id', $productSku)->first();
                    if (!$product) {
                        throw new \Exception("Product SKU '$productSku' not found.");
                    }
                    
                    $qty = (float)($row['quantity'] ?? 1);
                    $price = (float)($row['unit_price'] ?? $product->sale_price);
                    
                    $taxRate = $row['item_tax_rate'] ?? null;
                    if (trim($taxRate) === '-' || empty($taxRate)) {
                        $taxRate = $product->tax_rate ?? 0;
                    }

                    $items[] = [
                        'product_id' => $product->id,
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'tax_rate' => $taxRate,
                    ];
                }

                if (empty($items)) {
                    // Skip order if it contains no valid items
                    continue;
                }

                $orderDate = $firstRow['order_date'] ?? now()->toDateString();
                if (trim($orderDate) === '-') $orderDate = now()->toDateString();
                
                $status = $firstRow['status'] ?? 'pending';
                if (trim($status) === '-') $status = 'pending';

                // 4. Validate & Create Payload
                $payload = [
                    'type' => 'sale',
                    'party_id' => $customer->id,
                    'warehouse_id' => $warehouse->id,
                    'shipping_address_id' => $address->id,
                    'billing_address_id' => $address->id,
                    'order_date' => $orderDate,
                    'items' => $items,
                    'status' => $status,
                ];

                $calc = $orderService->recalculateAndValidate($payload);
                $payload['items'] = $calc['items'];
                $payload['total_amount'] = $calc['subtotal'];
                $payload['tax_amount'] = $calc['tax_amount'];
                $payload['discount_amount'] = $calc['total_discount'];
                $payload['net_amount'] = $calc['grand_total'];
                $payload['coupon_code'] = $calc['coupon_code'] ?? null;
                $payload['applied_offer_id'] = $calc['applied_offer_id'] ?? null;
                $payload['applied_bogo_ids'] = $calc['applied_bogo_ids'] ?? [];

                $orderService->createOrder($payload);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
CONTENT;

file_put_contents($file, $content);
echo "Patched OrdersImport.php\n";
