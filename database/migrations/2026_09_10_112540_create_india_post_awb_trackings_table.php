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
        Schema::create('india_post_awb_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('range_id')->nullable()->constrained('india_post_barcode_ranges')->nullOnDelete();
            $table->string('office_id')->index(); // The JSON settings office ID
            $table->string('barcode')->unique();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->string('status')->default('used');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('india_post_awb_trackings');
    }
};
