<?php
$content = file_get_contents('resources/views/promotions/coupons.blade.php');

$search1 = '<small class="text-muted d-block mt-1" style="font-size: 10px;">The promo code customers type in to redeem.</small>';
$replace1 = '<small class="text-muted d-block mt-1" style="font-size: 10px;">The promo code customers type in to redeem.</small><div class="mt-1 p-2 bg-body rounded-2 border" style="font-size: 9px;"><strong class="text-primary">Use Case:</strong> Share this code in marketing emails (e.g. WELCOME10) to track campaign success.</div>';

$search2 = '<small class="text-muted d-block mt-1" style="font-size: 10px;">Choose percentage or fixed rate.</small>';
$replace2 = '<small class="text-muted d-block mt-1" style="font-size: 10px;">Choose percentage or fixed rate.</small><div class="mt-1 p-2 bg-body rounded-2 border" style="font-size: 9px;"><strong class="text-primary">Use Case:</strong> Percentage discounts scale with cart size. Flat discounts are better for maintaining predictable profit margins on high-value carts.</div>';

$search3 = '<small class="text-muted d-block mt-1" style="font-size: 10px;">Numeric value of the discount.</small>';
$replace3 = '<small class="text-muted d-block mt-1" style="font-size: 10px;">Numeric value of the discount.</small>'; // already clear from context

$search4 = '<small class="text-muted d-block mt-1" style="font-size: 10px;">Minimum purchase requirement to unlock coupon.</small>';
$replace4 = '<small class="text-muted d-block mt-1" style="font-size: 10px;">Minimum purchase requirement to unlock coupon.</small><div class="mt-1 p-2 bg-body rounded-2 border" style="font-size: 9px;"><strong class="text-primary">Use Case:</strong> Encourage customers to add more items to their cart to reach the threshold (increases Average Order Value).</div>';

$search5 = '<small class="text-muted d-block mt-1" style="font-size: 10px;">Maximum cap. Leave empty/0 for unlimited.</small>';
$replace5 = '<small class="text-muted d-block mt-1" style="font-size: 10px;">Maximum cap. Leave empty/0 for unlimited.</small><div class="mt-1 p-2 bg-body rounded-2 border" style="font-size: 9px;"><strong class="text-primary">Use Case:</strong> Crucial when using Percentage discounts to protect your margins on very large bulk orders (e.g. 50% off up to Rs 1000).</div>';

$search6 = '<small class="text-muted d-block mt-1" style="font-size: 10px;">Date when this coupon becomes invalid.</small>';
$replace6 = '<small class="text-muted d-block mt-1" style="font-size: 10px;">Date when this coupon becomes invalid.</small><div class="mt-1 p-2 bg-body rounded-2 border" style="font-size: 9px;"><strong class="text-primary">Use Case:</strong> Drive urgency by creating time-limited promotions (e.g. "Sale ends Sunday!").</div>';

$search7 = '<small class="text-muted d-block mt-1" style="font-size: 10px;">Total times code can be redeemed (empty = unlimited).</small>';
$replace7 = '<small class="text-muted d-block mt-1" style="font-size: 10px;">Total times code can be redeemed (empty = unlimited).</small><div class="mt-1 p-2 bg-body rounded-2 border" style="font-size: 9px;"><strong class="text-primary">Use Case:</strong> Create scarcity and FOMO (e.g. "Valid only for the first 100 buyers!").</div>';

$content = str_replace($search1, $replace1, $content);
$content = str_replace($search2, $replace2, $content);
$content = str_replace($search4, $replace4, $content);
$content = str_replace($search5, $replace5, $content);
$content = str_replace($search6, $replace6, $content);
$content = str_replace($search7, $replace7, $content);

file_put_contents('resources/views/promotions/coupons.blade.php', $content);
echo "Coupons patched.\n";
