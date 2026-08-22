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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('party_id')->references('id')->on('parties')->nullOnDelete();
            $table->foreign('shipping_address_id')->references('id')->on('party_addresses')->nullOnDelete();
            $table->foreign('billing_address_id')->references('id')->on('party_addresses')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['party_id']);
            $table->dropForeign(['shipping_address_id']);
            $table->dropForeign(['billing_address_id']);
        });
    }
};
