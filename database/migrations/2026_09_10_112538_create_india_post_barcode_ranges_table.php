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
        Schema::create('india_post_barcode_ranges', function (Blueprint $table) {
            $table->id();
            $table->string('office_id')->index();
            $table->string('prefix', 10);
            $table->bigInteger('start_sequence');
            $table->bigInteger('end_sequence');
            $table->bigInteger('current_sequence');
            $table->enum('status', ['active', 'queued', 'exhausted', 'archived'])->default('queued');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('india_post_barcode_ranges');
    }
};
