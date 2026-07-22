<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->enum('type', ['fixed', 'percentage', 'free_shipping', 'free_product'])->default('fixed');
            $table->decimal('value', 15, 2)->default(0);
            $table->decimal('min_spend', 15, 2)->default(0);
            $table->decimal('max_discount', 15, 2)->nullable();
            
            // Advanced criteria
            $table->json('applicable_categories')->nullable();
            $table->json('applicable_products')->nullable();
            $table->json('excluded_categories')->nullable();
            $table->json('excluded_products')->nullable();
            
            // Free product info
            $table->foreignId('free_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->unsignedInteger('free_qty')->default(0);

            $table->date('expiry_date')->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('used_count')->default(0);
            $table->string('status')->default('active')->index();
            $table->boolean('is_active')->default(true);
            $table->foreignId("created_by")->nullable()->constrained("users")->nullOnDelete();
            $table->foreignId("updated_by")->nullable()->constrained("users")->nullOnDelete();
            $table->timestamps();
            $table->softDeletes()->index();

            $table->index('created_at');
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('coupons');
    }
};
