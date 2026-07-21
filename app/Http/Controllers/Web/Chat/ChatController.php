<?php

namespace App\Http\Controllers\Web\Chat;

use App\Modules\Core\Controllers\Controller;
use App\Services\Chat\ChatService;

class ChatController extends Controller
{
    public function __invoke(ChatService $chat)
    {
        abort_unless(config('chat.enabled'), 404);

        $user = auth()->user();

        return view('chat.index', [
            'conversations' => $chat->listConversations($user),
            'users' => $chat->listUsersWithPresence($user),
            'pollInterval' => config('chat.realtime.poll_interval_ms'),
        ]);
    }
}
