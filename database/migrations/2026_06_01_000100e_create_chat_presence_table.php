<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_presence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('offline')->index();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->foreignId('typing_conversation_id')->nullable()->constrained('chat_conversations')->nullOnDelete();
            $table->timestamp('typing_expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_presence');
    }
};
