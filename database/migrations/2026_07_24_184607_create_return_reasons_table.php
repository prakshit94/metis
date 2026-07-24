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
        Schema::create('return_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('reason');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insert default reasons
        DB::table('return_reasons')->insert([
            ['reason' => 'Damaged in transit', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['reason' => 'Defective product', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['reason' => 'Wrong item received', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['reason' => 'No longer needed', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['reason' => 'Other', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_reasons');
    }
};
