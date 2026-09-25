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
        // Replace legacy 'shipped' statuses with modern 'dispatched' in orders
        DB::table('orders')
            ->where('status', 'shipped')
            ->update(['status' => 'dispatched']);

        // Replace 'shipped' in status logs
        DB::table('order_status_logs')
            ->where('status', 'shipped')
            ->update(['status' => 'dispatched']);

        // Replace legacy 'shipped' statuses with modern 'in_transit' in shipments
        DB::table('shipments')
            ->where('status', 'shipped')
            ->update(['status' => 'in_transit']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down migration as 'shipped' is deprecated.
    }
};
