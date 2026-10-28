<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipment_tracking_events', fn (Blueprint $table) => $table->string('provider_event_key', 64)->nullable()->unique());
    }

    public function down(): void
    {
        Schema::table('shipment_tracking_events', function (Blueprint $table) {
            $table->dropUnique(['provider_event_key']);
            $table->dropColumn('provider_event_key');
        });
    }
};
