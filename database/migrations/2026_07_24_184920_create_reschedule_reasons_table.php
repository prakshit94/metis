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
        Schema::create('reschedule_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('reason');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // Insert default reasons
        DB::table('reschedule_reasons')->insert([
            ['reason' => 'Customer Not Reachable', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['reason' => 'Waiting for Payment Confirmation', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['reason' => 'Customer Requested Delay', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['reason' => 'Stock Verification Pending', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['reason' => 'Other', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reschedule_reasons');
    }
};
