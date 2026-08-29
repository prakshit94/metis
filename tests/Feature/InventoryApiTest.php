<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Warehouse;
use App\Modules\Inventory\Models\InventoryAdjustment;
use App\Modules\Inventory\Models\Stock;
use App\Modules\Inventory\Models\StockTransfer;
use App\Modules\Users\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class InventoryApiTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected Warehouse $warehouse1;

    protected Warehouse $warehouse2;

    protected Product $product;

    protected function setUp(): void
    {
        if (($_ENV['DB_CONNECTION'] ?? null) === 'sqlite' && ! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('The sqlite PDO driver is not available in this PHP runtime.');
        }

        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = $this->createUser('admin_'.uniqid().'@example.com');
        $this->admin->assignRole('Super Admin');
        $this->actingAs($this->admin);

        // Setup base data
        $this->warehouse1 = Warehouse::create(['name' => 'Main Warehouse', 'code' => 'WH-01', 'status' => 'active']);
        $this->warehouse2 = Warehouse::create(['name' => 'Secondary Warehouse', 'code' => 'WH-02', 'status' => 'active']);

        $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
        $this->product = Product::create([
            'name' => 'Smartphone',
            'sku' => 'PHONE-01',
            'slug' => 'smartphone',
            'category_id' => $category->id,
            'selling_price' => 500.00,
            'purchase_price' => 300.00,
            'status' => 'active',
        ]);

        // Seed initial stock
        Stock::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse1->id,
            'quantity' => 100.00,
        ]);
        Stock::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse2->id,
            'quantity' => 50.00,
        ]);
    }

    // ── Stock Transfers Tests ─────────────────────────────────────────

    public function test_store_creates_stock_transfer(): void
    {
        $response = $this->postJson('/api/inventory/transfers', [
            'from_warehouse_id' => $this->warehouse1->id,
            'to_warehouse_id' => $this->warehouse2->id,
            'reason' => 'Restocking',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10,
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.from_warehouse_id', $this->warehouse1->id)
            ->assertJsonPath('data.to_warehouse_id', $this->warehouse2->id);

        $this->assertDatabaseHas('stock_transfers', [
            'from_warehouse_id' => $this->warehouse1->id,
            'to_warehouse_id' => $this->warehouse2->id,
            'status' => 'draft',
        ]);
    }

    public function test_send_marks_transfer_as_sent(): void
    {
        $transfer = StockTransfer::create([
            'transfer_no' => 'TR-1001',
            'from_warehouse_id' => $this->warehouse1->id,
            'to_warehouse_id' => $this->warehouse2->id,
            'status' => 'draft',
        ]);
        $transfer->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 10,
        ]);

        $response = $this->postJson("/api/inventory/transfers/{$transfer->id}/send");

        $response->assertOk()
            ->assertJsonPath('data.status', 'sent');

        $this->assertDatabaseHas('stock_transfers', [
            'id' => $transfer->id,
            'status' => 'sent',
        ]);
        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse1->id,
            'in_transit_qty' => 10.00,
        ]);
    }

    public function test_receive_commits_stock_movement(): void
    {
        $transfer = StockTransfer::create([
            'transfer_no' => 'TR-1002',
            'from_warehouse_id' => $this->warehouse1->id,
            'to_warehouse_id' => $this->warehouse2->id,
            'status' => 'draft',
        ]);
        $transfer->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 20,
        ]);

        // Actually send it via API to deduct from main stock
        $this->postJson("/api/inventory/transfers/{$transfer->id}/send");

        $response = $this->postJson("/api/inventory/transfers/{$transfer->id}/receive");

        $response->assertOk()
            ->assertJsonPath('data.status', 'received');

        // Initial was 100, transferred 20. Main should now be 80.
        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse1->id,
            'quantity' => 80.00,
            'in_transit_qty' => 0.00,
        ]);

        // Secondary should be 50 + 20 = 70.
        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse2->id,
            'quantity' => 70.00,
        ]);
    }

    public function test_cancel_returns_stock_or_aborts_sent(): void
    {
        $transfer = StockTransfer::create([
            'transfer_no' => 'TR-1003',
            'from_warehouse_id' => $this->warehouse1->id,
            'to_warehouse_id' => $this->warehouse2->id,
            'status' => 'draft',
        ]);
        $transfer->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 20,
        ]);

        $this->postJson("/api/inventory/transfers/{$transfer->id}/send");

        $response = $this->postJson("/api/inventory/transfers/{$transfer->id}/cancel");

        $response->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('stock_transfers', [
            'id' => $transfer->id,
            'status' => 'cancelled',
        ]);
        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse1->id,
            'in_transit_qty' => 0.00,
        ]);
    }

    public function test_bulk_action_for_transfers(): void
    {
        $transfer1 = StockTransfer::create([
            'transfer_no' => 'TR-BULK1',
            'from_warehouse_id' => $this->warehouse1->id,
            'to_warehouse_id' => $this->warehouse2->id,
            'status' => 'draft',
        ]);
        $transfer1->items()->create(['product_id' => $this->product->id, 'quantity' => 5]);

        $transfer2 = StockTransfer::create([
            'transfer_no' => 'TR-BULK2',
            'from_warehouse_id' => $this->warehouse1->id,
            'to_warehouse_id' => $this->warehouse2->id,
            'status' => 'draft',
        ]);
        $transfer2->items()->create(['product_id' => $this->product->id, 'quantity' => 5]);

        // Test Bulk Send (dispatch)
        $response = $this->postJson('/api/inventory/transfers/bulk-action', [
            'action' => 'send',
            'ids' => [$transfer1->id, $transfer2->id],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('stock_transfers', ['id' => $transfer1->id, 'status' => 'sent']);
        $this->assertDatabaseHas('stock_transfers', ['id' => $transfer2->id, 'status' => 'sent']);
        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse1->id,
            'in_transit_qty' => 10.00,
        ]);

        // Test Bulk Receive
        $response = $this->postJson('/api/inventory/transfers/bulk-action', [
            'action' => 'receive',
            'ids' => [$transfer1->id, $transfer2->id],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('stock_transfers', ['id' => $transfer1->id, 'status' => 'received']);
        $this->assertDatabaseHas('stock_transfers', ['id' => $transfer2->id, 'status' => 'received']);
        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse1->id,
            'in_transit_qty' => 0.00,
        ]);
    }

    public function test_stock_management_populates_empty_warehouse_with_placeholder_rows(): void
    {
        Stock::where('warehouse_id', $this->warehouse2->id)->forceDelete();

        $response = $this->getJson('/api/inventory/stocks?warehouse_id='.$this->warehouse2->id);

        $response->assertOk();
        $item = collect($response->json('data'))->firstWhere('product_id', $this->product->id);
        $this->assertNotNull($item);
        $this->assertEquals($this->warehouse2->id, $item['warehouse_id']);
        $this->assertEquals(0, $item['quantity']);
        $this->assertEquals(0, $item['in_transit_qty']);

        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse2->id,
            'quantity' => 0.00,
            'in_transit_qty' => 0.00,
        ]);
    }

    // ── Inventory Adjustments Tests ───────────────────────────────────

    public function test_store_creates_inventory_adjustment(): void
    {
        $response = $this->postJson('/api/inventory/adjustments', [
            'warehouse_id' => $this->warehouse1->id,
            'reason' => 'Damaged Goods',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'current_qty' => 100,
                    'new_qty' => 95,
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.reason', 'Damaged Goods');

        $this->assertDatabaseHas('inventory_adjustments', [
            'warehouse_id' => $this->warehouse1->id,
            'status' => 'pending',
            'reason' => 'Damaged Goods',
        ]);
    }

    public function test_approve_applies_adjustment(): void
    {
        $adjustment = InventoryAdjustment::create([
            'reference_no' => 'ADJ-1001',
            'warehouse_id' => $this->warehouse1->id,
            'reason' => 'Audit check',
            'status' => 'pending',
            'adjusted_by' => $this->admin->id,
        ]);
        $adjustment->items()->create([
            'product_id' => $this->product->id,
            'current_qty' => 100,
            'new_qty' => 110, // Stock goes up by 10
            'difference' => 10,
        ]);

        $response = $this->postJson("/api/inventory/adjustments/{$adjustment->id}/approve");

        $response->assertOk()
            ->assertJsonPath('data.status', 'approved');

        // Stock should now be 110.00
        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse1->id,
            'quantity' => 110.00,
        ]);
    }

    public function test_reject_adjustment_marks_status(): void
    {
        $adjustment = InventoryAdjustment::create([
            'reference_no' => 'ADJ-1002',
            'warehouse_id' => $this->warehouse1->id,
            'status' => 'pending',
            'adjusted_by' => $this->admin->id,
        ]);
        $adjustment->items()->create([
            'product_id' => $this->product->id,
            'current_qty' => 100,
            'new_qty' => 90,
            'difference' => -10,
        ]);

        $response = $this->postJson("/api/inventory/adjustments/{$adjustment->id}/reject");

        $response->assertOk()
            ->assertJsonPath('data.status', 'rejected');

        // Stock should remain unchanged (100.00)
        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse1->id,
            'quantity' => 100.00,
        ]);
    }

    public function test_bulk_action_for_adjustments(): void
    {
        $adjustment1 = InventoryAdjustment::create([
            'reference_no' => 'ADJ-BULK1',
            'warehouse_id' => $this->warehouse1->id,
            'status' => 'pending',
            'adjusted_by' => $this->admin->id,
        ]);
        $adjustment1->items()->create(['product_id' => $this->product->id, 'current_qty' => 100, 'new_qty' => 105, 'difference' => 5]);

        $adjustment2 = InventoryAdjustment::create([
            'reference_no' => 'ADJ-BULK2',
            'warehouse_id' => $this->warehouse1->id,
            'status' => 'pending',
            'adjusted_by' => $this->admin->id,
        ]);
        $adjustment2->items()->create(['product_id' => $this->product->id, 'current_qty' => 100, 'new_qty' => 95, 'difference' => -5]);

        // Bulk Approve
        $response = $this->postJson('/api/inventory/adjustments/bulk-action', [
            'action' => 'approve',
            'ids' => [$adjustment1->id, $adjustment2->id],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('inventory_adjustments', ['id' => $adjustment1->id, 'status' => 'approved']);
        $this->assertDatabaseHas('inventory_adjustments', ['id' => $adjustment2->id, 'status' => 'approved']);

        // Stock adjustments: first to 105, then to 95 (absolute override, so final is 95)
        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse1->id,
            'quantity' => 95.00,
        ]);
    }

    public function test_stock_level_filter(): void
    {
        $admin = $this->createUser('stock-filter-admin@example.com');
        $admin->givePermissionTo(['stockmanagement-view', 'product-view']);
        $this->actingAs($admin);

        // Clear existing stocks to ensure predictable test results
        \DB::table('stocks')->delete();

        // Create product
        $product1 = Product::create(['name' => 'Prod 1', 'sku' => 'SKU1', 'status' => 'active', 'selling_price' => 10, 'purchase_price' => 5, 'slug' => 'prod-1', 'min_stock_level' => 5]);
        $product2 = Product::create(['name' => 'Prod 2', 'sku' => 'SKU2', 'status' => 'active', 'selling_price' => 10, 'purchase_price' => 5, 'slug' => 'prod-2', 'min_stock_level' => 5]);
        $product3 = Product::create(['name' => 'Prod 3', 'sku' => 'SKU3', 'status' => 'active', 'selling_price' => 10, 'purchase_price' => 5, 'slug' => 'prod-3', 'min_stock_level' => 5]);

        // Stock levels:
        // Prod 1: in_stock (Qty 20)
        Stock::create(['product_id' => $product1->id, 'warehouse_id' => $this->warehouse1->id, 'quantity' => 20, 'reserved_qty' => 0]);
        // Prod 2: low_stock (Qty 4)
        Stock::create(['product_id' => $product2->id, 'warehouse_id' => $this->warehouse1->id, 'quantity' => 4, 'reserved_qty' => 0]);
        // Prod 3: out_of_stock (Qty 0)
        Stock::create(['product_id' => $product3->id, 'warehouse_id' => $this->warehouse1->id, 'quantity' => 0, 'reserved_qty' => 0]);

        // 1. Filter: in_stock (scope to our test warehouse) - verify product1 appears
        $response = $this->getJson('/api/inventory/stocks?stock_level=in_stock&warehouse_id='.$this->warehouse1->id);
        $response->assertOk();
        $productIds = collect($response->json('data'))->pluck('product_id')->toArray();
        $this->assertContains($product1->id, $productIds, 'Product1 (qty=20) should appear in in_stock filter');

        // 2. Filter: low_stock - product2 (qty=4, threshold=5) should be here
        $response = $this->getJson('/api/inventory/stocks?stock_level=low_stock&warehouse_id='.$this->warehouse1->id);
        $response->assertOk();
        $this->assertContains(
            $product2->id,
            collect($response->json('data'))->pluck('product_id')->toArray(),
            'Product2 (qty=4) should appear in low_stock filter'
        );

        // 3. Filter: out_of_stock - product3 (qty=0) should be here
        $response = $this->getJson('/api/inventory/stocks?stock_level=out_of_stock&warehouse_id='.$this->warehouse1->id);
        $response->assertOk();
        $this->assertContains(
            $product3->id,
            collect($response->json('data'))->pluck('product_id')->toArray(),
            'Product3 (qty=0) should appear in out_of_stock filter'
        );
    }

    /**
     * Helper to create a user.
     */
    private function createUser(string $email): User
    {
        return User::create([
            'name' => 'Test Admin',
            'email' => $email,
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
    }
}
