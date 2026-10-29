<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing CRM identities remain unchanged; public credentials live separately.
        Schema::create('customer_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->unique()->constrained('parties')->restrictOnDelete();
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('login_enabled')->default(false);
            $table->boolean('ordering_enabled')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::table('products', function (Blueprint $table) {
            // Publication is explicit: never expose the existing private catalog by default.
            $table->boolean('storefront_visible')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->unsignedSmallInteger('min_order_qty')->default(1);
            $table->unsignedSmallInteger('max_order_qty')->default(1000);
            $table->index(['storefront_visible', 'is_active', 'status', 'id'], 'products_storefront_catalog');
        });
        Schema::create('storefront_checkouts', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_key')->unique();
            $table->foreignId('customer_account_id')->constrained('customer_accounts')->restrictOnDelete();
            $table->foreignId('order_id')->unique()->constrained('orders')->restrictOnDelete();
            $table->string('contact_name', 150);
            $table->string('contact_phone', 20);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (DB::table('customer_accounts')->exists() || DB::table('storefront_checkouts')->exists()) {
            throw new RuntimeException('Storefront accounts/orders exist. Archive them before removing storefront access.');
        }
        Schema::dropIfExists('storefront_checkouts');
        Schema::dropIfExists('customer_accounts');
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_storefront_catalog');
            $table->dropColumn(['storefront_visible', 'is_featured', 'min_order_qty', 'max_order_qty']);
        });
    }
};
