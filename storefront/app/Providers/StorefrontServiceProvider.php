<?php

namespace Storefront\Providers;

use App\Modules\Inventory\Models\GoodsReceipt;
use App\Modules\Inventory\Models\InventoryAdjustment;
use App\Modules\Inventory\Models\PurchaseOrder;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Models\StockTransfer;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderReturn;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Storefront\Auth\CustomerUserProvider;

class StorefrontServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        \Illuminate\Support\Facades\Validator::resolver(
            fn ($translator, $data, $rules, $messages, $attributes) => new \App\Validation\TypedValidator($translator, $data, $rules, $messages, $attributes)
        );

        config(['app.name' => 'Metis Market', 'app.url' => config('storefront.url')]);
        Relation::morphMap([
            'Order' => Order::class,
            'return' => OrderReturn::class,
            'adjustment' => InventoryAdjustment::class,
            'receipt' => GoodsReceipt::class,
            'purchase_order' => PurchaseOrder::class,
            'transfer' => StockTransfer::class,
            'opening' => StockMovement::class,
        ]);
        Auth::provider('customer-eloquent', fn ($app, $config) => new CustomerUserProvider($app['hash'], $config['model']));
    }
}
