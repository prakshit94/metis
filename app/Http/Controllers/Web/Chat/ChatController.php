<?php

namespace App\Http\Controllers\Web\Chat;

use App\Modules\Core\Controllers\Controller;
use App\Services\Chat\ChatService;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ChatController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:chat-view'),
        ];
    }

    public function __invoke(ChatService $chat)
    {
        abort_unless(config('chat.enabled'), 404);

        $user = auth()->user();

        return view('chat.index', [
            'conversations' => $chat->listConversations($user, 50),
            'users' => $chat->listUsersWithPresence($user),
            'currentUserData' => $chat->listUsersWithPresence($user, null, includeSelf: true)
                ->firstWhere('id', $user->id) ?? [],
            'pollInterval' => config('chat.realtime.poll_interval_ms'),
        ]);
    }
}
