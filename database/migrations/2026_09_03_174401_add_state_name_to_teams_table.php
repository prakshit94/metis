<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add state_name to teams so the code (GJ) can be mapped to the full
     * state name used in orders.shipping_state and villages.state_name.
     *
     * Also adds state_names (JSON) to support teams that span multiple states
     * in the future without a schema change.
     */
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            // Primary state name — must match orders.shipping_state values exactly
            $table->string('state_name')->nullable()->after('code');
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

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('state_name');
        });
    }
};
