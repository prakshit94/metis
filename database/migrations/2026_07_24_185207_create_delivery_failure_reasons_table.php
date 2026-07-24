<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('delivery_failure_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('reason');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insert default reasons
        DB::table('delivery_failure_reasons')->insert([
            ['reason' => 'Customer unavailable', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['reason' => 'Customer requested future delivery', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['reason' => 'Address issue/Incomplete', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['reason' => 'Out of delivery area/Time limit', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['reason' => 'Consignee refused to accept', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['reason' => 'Other', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_failure_reasons');
    }
};
