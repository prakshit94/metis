<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('village_service_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_available')->default(true)->index();
            $table->date('serviceable_from_date')->nullable();
            $table->date('serviceable_to_date')->nullable();
            $table->string('remarks', 500)->nullable();
            $table->integer('priority')->unsigned()->nullable();
            $table->timestamps();

            $table->unique(['village_id', 'service_id'], 'unique_village_service');
            $table->index(['service_id', 'is_available'], 'idx_service_availability');
            $table->index(['village_id', 'is_available'], 'idx_village_availability');
            $table->index(['service_id', 'village_id'], 'idx_service_village_lookup');
            $table->index(['service_id', 'priority'], 'idx_service_priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('village_service_mappings');
    }
};
