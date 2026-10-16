<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('chat_messages')->nullOnDelete();
            $table->foreignId('forwarded_from_id')->nullable()->constrained('chat_messages')->nullOnDelete();
            $table->string('type', 30)->default('text')->index();
            $table->longText('content')->nullable();
            $table->json('attachments')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes()->index();

            $table->index(['conversation_id', 'created_at']);
            $table->index(['sender_id', 'created_at']);
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->fullText('content');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
