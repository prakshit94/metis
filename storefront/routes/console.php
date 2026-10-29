<?php

use App\Modules\Catalog\Models\Product;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Storefront\Models\CustomerAccount;

Artisan::command('shop:publish {product : Product ID} {--hide} {--featured} {--min=1} {--max=1000}', function () {
    $min = filter_var($this->option('min'), FILTER_VALIDATE_INT);
    $max = filter_var($this->option('max'), FILTER_VALIDATE_INT);
    if (! $min || ! $max || $min < 1 || $max < $min || $max > 1000) {
        $this->error('Choose quantity limits between 1 and 1000, with max >= min.');

        return 1;
    }
    $product = Product::findOrFail($this->argument('product'));
    DB::table('products')->where('id', $product->id)->update(['storefront_visible' => ! $this->option('hide'), 'is_featured' => (bool) $this->option('featured'), 'min_order_qty' => $min, 'max_order_qty' => $max, 'updated_at' => now()]);
    $this->info('Storefront visibility updated. Existing product prices, status and inventory are unchanged.');
});

Artisan::command('shop:account {id : Customer account ID} {--disable-login} {--enable-login} {--disable-ordering} {--enable-ordering}', function () {
    $data = [];
    foreach (['login', 'ordering'] as $field) {
        if ($this->option('disable-'.$field) && $this->option('enable-'.$field)) {
            $this->error('Choose enable or disable for '.$field.'.');

            return 1;
        }
        if ($this->option('disable-'.$field)) {
            $data[$field.'_enabled'] = false;
        }
        if ($this->option('enable-'.$field)) {
            $data[$field.'_enabled'] = true;
        }
    }
    if (! $data) {
        $this->error('Choose an access option.');

        return 1;
    }
    $account = CustomerAccount::findOrFail($this->argument('id'));
    $account->forceFill($data)->save();
    $this->info('Customer storefront access updated. Email verification is still required.');
});
