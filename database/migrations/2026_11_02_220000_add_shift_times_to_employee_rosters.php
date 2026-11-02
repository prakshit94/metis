<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_rosters', function (Blueprint $table) {
            $table->time('shift_start')->nullable();
            $table->time('shift_end')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('employee_rosters', fn (Blueprint $table) => $table->dropColumn(['shift_start', 'shift_end']));
    }
};
