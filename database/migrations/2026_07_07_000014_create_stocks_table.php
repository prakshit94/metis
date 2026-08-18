<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('reserved_qty', 15, 4)->default(0);
            $table->decimal('dispatched_qty', 15, 4)->default(0);
            $table->decimal('committed_qty', 15, 4)->default(0);
            $table->decimal('in_transit_qty', 15, 4)->default(0);
            $table->decimal('damaged_qty', 15, 4)->default(0);
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->softDeletes()->index();

            // One row per product per warehouse
            $table->unique(['product_id', 'warehouse_id'], 'stocks_product_warehouse_unique');
            $table->index(['product_id', 'warehouse_id'], 'idx_stocks_product_warehouse');

            // Optimization for high data load
            $table->index('created_at');
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('stocks');
    }
};
