<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('future_order', 'pending', 'pending_confirmation', 'confirmed', 'processing', 'ready_to_ship', 'dispatched', 'shipped', 'delivered', 'cancelled', 'returned', 'return_requested') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'pending_confirmation', 'confirmed', 'processing', 'ready_to_ship', 'dispatched', 'shipped', 'delivered', 'cancelled', 'returned', 'return_requested') NOT NULL DEFAULT 'pending'");
    }
};
