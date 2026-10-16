<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 20)->default('member')->index();
            $table->string('status', 20)->default('active')->index();
            $table->unsignedBigInteger('last_read_message_id')->nullable()->index();
            $table->timestamp('muted_until')->nullable();
            $table->timestamp('archived_at')->nullable()->index();
            $table->timestamp('pinned_at')->nullable()->index();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->string('location_visibility', 30)->default('contacts_only');
            $table->json('notification_preferences')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id']);
            $table->index(['user_id', 'status', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_members');
    }
};
