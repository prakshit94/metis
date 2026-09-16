<?php
$file = 'app/Modules/Orders/Controllers/OrderController.php';
$content = file_get_contents($file);

$target = "        \$headers = ['order_reference', 'order_date', 'customer_phone', 'customer_first_name', 'customer_last_name', 'shipping_address_line_1', 'shipping_village_name', 'product_sku', 'quantity', 'unit_price', 'status'];";

$replacement = "        \$headers = [
            'Order ID', 'Order No', 'Order Date', 'Status', 'Order Type',
            'Order Subtotal', 'Order Tax', 'Order Discount', 'Order Total',
            'Coupon Code', 'Wallet Used', 'Cashback Earned',
            'Customer First Name', 'Customer Middle Name', 'Customer Last Name',
            'Company Name', 'Customer Email', 'Customer Phone', 'Alternate Mobile', 'Relative Name', 'Relative Phone', 'GST Number', 'PAN Number',
            'Billing Address 1', 'Billing Address 2', 'Billing Village', 'Billing PO/BO', 'Billing Taluka', 'Billing District', 'Billing City', 'Billing State', 'Billing Pincode',
            'Shipping Address 1', 'Shipping Address 2', 'Shipping Village', 'Shipping PO/BO', 'Shipping Taluka', 'Shipping District', 'Shipping City', 'Shipping State', 'Shipping Pincode',
            'Warehouse Name', 'Carrier Name', 'Tracking No',
            'Product Name', 'Product SKU', 'Batch Number', 'Quantity', 'Unit Price', 'Item Tax Rate', 'Item Tax', 'Item Discount', 'Item Net',
        ];";

$content = str_replace($target, $replacement, $content);
file_put_contents($file, $content);
echo "Patched template headers.\n";
