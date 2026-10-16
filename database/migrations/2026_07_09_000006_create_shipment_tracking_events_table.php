<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_tracking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('event_name');
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->string('reschedule_reason')->nullable();
            $table->dateTime('occurred_at');
            $table->timestamps();
            $table->string('provider')->nullable()->index();
            $table->string('provider_event_code')->nullable()->index();
            $table->string('provider_office_id')->nullable();
            $table->text('provider_remarks')->nullable();
            $table->boolean('provider_is_rts')->nullable();
            $table->json('provider_payload')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_tracking_events');
    }
};
