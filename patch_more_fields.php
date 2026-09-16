<?php
$file = 'app/Imports/OrdersImport.php';
$content = file_get_contents($file);

$target = "                // 4. Validate & Create Payload
                \$payload = [
                    'type' => 'sale',
                    'party_id' => \$customer->id,
                    'warehouse_id' => \$warehouse->id,
                    'shipping_address_id' => \$shippingAddress->id,
                    'billing_address_id' => \$billingAddress->id,
                    'order_date' => \$orderDate,
                    'items' => \$items,
                    'status' => \$status,
                ];";

$replacement = "                \$orderType = trim((string)(\$firstRow['order_type'] ?? ''));
                if (\$orderType === '' || \$orderType === '-') \$orderType = 'sale';

                \$couponCode = trim((string)(\$firstRow['coupon_code'] ?? ''));
                if (\$couponCode === '-') \$couponCode = null;

                \$trackingNo = trim((string)(\$firstRow['tracking_no'] ?? ''));
                if (\$trackingNo === '-') \$trackingNo = null;

                \$carrierName = trim((string)(\$firstRow['carrier_name'] ?? ''));
                if (\$carrierName === '-') \$carrierName = null;

                // 4. Validate & Create Payload
                \$payload = [
                    'type' => \$orderType,
                    'party_id' => \$customer->id,
                    'warehouse_id' => \$warehouse->id,
                    'shipping_address_id' => \$shippingAddress->id,
                    'billing_address_id' => \$billingAddress->id,
                    'order_date' => \$orderDate,
                    'items' => \$items,
                    'status' => \$status,
                    'coupon_code' => \$couponCode,
                    'tracking_no' => \$trackingNo,
                    'carrier_name' => \$carrierName,
                ];";

$content = str_replace($target, $replacement, $content);
file_put_contents($file, $content);
echo "Patched more fields\n";
