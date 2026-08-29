<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

            $table->index(['pincode', 'normalized_name'], 'idx_pincode_village');
            $table->index(['district_name', 'state_name'], 'idx_district_state');

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('village_id')->references('id')->on('villages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('villages');
    }
};
