<?php
$file = 'resources/views/orders/create.blade.php';
$content = file_get_contents($file);

$target = '<tr @click="viewOrder(order.id)" class="transition-all" style="cursor: pointer;">
                                            <td class="text-nowrap ps-4 py-2 fw-bold text-body-emphasis">
                                                <span class="text-secondary opacity-75 me-1" x-text="(index + 1) + \'.\'"></span>
                                                <span x-text="order.order_no || order.order_number || (\'Order #\' + order.id)"></span>
                                            </td>';

$replacement = '<tr class="transition-all hover-bg-body-tertiary">
                                            <td class="text-nowrap ps-4 py-2 fw-bold text-body-emphasis">
                                                <span class="text-secondary opacity-75 me-1" x-text="(index + 1) + \'.\'"></span>
                                                <a href="#" @click.prevent="viewOrder(order.id)" class="text-decoration-none text-primary" title="View Order Details">
                                                    <span x-text="order.order_no || order.order_number || (\'Order #\' + order.id)"></span>
                                                </a>
                                                <i class="bi ms-2 text-secondary opacity-50" style="cursor: pointer; font-size: 0.85rem;" title="Copy Order Number" x-data="{ copied: false }" @click="navigator.clipboard.writeText(order.order_no || order.order_number || (\'Order #\' + order.id)).then(() => { copied = true; setTimeout(() => copied = false, 2000) })" :class="copied ? \'bi-check-lg text-success\' : \'bi-copy\'"></i>
                                            </td>';

$count = 0;
$content = str_replace($target, $replacement, $content, $count);
file_put_contents($file, $content);
echo "Replaced $count occurrences.\n";
