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
        // 1. VILLAGES TABLE
        Schema::create('villages', function (Blueprint $table) {
            $table->id();
            $table->string('village_name');
            $table->string('normalized_name')->index();
            $table->string('pincode', 10)->index();
            $table->string('post_so_name')->nullable();
            $table->string('taluka_name')->nullable();
            $table->string('district_name')->nullable()->index();
            $table->string('state_name')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Composite Indexes for MySQL 8 Optimization
            $table->index(['pincode', 'normalized_name'], 'idx_pincode_village');
            $table->index(['district_name', 'state_name'], 'idx_district_state');
            
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });

        // 2. SERVICES TABLE
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        // 3. VILLAGE SERVICE MAPPINGS (Pivot Model)
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

            // Unique Constraint to prevent duplicates
            $table->unique(['village_id', 'service_id'], 'unique_village_service');

            // High-Performance Composite Indexes
            $table->index(['service_id', 'is_available'], 'idx_service_availability');
            $table->index(['village_id', 'is_available'], 'idx_village_availability');
            $table->index(['service_id', 'village_id'], 'idx_service_village_lookup');
            $table->index(['service_id', 'priority'], 'idx_service_priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('village_service_mappings');
        Schema::dropIfExists('services');
        Schema::dropIfExists('villages');
    }
};
