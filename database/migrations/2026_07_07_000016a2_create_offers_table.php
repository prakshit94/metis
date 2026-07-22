<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['order_discount', 'bogo', 'category_discount', 'free_product']);
            $table->enum('discount_type', ['fixed', 'percentage'])->nullable();
            $table->decimal('value', 15, 2)->default(0);
            $table->decimal('min_spend', 15, 2)->default(0);
            $table->decimal('max_discount', 15, 2)->nullable();
            
            // Advanced criteria
            $table->json('applicable_categories')->nullable();
            $table->json('applicable_products')->nullable();
            $table->json('excluded_categories')->nullable();
            $table->json('excluded_products')->nullable();
            
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('buy_qty')->default(1);
            $table->unsignedInteger('get_qty')->default(1);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('used_count')->default(0);
            $table->foreignId("created_by")->nullable()->constrained("users")->nullOnDelete();
            $table->foreignId("updated_by")->nullable()->constrained("users")->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'is_active', 'priority']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('offers');
    }
};
