<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasIndex('chat_messages', ['conversation_id', 'deleted_at', 'created_at', 'id'])) {
            Schema::table('chat_messages', fn (Blueprint $table) => $table->index(
                ['conversation_id', 'deleted_at', 'created_at', 'id'], 'chat_messages_history_cursor_index'
            ));
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('chat_messages', 'chat_messages_history_cursor_index')) {
            Schema::table('chat_messages', fn (Blueprint $table) => $table->dropIndex('chat_messages_history_cursor_index'));
        }
    }
};
