<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_log_metas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('call_log_id')->constrained()->onDelete('cascade');
            $table->string('key')->index(); // Index for querying specific meta values across logs
            $table->longText('value')->nullable(); // Use longText instead of text for large JSON payloads
            $table->timestamps();

            $table->unique(['call_log_id', 'key']); // Prevent duplicate keys per log
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_log_metas');
    }
};
