<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('shipment_no')->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('carrier_name')->nullable();
            $table->string('tracking_no')->nullable()->index();
            $table->enum('status', ['pending', 'shipped', 'in_transit', 'delivered', 'failed', 'returned', 'cancelled'])->default('pending')->index();
            $table->dateTime('shipped_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->string('delivered_by')->nullable();
            $table->integer('delivery_attempts')->default(0);
            $table->timestamp('next_followup_date')->nullable();
            $table->string('reschedule_reason')->nullable();
            $table->timestamps();
            $table->softDeletes()->index();

            $table->index('created_at');
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('shipments');
    }
};
