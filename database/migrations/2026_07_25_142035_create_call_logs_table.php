<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();
            $table->string('call_sid')->nullable()->index(); // Third-party telephony ID (e.g., Twilio/Exotel)
            $table->foreignId('customer_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('tag_l1_id')->nullable()->constrained('call_tags')->onDelete('set null');
            $table->foreignId('tag_l2_id')->nullable()->constrained('call_tags')->onDelete('set null');
            $table->foreignId('tag_l3_id')->nullable()->constrained('call_tags')->onDelete('set null');
            $table->integer('duration_seconds')->default(0); // Call duration
            $table->string('direction', 20)->default('inbound')->index(); // inbound, outbound
            $table->string('status', 30)->default('completed')->index(); // completed, missed, busy, failed
            $table->string('recording_url')->nullable(); // AWS S3 or provider URL
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_logs');
    }
};
