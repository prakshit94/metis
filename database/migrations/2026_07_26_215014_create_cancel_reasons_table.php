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
        Schema::create('cancel_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('reason');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // Insert default reasons
        DB::table('cancel_reasons')->insert([
            ['reason' => 'Customer Requested Cancel', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['reason' => 'Out of Stock', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['reason' => 'Duplicate Order', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['reason' => 'Payment Failed', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['reason' => 'Invalid Address', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['reason' => 'Other', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cancel_reasons');
    }
};
