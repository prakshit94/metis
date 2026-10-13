<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipment_tracking_events', function (Blueprint $table) {
            $table->string('provider')->nullable()->index();
            $table->string('provider_event_code')->nullable()->index();
            $table->string('provider_office_id')->nullable();
            $table->text('provider_remarks')->nullable();
            $table->boolean('provider_is_rts')->nullable();
            $table->json('provider_payload')->nullable();
        });
        Schema::table('shipments', function (Blueprint $table) {
            $table->json('india_post_tracking_response')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('shipment_tracking_events', function (Blueprint $table) {
            $table->dropIndex(['provider']);
            $table->dropIndex(['provider_event_code']);
            $table->dropColumn([
                'provider',
                'provider_event_code',
                'provider_office_id',
                'provider_remarks',
                'provider_is_rts',
                'provider_payload',
            ]);
        });
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('india_post_tracking_response');
        });
    }
};
