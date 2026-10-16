<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->nullable();
            // Must match orders.shipping_state and villages.state_name values.
            $table->string('state_name')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed the existing teams with their correct state names
        $map = [
            'GJ' => 'Gujarat',
            'RJ' => 'Rajasthan',
            'MH' => 'Maharastra', // matches actual village spelling in DB
            'MP' => 'Madhya Pradesh',
        ];

        foreach ($map as $code => $stateName) {
            DB::table('teams')
                ->where('code', $code)
                ->update(['state_name' => $stateName]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
