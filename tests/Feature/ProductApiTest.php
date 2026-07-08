<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (($_ENV['DB_CONNECTION'] ?? null) === 'sqlite' && ! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('The sqlite PDO driver is not available in this PHP runtime.');
        }

        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'product-view',
            'product-create',
            'product-edit',
            'product-delete',
            'product-restore',
            'product-permanent-delete',
            'product-import',
            'product-export',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superAdminRole = Role::findOrCreate('Super Admin', 'web');
        $superAdminRole->syncPermissions($permissions);

        $admin = $this->createUser('admin@example.com');
        $admin->assignRole('Super Admin');

        $this->actingAs($admin);
    }

    public function test_index_returns_products(): void
    {
        $category = Category::create(['name' => 'Test Cat', 'slug' => 'test-cat']);
        for ($i = 1; $i <= 3; $i++) {
            Product::create([
                'name' => "Product $i",
                'sku' => "SKU-$i",
                'slug' => "product-$i",
                'category_id' => $category->id,
                'selling_price' => 10.00,
                'purchase_price' => 5.00,
                'status' => 'published',
            ]);
        }

        $response = $this->getJson('/api/products');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'name', 'sku', 'category', 'price', 'stock', 'status'
                    ]
                ],
                'stats',
                'options'
            ]);
    }

    public function test_store_creates_product(): void
    {
        $category = Category::create(['name' => 'Test Cat', 'slug' => 'test-cat-store']);

        $response = $this->postJson('/api/products', [
            'name' => 'New Awesome Product',
            'sku' => 'AWESOME-123',
            'category_id' => $category->id,
            'purchase_price' => 10.00,
            'selling_price' => 15.00,
            'status' => 'published',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'New Awesome Product')
            ->assertJsonPath('data.sku', 'AWESOME-123');

        $this->assertDatabaseHas('products', [
            'name' => 'New Awesome Product',
            'sku' => 'AWESOME-123',
            'selling_price' => 15.00,
        ]);
    }

    public function test_update_modifies_product(): void
    {
        $category = Category::create(['name' => 'Test Cat', 'slug' => 'test-cat-update']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Old Name',
            'sku' => 'OLD-SKU',
            'slug' => 'old-name',
            'purchase_price' => 10.00,
            'selling_price' => 15.00,
            'status' => 'published',
        ]);

        $response = $this->patchJson('/api/products/' . $product->id, [
            'name' => 'New Name',
            'sku' => 'OLD-SKU', // keep same SKU
            'category_id' => $category->id,
            'purchase_price' => 10.00,
            'selling_price' => 20.00,
            'status' => 'published',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'New Name',
            'selling_price' => 20.00,
        ]);
    }

    public function test_destroy_soft_deletes_product(): void
    {
        $category = Category::create(['name' => 'Test Cat', 'slug' => 'test-cat-delete']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Product to Delete',
            'sku' => 'DEL-SKU',
            'slug' => 'del-sku',
            'purchase_price' => 10.00,
            'selling_price' => 15.00,
            'status' => 'published',
        ]);

        $response = $this->deleteJson('/api/products/' . $product->id);

        $response->assertOk();

        $this->assertSoftDeleted('products', [
            'id' => $product->id,
        ]);
    }

    public function test_bulk_status_updates_multiple_products(): void
    {
        $category = Category::create(['name' => 'Test Cat', 'slug' => 'test-cat-bulk-status']);
        $product1 = Product::create([
            'category_id' => $category->id,
            'name' => 'P1',
            'sku' => 'SKU-P1',
            'slug' => 'sku-p1',
            'purchase_price' => 10.00,
            'selling_price' => 15.00,
            'status' => 'draft',
        ]);
        $product2 = Product::create([
            'category_id' => $category->id,
            'name' => 'P2',
            'sku' => 'SKU-P2',
            'slug' => 'sku-p2',
            'purchase_price' => 10.00,
            'selling_price' => 15.00,
            'status' => 'draft',
        ]);

        $response = $this->postJson('/api/products/bulk-status', [
            'ids' => [$product1->id, $product2->id],
            'status' => 'published',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('products', [
            'id' => $product1->id,
            'status' => 'published',
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $product2->id,
            'status' => 'published',
        ]);
    }

    public function test_bulk_delete_soft_deletes_multiple_products(): void
    {
        $category = Category::create(['name' => 'Test Cat', 'slug' => 'test-cat-bulk-del']);
        $product1 = Product::create([
            'category_id' => $category->id,
            'name' => 'P3',
            'sku' => 'SKU-P3',
            'slug' => 'sku-p3',
            'purchase_price' => 10.00,
            'selling_price' => 15.00,
            'status' => 'draft',
        ]);
        $product2 = Product::create([
            'category_id' => $category->id,
            'name' => 'P4',
            'sku' => 'SKU-P4',
            'slug' => 'sku-p4',
            'purchase_price' => 10.00,
            'selling_price' => 15.00,
            'status' => 'draft',
        ]);

        $response = $this->postJson('/api/products/bulk-delete', [
            'ids' => [$product1->id, $product2->id],
        ]);

        $response->assertOk();

        $this->assertSoftDeleted('products', ['id' => $product1->id]);
        $this->assertSoftDeleted('products', ['id' => $product2->id]);
    }

    public function test_import_products_with_fallback_and_dynamic_categories(): void
    {
        // Ensure a warehouse exists so the fallback can trigger
        \App\Models\Warehouse::create(['name' => 'First Main Warehouse', 'code' => 'WH01', 'is_active' => true]);

        // CSV data with missing categories and missing default_warehouse_id but having stock
        $csvContent = "Name,SKU,Category,Selling_Price,Stock\n" .
                      "Imported Product 1,IMP-001,Newly Created Category,25.50,150\n" .
                      "Imported Product 2,IMP-002,,19.99,80\n";

        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('products.csv', $csvContent);

        $response = $this->postJson('/api/products/import', [
            'file' => $file,
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Imported 2 products successfully.');

        // Assert dynamic categories were created correctly
        $this->assertDatabaseHas('categories', ['name' => 'Newly Created Category', 'slug' => 'newly-created-category']);
        $this->assertDatabaseHas('categories', ['name' => 'Uncategorized', 'slug' => 'uncategorized']);

        // Assert products were imported
        $this->assertDatabaseHas('products', [
            'sku' => 'IMP-001',
            'name' => 'Imported Product 1',
            'selling_price' => 25.50,
        ]);

        $this->assertDatabaseHas('products', [
            'sku' => 'IMP-002',
            'name' => 'Imported Product 2',
            'selling_price' => 19.99,
        ]);

        // Assert warehouse fallback assigned stock correctly
        $product1 = Product::where('sku', 'IMP-001')->first();
        $this->assertNotNull($product1->default_warehouse_id);
        $this->assertDatabaseHas('stocks', [
            'product_id' => $product1->id,
            'warehouse_id' => $product1->default_warehouse_id,
            'quantity' => 150,
        ]);
    }

    public function test_user_without_permissions_is_forbidden(): void
    {
        $unauthorizedUser = $this->createUser('guest@example.com');
        $this->actingAs($unauthorizedUser);

        $response = $this->getJson('/api/products');
        $response->assertForbidden();

        $response = $this->postJson('/api/products', []);
        $response->assertForbidden();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createUser(string $email, array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => 'User ' . $email,
            'email' => $email,
            'password' => Hash::make('Password123'),
            'is_active' => true,
        ], $attributes));
    }
}
