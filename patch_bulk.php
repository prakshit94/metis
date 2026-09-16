<?php
$file = 'app/Modules/Orders/Controllers/OrderController.php';
$content = file_get_contents($file);

$target = "                    } elseif (\$targetStatus === 'dispatched') {
                        if (\$order->status === 'ready_to_ship') {";

$replacement = "                    } elseif (\$targetStatus === 'dispatched') {
                        if (\$order->status === 'ready_to_ship') {
                            \$shipment = \$order->shipments()->first();
                            if (! \$shipment || ! \$shipment->carrier_name) {
                                \$errors[] = \"Order #{\$order->order_no}: Cannot dispatch without a valid carrier.\";
                                \$skipped++;
                                continue;
                            }
                            if (strtolower(\$shipment->carrier_name) === 'india post' && empty(\$shipment->tracking_no)) {
                                \$errors[] = \"Order #{\$order->order_no}: Cannot dispatch without valid India Post tracking details.\";
                                \$skipped++;
                                continue;
                            }";

$content = str_replace($target, $replacement, $content);
file_put_contents($file, $content);
echo "Done replacing bulk status dispatch logic.\n";
