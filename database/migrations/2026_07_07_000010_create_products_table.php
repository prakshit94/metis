<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('sku')->unique();
            $table->string('slug')->unique();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->foreignId('hsn_code_id')->nullable()->constrained('hsn_codes')->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('units_of_measure')->nullOnDelete();
            $table->foreignId('default_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('barcode')->nullable()->index();
            $table->string('image_path')->nullable();
            $table->string('weight')->nullable();
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->decimal('mrp', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->decimal('default_discount', 5, 2)->default(0);
            $table->string('default_discount_type')->default('percent');
            $table->integer('min_stock_level')->default(0);
            $table->boolean('batch_tracking')->default(false);
            $table->boolean('expiry_tracking')->default(false);
            $table->boolean('allow_overselling')->default(false);
            $table->integer('overselling_qty')->default(0);
            $table->boolean('manage_stock')->default(true);
            $table->boolean('is_sku_enabled')->default(true);
            $table->boolean('is_active')->default(true);
            $table->text('application_instructions')->nullable();
            $table->string('status')->default('published')->index();
            $table->string('grade', 10)->nullable()->index();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
            $table->index(['category_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
