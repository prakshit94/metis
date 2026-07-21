<?php

namespace App\Http\Controllers\Api\Chat;

use App\Modules\Core\Controllers\Controller;
use App\Models\Chat\Presence;
use App\Services\Chat\ChatService;
use Illuminate\Http\Request;

class PresenceController extends Controller
{
    public function index()
    {
        abort_unless(config('chat.enabled'), 404);

        return response()->json([
            'data' => Presence::query()
                ->with('user:id,name,photo')
                ->where('last_seen_at', '>=', now()->subMinutes(10))
                ->latest('last_seen_at')
                ->get(),
        ]);
    }

    public function update(Request $request, ChatService $chat)
    {
        abort_unless(config('chat.enabled'), 404);

        $data = $request->validate([
            'status' => ['nullable', 'in:online,offline,away,busy'],
            'typing_conversation_id' => ['nullable', 'integer', 'exists:chat_conversations,id'],
            'metadata' => ['nullable', 'array'],
        ]);

        return response()->json(['data' => $chat->updatePresence($request->user(), $data)]);
    }
}
