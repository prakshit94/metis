<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Catalog\Models\Category;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Users\Models\Permission;
use App\Modules\Catalog\Models\Product;
use App\Modules\Users\Models\Role;
use App\Modules\Orders\Models\Shipment;
use App\Modules\Inventory\Models\Stock;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Models\StockReservation;
use App\Modules\Users\Models\User;
use App\Modules\Catalog\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class OrderLifecycleTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected Warehouse $warehouse;
    protected Product $product;

    protected function setUp(): void
    {
        if (($_ENV['DB_CONNECTION'] ?? null) === 'sqlite' && ! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('The sqlite PDO driver is not available in this PHP runtime.');
        }

        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'orders.view',
            'orders.create',
            'orders.edit',
            'orders.delete',
            'orders.confirm',
            'orders.ship',
            'orders.dispatch',
            'orders.processing',
            'orders.deliver',
            'orders.cancel',
            'orders.return',
            'orders.bulk_status',
            'orders.revert_status',
        ];
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $superAdminRole = Role::findOrCreate('Super Admin', 'web');
        $superAdminRole->syncPermissions($permissions);

        // Create user and roles
        $this->admin = User::create([
            'name' => 'Order Admin User',
            'email' => 'order-admin@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $this->admin->assignRole('Super Admin');

        $this->actingAs($this->admin);

        // Setup Base Inventory & Product
        $this->warehouse = Warehouse::create([
            'name' => 'Order Lifecycle Warehouse',
            'code' => 'OL-WH',
            'status' => 'active',
        ]);

        $category = Category::create([
            'name' => 'Order Category',
            'slug' => 'order-category',
        ]);

        $this->product = Product::create([
            'name' => 'Lifecycle Product',
            'sku' => 'LIFE-PROD-01',
            'slug' => 'lifecycle-product',
            'category_id' => $category->id,
            'selling_price' => 150.00,
            'purchase_price' => 80.00,
            'status' => 'active',
        ]);

        Stock::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100.00,
            'reserved_qty' => 0.00,
            'dispatched_qty' => 0.00,
        ]);
    }

    protected function createPendingOrder(float $qty = 10.0): Order
    {
        $order = Order::create([
            'order_no' => 'ORD-' . strtoupper(Str::random(8)),
            'type' => 'sale',
            'status' => 'pending',
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now(),
            'total_amount' => 150.00 * $qty,
            'net_amount' => 150.00 * $qty,
            'is_draft' => false,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => $qty,
            'unit_price' => 150.00,
            'total_amount' => 150.00 * $qty,
        ]);

        return $order;
    }

    public function test_complete_forward_order_lifecycle_and_inventory_mutations(): void
    {
        $order = $this->createPendingOrder(10.0);

        // Step 1: Confirm Order (Pending -> Confirmed)
        $response = $this->postJson(route('orders.confirm', $order));
        $response->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'confirmed']);
        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100.00,
            'reserved_qty' => 10.00,
        ]);
        $this->assertDatabaseHas('stock_reservations', [
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => 10.00,
            'status' => 'active',
        ]);

        // Step 2: Process Order (Confirmed -> Processing)
        $response = $this->postJson(route('orders.processing', $order));
        $response->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'processing']);
        $this->assertDatabaseHas('shipments', [
            'order_id' => $order->id,
            'status' => 'pending',
        ]);

        // Step 3: Ready to Ship (Processing -> Ready to Ship)
        $response = $this->postJson(route('orders.ship', $order), [
            'carrier_name' => 'FedEx Express',
            'tracking_no' => 'FX-99887766',
        ]);
        $response->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'ready_to_ship']);
        $this->assertDatabaseHas('shipments', [
            'order_id' => $order->id,
            'carrier_name' => 'FedEx Express',
            'tracking_no' => 'FX-99887766',
            'status' => 'pending',
        ]);

        // Step 4: Dispatch Order (Ready to Ship -> Dispatched)
        $response = $this->postJson(route('orders.dispatch', $order));
        $response->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'dispatched']);
        // Verify stock quantity deducted and moved to dispatched_qty, reserved_qty cleared
        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 90.00,
            'reserved_qty' => 0.00,
            'dispatched_qty' => 10.00,
        ]);
        $this->assertDatabaseHas('stock_reservations', [
            'order_id' => $order->id,
            'status' => 'used',
        ]);
        $this->assertDatabaseHas('shipments', [
            'order_id' => $order->id,
            'status' => 'in_transit',
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 10.00,
            'type' => 'out',
        ]);

        // Step 5: Deliver Order (Dispatched -> Delivered)
        $response = $this->postJson(route('orders.deliver', $order));
        $response->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'delivered']);
        // Verify dispatched_qty cleared to 0
        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 90.00,
            'dispatched_qty' => 0.00,
        ]);
        $this->assertDatabaseHas('shipments', [
            'order_id' => $order->id,
            'status' => 'delivered',
        ]);
    }

    public function test_order_cancellation_releases_reserved_stock(): void
    {
        $order = $this->createPendingOrder(15.0);

        // Confirm order to reserve stock
        $this->postJson(route('orders.confirm', $order))->assertOk();
        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->product->id,
            'reserved_qty' => 15.00,
        ]);

        // Cancel order
        $response = $this->postJson(route('orders.cancel', $order));
        $response->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->product->id,
            'reserved_qty' => 0.00,
        ]);
        $this->assertDatabaseHas('stock_reservations', [
            'order_id' => $order->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_order_return_restocks_physical_inventory(): void
    {
        $order = $this->createPendingOrder(20.0);

        // Progress order through confirm -> processing -> ready_to_ship -> dispatch -> deliver
        $this->postJson(route('orders.confirm', $order))->assertOk();
        $this->postJson(route('orders.processing', $order))->assertOk();
        $this->postJson(route('orders.ship', $order), [
            'carrier_name' => 'FedEx Express',
            'tracking_no' => 'FX-99887766',
        ])->assertOk();
        $this->postJson(route('orders.dispatch', $order))->assertOk();
        $this->postJson(route('orders.deliver', $order))->assertOk();

        // Stock quantity should now be 80.00 (100 - 20)
        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->product->id,
            'quantity' => 80.00,
        ]);

        // Return order
        $response = $this->postJson(route('orders.return', $order));
        $response->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'returned']);
        // Stock quantity should be restored to 100.00
        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->product->id,
            'quantity' => 100.00,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'quantity' => 20.00,
            'type' => 'in',
        ]);
        $this->assertDatabaseHas('shipments', [
            'order_id' => $order->id,
            'status' => 'returned',
        ]);
    }

    public function test_order_status_reversions_rollback_inventory_and_shipment(): void
    {
        $order = $this->createPendingOrder(10.0);

        $this->postJson(route('orders.confirm', $order))->assertOk();
        $this->postJson(route('orders.processing', $order))->assertOk();
        $this->postJson(route('orders.ship', $order), [
            'carrier_name' => 'FedEx Express',
            'tracking_no' => 'FX-99887766',
        ])->assertOk();
        $this->postJson(route('orders.dispatch', $order))->assertOk();
        $this->postJson(route('orders.deliver', $order))->assertOk();

        // 1. Revert delivered -> dispatched
        $response = $this->postJson(route('orders.revert-status', $order), ['status' => 'dispatched']);
        $response->assertOk();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'dispatched']);
        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->product->id,
            'dispatched_qty' => 10.00,
        ]);

        // 2. Revert dispatched -> ready_to_ship
        $response = $this->postJson(route('orders.revert-status', $order), ['status' => 'ready_to_ship']);
        $response->assertOk();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'ready_to_ship']);
        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->product->id,
            'quantity' => 100.00,
            'reserved_qty' => 10.00,
            'dispatched_qty' => 0.00,
        ]);

        // 3. Revert ready_to_ship -> processing
        $response = $this->postJson(route('orders.revert-status', $order), ['status' => 'processing']);
        $response->assertOk();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'processing']);

        // 4. Revert processing -> confirmed
        $response = $this->postJson(route('orders.revert-status', $order), ['status' => 'confirmed']);
        $response->assertOk();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'confirmed']);

        // 5. Revert confirmed -> pending
        $response = $this->postJson(route('orders.revert-status', $order), ['status' => 'pending']);
        $response->assertOk();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending']);
        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->product->id,
            'reserved_qty' => 0.00,
        ]);
    }

    public function test_bulk_dispatch_moves_multiple_ready_to_ship_orders_to_dispatched(): void
    {
        $order1 = $this->createPendingOrder(3.0);
        $order2 = $this->createPendingOrder(2.0);

        foreach ([$order1, $order2] as $order) {
            $this->postJson(route('orders.confirm', $order))->assertOk();
            $this->postJson(route('orders.processing', $order))->assertOk();
            $this->postJson(route('orders.ship', $order), [
                'carrier_name' => 'FedEx Express',
                'tracking_no' => 'FX-99887766-' . $order->id,
            ])->assertOk();
        }

        $response = $this->postJson(route('orders.bulk-status'), [
            'order_ids' => [$order1->id, $order2->id],
            'status' => 'dispatched',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $order1->id, 'status' => 'dispatched']);
        $this->assertDatabaseHas('orders', ['id' => $order2->id, 'status' => 'dispatched']);
        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 95.00,
            'reserved_qty' => 0.00,
            'dispatched_qty' => 5.00,
        ]);
    }

    public function test_confirm_uses_legacy_product_stock_when_stock_row_is_missing(): void
    {
        $this->product->update(['stock_quantity' => 25]);

        Stock::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->forceDelete();

        $order = $this->createPendingOrder(3.0);

        $response = $this->postJson(route('orders.confirm', $order));
        $response->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'confirmed']);
        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 25.00,
            'reserved_qty' => 3.00,
        ]);
        $this->assertDatabaseHas('stock_reservations', [
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => 3.00,
            'status' => 'active',
        ]);
    }

    public function test_future_dated_draft_orders_expose_future_order_status_for_the_ui(): void
    {
        $order = Order::create([
            'order_no' => 'ORD-' . strtoupper(Str::random(8)),
            'type' => 'sale',
            'status' => 'pending',
            'order_date' => now(),
            'is_draft' => true,
            'future_order_date' => now()->addDays(7)->toDateString(),
        ]);

        $this->assertSame('future_order', $order->lifecycle_status);
        $this->assertSame('Future Order', $order->status_label);

        $serialized = $order->toArray();
        $this->assertSame('future_order', $serialized['lifecycle_status']);
        $this->assertSame('Future Order', $serialized['status_label']);
    }
}
