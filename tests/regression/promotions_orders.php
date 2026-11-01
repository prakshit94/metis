<?php

// Uses only the disposable schema selected by bootstrap.php.
require __DIR__.'/bootstrap.php';

use App\Http\Controllers\ReferralProgramController;
use App\Models\ReferralProgram;
use App\Modules\Orders\Controllers\CouponController;
use App\Modules\Orders\Controllers\PromotionsController;
use App\Modules\Orders\Models\Coupon;
use App\Modules\Orders\Models\Offer;
use App\Modules\Orders\Models\Order;
use App\Modules\Users\Models\User;
use App\Services\InventoryService;
use App\Services\OrderService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::call('migrate', ['--force' => true]);
$user = User::forceCreate(['name' => 'Promotion test', 'email' => 'promotion-test@example.invalid', 'password' => bcrypt('LocalTest123!')]);
auth()->setUser($user);
$party = DB::table('parties')->insertGetId(['type' => 'customer', 'firstname' => 'Buyer', 'created_by' => $user->id]);
$otherParty = DB::table('parties')->insertGetId(['type' => 'customer', 'firstname' => 'Other']);
$wh = DB::table('warehouses')->insertGetId(['name' => 'Promotion warehouse']);
$taxA = DB::table('tax_rates')->insertGetId(['name' => '10%', 'rate' => 10]);
$taxB = DB::table('tax_rates')->insertGetId(['name' => '20%', 'rate' => 20]);
$a = DB::table('products')->insertGetId(['name' => 'A', 'sku' => 'PROMO-A', 'slug' => 'promo-a', 'selling_price' => 100, 'tax_rate_id' => $taxA]);
$b = DB::table('products')->insertGetId(['name' => 'B', 'sku' => 'PROMO-B', 'slug' => 'promo-b', 'selling_price' => 200, 'tax_rate_id' => $taxB]);
$gift = DB::table('products')->insertGetId(['name' => 'Gift', 'sku' => 'PROMO-G', 'slug' => 'promo-g', 'selling_price' => 25]);
$pricing = app(OrderService::class);
$admin = app(PromotionsController::class);
$cart = ['party_id' => $party, 'warehouse_id' => $wh, 'type' => 'sale', 'selected_offer_id' => null, 'items' => [['product_id' => $a, 'quantity' => 1], ['product_id' => $b, 'quantity' => 1]]];
$quote = $pricing->recalculateAndValidate($cart);
check($quote['grand_total'] == 350, 'baseline sale prices and mixed tax rates preserved');
$created = $admin->couponsStore(requestFor(['code' => ' target ', 'type' => 'fixed', 'value' => 20, 'applicable_products' => [$b]]))->getData(true)['data'];
$coupon = Coupon::findOrFail($created['id']);
check($coupon->code === 'TARGET' && $coupon->applicable_products === [$b], 'CRUD normalizes code and writes canonical target arrays');
DB::table('coupons')->where('id', $coupon->id)->update(['applicable_products' => json_encode(json_encode([$b]))]);
check($coupon->fresh()->applicable_products === [$b], 'legacy double-encoded targets remain readable');
$valid = app(CouponController::class)->validateApi(requestFor(['code' => 'TARGET', 'party_id' => $party, 'warehouse_id' => $wh, 'items' => $cart['items']]))->getData(true);
check($valid['valid'] && $valid['discount'] == 20, 'coupon validator uses canonical server pricing for legacy targets');
invalid(fn () => $admin->couponsStore(requestFor(['code' => ' target ', 'type' => 'fixed', 'value' => 1])), 'canonical duplicate code returns validation failure');
invalid(fn () => $admin->couponsStore(requestFor(['code' => 'BADPCT', 'type' => 'percentage', 'value' => 101])), 'percentage cannot exceed 100');
invalid(fn () => $admin->couponsUpdate(requestFor(['type' => 'free_product']), $coupon->fresh()), 'partial type transition requires gift configuration');
$category = DB::table('categories')->insertGetId(['name' => 'Selected category', 'slug' => 'selected-category']);
$categoryOffer = Offer::create(['name' => 'Category', 'type' => 'category_discount', 'discount_type' => 'fixed', 'value' => 10, 'applicable_categories' => [$category], 'starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(3), 'is_active' => false]);
invalid(fn () => $admin->offersUpdate(requestFor(['applicable_categories' => []]), $categoryOffer), 'sparse category edit cannot remove required targets');
invalid(fn () => $admin->offersUpdate(requestFor(['ends_at' => now()->toDateTimeString()]), $categoryOffer), 'sparse schedule edit validates against retained start time');
$admin->offersUpdate(requestFor(['name' => 'Renamed']), $categoryOffer);
check($categoryOffer->fresh()->name === 'Renamed', 'sparse valid edits preserve campaign configuration');
invalid(fn () => $admin->couponsStore(requestFor(['code' => 'NOSHIPPING', 'type' => 'free_shipping'])), 'unsupported shipping benefit is explicitly rejected');
$search = $admin->productOptions(requestFor(['search' => 'absent-name', 'ids' => [$a]]))->getData(true)['data'];
check(count($search) === 1 && $search[0]['id'] == $a, 'bounded product lookup retains selected targets even outside search results');
$offer = Offer::create(['name' => 'A discount', 'type' => 'order_discount', 'discount_type' => 'fixed', 'value' => 50, 'applicable_products' => [$a], 'is_active' => true]);
$combined = $pricing->recalculateAndValidate(array_replace($cart, ['coupon_code' => 'TARGET', 'selected_offer_id' => $offer->id]));
check(abs($combined['tax_amount'] - 41) < .001 && $combined['grand_total'] == 271, 'disjoint offer/coupon targets allocate tax independently');
$coupon->update(['applicable_products' => [$a], 'value' => 90]);
$overlap = $pricing->recalculateAndValidate(array_replace($cart, ['coupon_code' => 'TARGET', 'selected_offer_id' => $offer->id]));
check($overlap['coupon_discount'] == 50 && $overlap['tax_amount'] == 40 && $overlap['grand_total'] == 240, 'overlapping fixed discounts cannot spill onto unrelated products');
$offer->update(['excluded_products' => [$a]]);
$excluded = $pricing->recalculateAndValidate(array_replace($cart, ['selected_offer_id' => $offer->id]));
check($excluded['order_discount_amount'] == 0, 'offer exclusions take precedence');
$offer->update(['is_active' => false]);
$freeOffer = Offer::create(['name' => 'Buy A earn Gift', 'type' => 'free_product', 'product_id' => $gift, 'applicable_products' => [$a], 'buy_qty' => 1, 'get_qty' => 1, 'is_active' => true]);
$giftQuote = $pricing->recalculateAndValidate(array_replace($cart, ['selected_offer_id' => $freeOffer->id]));
check(count($giftQuote['items']) === 3 && $giftQuote['items'][2]['total_amount'] == 0, 'server appends gift absent from incoming cart');
$giftCoupon = Coupon::create(['code' => 'OWNED', 'type' => 'free_product', 'free_product_id' => $gift, 'free_qty' => 1, 'recipient_party_id' => $party, 'applicable_products' => [$a]]);
$admin->couponsUpdate(requestFor(['is_active' => true]), $giftCoupon);
check($giftCoupon->fresh()->value == 0, 'sparse special-coupon PATCH does not read missing value');
invalid(fn () => $pricing->recalculateAndValidate(array_replace($cart, ['party_id' => $otherParty, 'coupon_code' => 'OWNED'])), 'personal reward cannot be used by another customer');
invalid(fn () => $pricing->recalculateAndValidate(array_replace($cart, ['coupon_code' => 'OWNED', 'items' => [['product_id' => $b, 'quantity' => 1], ['product_id' => $gift, 'quantity' => 1, 'is_gift' => true, 'gift_source' => 'coupon_OWNED']]])), 'ineligible purchased cart cannot claim coupon gift');
invalid(fn () => $pricing->recalculateAndValidate(array_replace($cart, ['items' => [['product_id' => $a, 'quantity' => 1], ['product_id' => $gift, 'quantity' => 1, 'is_gift' => true, 'gift_source' => 'fake']]])), 'fabricated gift rows are rejected');
invalid(fn () => $pricing->recalculateAndValidate(array_replace($cart, ['expected_quote' => str_repeat('0', 64)])), 'changed server quote cannot be silently saved');
$accepted = $pricing->recalculateAndValidate(array_replace($cart, ['expected_quote' => hash('sha256', json_encode($quote))]));
check($accepted['grand_total'] == 350, 'unchanged authoritative quote is accepted');
$programs = app(ReferralProgramController::class);
$programs->store(requestFor(['name' => 'Current', 'is_active' => true, 'milestones' => [['required_referrals' => 0, 'reward_type' => 'wallet', 'reward_value' => '10']]]));
$program = ReferralProgram::firstOrFail();
$milestoneId = $program->milestones->first()->id;
$programs->update(requestFor(['name' => 'Updated', 'is_active' => true, 'milestones' => [['required_referrals' => 0, 'reward_type' => 'wallet', 'reward_value' => '20']]]), $program->id);
check($program->fresh()->milestones->first()->id === $milestoneId, 'referral edit retains existing milestone identity');
invalid(fn () => $programs->store(requestFor(['name' => 'Future', 'is_active' => true, 'start_date' => today()->addDays(7)->toDateString(), 'milestones' => [['required_referrals' => 0, 'reward_type' => 'wallet', 'reward_value' => '10']]])), 'future activation cannot silently switch off current program');
check($program->fresh()->is_active, 'failed activation preserves current program');
\Spatie\Permission\Models\Permission::findOrCreate('promotions-edit', 'web');
$user->givePermissionTo('promotions-edit');
try {
    $programs->bulk(requestFor(['action' => 'delete', 'ids' => [$program->id]]));
    check(false, 'edit/no-delete principal cannot bulk delete');
} catch (\Symfony\Component\HttpKernel\Exception\HttpException $error) {
    check($error->getStatusCode() === 403, 'bulk deletion enforces action-specific permission');
}
// Reversal must reconcile the actual wallet movement and separately record recovery.
DB::table('parties')->where('id', $otherParty)->update(['wallet_balance' => 20]);
$order = Order::forceCreate(['order_no' => 'PROMO-REVERSAL', 'type' => 'sale', 'party_id' => $party, 'warehouse_id' => $wh, 'order_date' => now(), 'status' => 'delivered']);
$rewardId = DB::table('referral_rewards')->insertGetId(['referrer_id' => $otherParty, 'referred_id' => $party, 'order_id' => $order->id, 'referral_program_id' => $program->id, 'milestone_id' => $milestoneId, 'reward_type' => 'wallet', 'reward_amount' => 100, 'status' => 'completed']);
(new ReflectionMethod(InventoryService::class, 'revokeReferralReward'))->invoke(app(InventoryService::class), $order);
check(DB::table('wallet_transactions')->where('reference_type', 'referral_revoked')->where('reference_id', $rewardId)->value('amount') == 20 && DB::table('referral_rewards')->where('id', $rewardId)->value('recovery_due') == 80, 'referral reversal records actual debit and outstanding recovery separately');
invalid(fn () => $programs->destroy($program->id), 'issued reward history prevents destructive program deletion');


// Exercise referral delivery and requalification using synthetic orders only.
$programs->store(requestFor(['name' => 'Referral gift', 'is_active' => true, 'milestones' => [['required_referrals' => 2, 'reward_type' => 'product', 'reward_value' => (string) $gift]]]));
$giftProgram = ReferralProgram::latest('id')->firstOrFail();
$referralOrders = [];
foreach ([1, 2, 3] as $number) {
    $referred = DB::table('parties')->insertGetId(['type' => 'customer', 'firstname' => 'Referral '.$number, 'referred_by' => $otherParty]);
    $referralOrders[] = Order::forceCreate(['order_no' => 'PROMO-REF-'.$number, 'type' => 'sale', 'party_id' => $referred, 'warehouse_id' => $wh, 'order_date' => now(), 'status' => 'dispatched', 'created_by' => $user->id]);
}
$inventory = app(InventoryService::class);
$inventory->deliverOrder($referralOrders[0]);
$inventory->deliverOrder($referralOrders[1]);
$issued = DB::table('referral_rewards')->where('referral_program_id', $giftProgram->id)->first();
$issuedCoupon = Coupon::where('code', $issued->coupon_code)->firstOrFail();
check($issuedCoupon->type === 'free_product' && $issuedCoupon->free_qty === 1 && $issuedCoupon->recipient_party_id === $otherParty, 'delivery issues a one-unit reward bound to its recipient');
$giftRedemption = $pricing->recalculateAndValidate(array_replace($cart, ['party_id' => $otherParty, 'coupon_code' => $issuedCoupon->code, 'items' => [['product_id' => $gift, 'quantity' => 4]]]));
check(count($giftRedemption['items']) === 2 && $giftRedemption['items'][0]['total_amount'] == 100 && $giftRedemption['items'][1]['quantity'] == 1 && $giftRedemption['items'][1]['total_amount'] == 0, 'reward gives one free unit while purchased units remain payable');
$referralOrders[0]->update(['status' => 'returned']);
$inventory->deliverOrder($referralOrders[2]);
$inventory->deliverOrder($referralOrders[2]);
check(DB::table('referral_rewards')->where('referral_program_id', $giftProgram->id)->count() === 1, 'count drop and requalification do not reissue an already completed milestone');

// Concurrent program activations serialize on one shared database row.
$activationIds = [];
foreach (['Concurrent A', 'Concurrent B'] as $name) $activationIds[] = ReferralProgram::create(['name' => $name, 'is_active' => false])->id;
$barrier = sys_get_temp_dir().'/promotion-activation-'.bin2hex(random_bytes(6));
$workers = [];
try {
    foreach ($activationIds as $id) {
        $process = proc_open([PHP_BINARY, __DIR__.'/concurrency_worker.php', 'referral-activation', json_encode(['program' => $id, 'barrier' => $barrier])], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, array_merge(getenv(), ['METIS_EXISTING_TEST_DB' => $testDatabase]));
        if (! is_resource($process)) throw new RuntimeException('Could not start activation worker');
        fclose($pipes[0]);
        $workers[] = [$process, $pipes];
    }
    $deadline = microtime(true) + 15;
    while (count(glob($barrier.'.ready.*')) < 2 && microtime(true) < $deadline) usleep(10000);
    file_put_contents($barrier.'.go', 'go');
    foreach ($workers as [$process, $pipes]) {
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        $exit = proc_close($process);
        $result = json_decode($output, true);
        check($exit === 0 && ($result['ok'] ?? false), 'concurrent activation worker commits: '.$error);
    }
    check(ReferralProgram::where('is_active', true)->count() === 1, 'concurrent activations preserve one active program');
} finally {
    foreach (glob($barrier.'.*') as $file) unlink($file);
}

// A wallet change between review and save must roll back the new order.
$ordersBefore = Order::count();
invalid(fn () => $pricing->createOrder(['type' => 'sale', 'party_id' => $party, 'warehouse_id' => $wh, 'order_date' => now(), 'net_amount' => 100, 'use_wallet_balance' => true, 'expected_wallet_amount' => 100, 'items' => [['product_id' => $a, 'quantity' => 1, 'unit_price' => 100, 'total_amount' => 100]]]), 'stale wallet review is rejected');
check(Order::count() === $ordersBefore, 'stale wallet review leaves no partial order');

// Rendering stays in memory; regression runs leave no fixture HTML in the workspace.
$role = \Spatie\Permission\Models\Role::findOrCreate('Super Admin', 'web');
$user->assignRole($role);
$request = \Illuminate\Http\Request::create('/orders/create?customer_id='.$party);
$request->setUserResolver(fn () => $user);
view()->share('errors', new \Illuminate\Support\ViewErrorBag);
app()->instance('request', $request);
foreach (['offers', 'coupons'] as $screen) {
    check(str_contains(app(PromotionsController::class)->$screen()->render(), 'promotions'), $screen.' page renders');
}
view()->share('errors', new \Illuminate\Support\ViewErrorBag);
$viewRequest = \Illuminate\Http\Request::create('/promotions/referral-programs');
$viewRequest->setUserResolver(fn () => $user);
check(str_contains($programs->index($viewRequest)->render(), 'Referral Programs'), 'referral page renders');
check(str_contains(app(\App\Modules\Orders\Controllers\OrderController::class)->create()->render(), 'Create Order'), 'order creation page renders');

// Both edit entry points discover only the selected customer's personal rewards.
$editableOrder = Order::forceCreate(['order_no' => 'PROMO-EDIT', 'type' => 'sale', 'party_id' => $party, 'warehouse_id' => $wh, 'order_date' => now(), 'status' => 'pending', 'created_by' => $user->id]);
$apiRequest = \Illuminate\Http\Request::create('/api/orders/create-options?order_id='.$editableOrder->id);
$apiRequest->setUserResolver(fn () => $user);
app()->instance('request', $apiRequest);
$options = app(\App\Modules\Orders\Controllers\OrderController::class)->create()->getData(true);
check(collect($options['activeCoupons'])->contains('code', 'OWNED') && ! collect($options['activeCoupons'])->contains('code', $issuedCoupon->code), 'order-ID create-options retain own rewards and hide another recipient rewards');
$options = app(\App\Modules\Orders\Controllers\OrderController::class)->edit($editableOrder->fresh())->getData(true);
check(collect($options['activeCoupons'])->contains('code', 'OWNED') && ! collect($options['activeCoupons'])->contains('code', $issuedCoupon->code), 'separate edit endpoint filters reward recipients');
app()->instance('request', $request);

// A rollback must refuse before deleting ownership or reward history.
$rollback = require dirname(__DIR__, 2).'/database/migrations/2026_11_01_000001_harden_promotion_rewards.php';
try {
    $rollback->down();
    check(false, 'rollback cannot discard issued reward metadata');
} catch (RuntimeException $error) {
    check(str_contains($error->getMessage(), 'Cannot roll back promotion ownership'), 'rollback protects existing ownership and reward history');
}
check(\Illuminate\Support\Facades\Schema::hasTable('promotion_locks'), 'rollback refusal happens before any schema is dropped');
// Clear only synthetic metadata in this disposable schema before structural rollback testing.
DB::table('coupons')->update(['recipient_party_id' => null]);
DB::table('referral_rewards')->update(['referral_threshold' => null, 'entitlement_key' => null, 'recovery_due' => 0]);
DB::table('referral_program_milestones')->update(['retired_at' => null]);
// Verify forward repair of legacy data and rollback of this migration.
Artisan::call('migrate:rollback', ['--step' => 1, '--force' => true]);
$legacyCustomer = DB::table('parties')->insertGetId(['type' => 'customer', 'firstname' => 'Legacy blank code']);
$legacy = DB::table('coupons')->insertGetId(['code' => 'LEGACY-REPAIR', 'type' => 'fixed', 'value' => 5, 'applicable_products' => json_encode(json_encode([$a]))]);
Artisan::call('migrate', ['--force' => true]);
check(json_decode(DB::table('coupons')->where('id', $legacy)->value('applicable_products'), true) === [$a], 'migration repairs persisted legacy targets and rolls forward after rollback');
check(! empty(DB::table('parties')->where('id', $legacyCustomer)->value('referral_code')), 'migration backfills missing customer referral codes outside page reads');
$backfilledCode = DB::table('parties')->where('id', $legacyCustomer)->value('referral_code');
DB::table('promotion_locks')->insert(['name' => 'unrelated_lock']);
DB::table('referral_rewards')->where('id', $rewardId)->update(['referral_threshold' => 123]);
$retry = require dirname(__DIR__, 2).'/database/migrations/2026_11_01_000001_harden_promotion_rewards.php';
$invalidTarget = DB::table('coupons')->insertGetId(['code' => 'INVALID-LEGACY-TARGET', 'type' => 'fixed', 'value' => 5, 'applicable_products' => json_encode('not-an-array')]);
try {
    $retry->up();
    check(false, 'malformed legacy targets must stop the migration');
} catch (RuntimeException $error) {
    check(str_contains($error->getMessage(), 'promotion target array'), 'malformed legacy targets fail preflight instead of broadening campaign eligibility');
}
DB::table('coupons')->where('id', $invalidTarget)->delete();
DB::table('coupons')->where('id', $issuedCoupon->id)->update(['recipient_party_id' => $party]);
try {
    $retry->up();
    check(false, 'migration cannot silently reassign existing coupon ownership');
} catch (RuntimeException $error) {
    check(str_contains($error->getMessage(), 'different customer'), 'conflicting reward ownership stops migration preflight');
}
check(DB::table('coupons')->where('id', $issuedCoupon->id)->value('recipient_party_id') == $party, 'ownership preflight leaves existing recipient untouched');
DB::table('coupons')->where('id', $issuedCoupon->id)->update(['recipient_party_id' => $otherParty]);

$retry->up();
check(DB::table('parties')->where('id', $legacyCustomer)->value('referral_code') === $backfilledCode
    && DB::table('promotion_locks')->count() === 2
    && DB::table('referral_rewards')->where('id', $rewardId)->value('referral_threshold') == 123,
    'migration resumes existing schema without replacing assigned codes, locks or reward snapshots');
echo "Promotion lifecycle, rendering and migration checks passed.\n";
