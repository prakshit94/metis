<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no')->unique();
            $table->enum('type', ['sale', 'purchase'])->index();
            $table->unsignedBigInteger('party_id')->nullable();
            $table->dateTime('order_date');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->string('coupon_code')->nullable();
            $table->foreignId('applied_offer_id')->nullable()->constrained('offers')->nullOnDelete();
            $table->decimal('net_amount', 15, 2)->default(0);
            $table->enum('status', ['pending', 'confirmed', 'processing', 'ready_to_ship', 'dispatched', 'shipped', 'delivered', 'cancelled', 'returned', 'return_requested'])->default('pending')->index();
            $table->boolean('is_draft')->default(false)->index();
            $table->date('future_order_date')->nullable();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();

            $table->unsignedBigInteger('shipping_address_id')->nullable();
            $table->string('shipping_address_line_1')->nullable();
            $table->string('shipping_address_line_2')->nullable();
            $table->foreignId('shipping_village_id')->nullable()->constrained('villages')->nullOnDelete();
            $table->string('shipping_village_name')->nullable();
            $table->string('shipping_post_office')->nullable();
            $table->string('shipping_taluka')->nullable();
            $table->string('shipping_district')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_state')->nullable();
            $table->string('shipping_pincode')->nullable();

            $table->unsignedBigInteger('billing_address_id')->nullable();
            $table->string('billing_address_line_1')->nullable();
            $table->string('billing_address_line_2')->nullable();
            $table->foreignId('billing_village_id')->nullable()->constrained('villages')->nullOnDelete();
            $table->string('billing_village_name')->nullable();
            $table->string('billing_post_office')->nullable();
            $table->string('billing_taluka')->nullable();
            $table->string('billing_district')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_state')->nullable();
            $table->string('billing_pincode')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes()->index();

            // Optimization for high data load
            $table->index('created_at');
            $table->index(['status', 'created_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->timestamps();

            $table->index(['order_id', 'product_id'], 'idx_order_items_lookup');
        });
    }

    public function down(): void {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
