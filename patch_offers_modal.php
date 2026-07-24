<?php
$content = file_get_contents('resources/views/promotions/offers.blade.php');

$search1 = '<small class="text-muted d-block mt-1" style="font-size: 10px;">Select Order Discount or BOGO.</small>';
$replace1 = '<small class="text-muted d-block mt-1" style="font-size: 10px;">Select Order Discount or BOGO.</small><div class="mt-1 p-2 bg-body rounded-2 border" style="font-size: 9px;"><strong class="text-warning">Use Case:</strong> Use "Order Discount" to apply a flat/percentage discount to the entire cart. Use "BOGO" to give away free items when customers buy specific quantities (e.g. Buy 2 Get 1 Free).</div>';

$search2 = '<small class="text-muted d-block mt-1" style="font-size: 10px;">Minimum purchase requirement to unlock offer.</small>';
$replace2 = '<small class="text-muted d-block mt-1" style="font-size: 10px;">Minimum purchase requirement to unlock offer.</small><div class="mt-1 p-2 bg-body rounded-2 border" style="font-size: 9px;"><strong class="text-success">Use Case:</strong> Encourage customers to add more items to their cart to reach the threshold (increases Average Order Value).</div>';

$search3 = '<small class="text-muted d-block mt-1" style="font-size: 10px;">Maximum cap. Leave empty/0 for unlimited.</small>';
$replace3 = '<small class="text-muted d-block mt-1" style="font-size: 10px;">Maximum cap. Leave empty/0 for unlimited.</small><div class="mt-1 p-2 bg-body rounded-2 border" style="font-size: 9px;"><strong class="text-success">Use Case:</strong> Crucial when using Percentage discounts to protect your margins on very large bulk orders (e.g. 50% off up to Rs 1000).</div>';

$search4 = '<small class="text-muted d-block mt-1" style="font-size: 10px;">Quantity customer must buy.</small>';
$replace4 = '<small class="text-muted d-block mt-1" style="font-size: 10px;">Quantity customer must buy.</small><div class="mt-1 p-2 bg-body rounded-2 border" style="font-size: 9px;"><strong class="text-success">Use Case:</strong> E.g. Set to 2 for a "Buy 2 Get 1" deal.</div>';

$search5 = '<small class="text-muted d-block mt-1" style="font-size: 10px;">Select specific products, or leave empty for global offer.</small>';
$replace5 = '<small class="text-muted d-block mt-1" style="font-size: 10px;">Select specific products, or leave empty for global offer.</small><div class="mt-1 p-2 bg-body rounded-2 border" style="font-size: 9px;"><strong class="text-warning">Use Case:</strong> Useful for clearing out old inventory or aggressively pushing a new product launch. If left empty, the offer applies to the entire catalog.</div>';

$search6 = '<small class="text-muted d-block mt-1" style="font-size: 10px;">Application priority rank.</small>';
$replace6 = '<small class="text-muted d-block mt-1" style="font-size: 10px;">Application priority rank.</small><div class="mt-1 p-2 bg-body rounded-2 border" style="font-size: 9px;"><strong class="text-info">Use Case:</strong> When multiple offers could apply to a cart, the system evaluates the one with the highest priority number first.</div>';

$content = str_replace($search1, $replace1, $content);
$content = str_replace($search2, $replace2, $content);
$content = str_replace($search3, $replace3, $content);
$content = str_replace($search4, $replace4, $content);
$content = str_replace($search5, $replace5, $content);
$content = str_replace($search6, $replace6, $content);

file_put_contents('resources/views/promotions/offers.blade.php', $content);
echo "Offers patched.\n";
