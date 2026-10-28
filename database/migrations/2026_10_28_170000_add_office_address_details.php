<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->unsignedBigInteger('village_id')->nullable();
            $table->string('village_name')->nullable();
            $table->string('post_office')->nullable();
            $table->string('taluka')->nullable();
            $table->string('district')->nullable();
            $table->string('state')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('offices', fn (Blueprint $table) => $table->dropColumn([
            'address_line_1', 'address_line_2', 'village_id', 'village_name', 'post_office', 'taluka', 'district', 'state',
        ]));
    }
};
