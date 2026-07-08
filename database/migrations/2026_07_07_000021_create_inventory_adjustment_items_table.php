<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('inventory_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adjustment_id')->constrained('inventory_adjustments')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('current_qty', 15, 4);
            $table->decimal('new_qty', 15, 4);
            $table->decimal('difference', 15, 4);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('inventory_adjustment_items');
    }
};
