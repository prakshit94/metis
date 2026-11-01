<?php

namespace App\Http\Controllers\Api\Chat;

use App\Models\Chat\Conversation;
use App\Models\Chat\Presence;
use App\Modules\Core\Controllers\Controller;
use App\Services\Chat\ChatService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;

class PresenceController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:chat-view'),
        ];
    }

    public function index(Request $request)
    {
        abort_unless(config('chat.enabled'), 404);

        $visible = Conversation::visibleTo($request->user())->pluck('id')->all();
        $presences = Presence::query()->select('id', 'user_id', 'status', 'last_seen_at', 'typing_conversation_id', 'typing_expires_at')
            ->with('user:id,name,photo')->whereHas('user', fn ($query) => $query->where('is_active', 1))
            ->where('last_seen_at', '>=', now()->subMinutes(10))->latest('last_seen_at')->get();
        foreach ($presences as $presence) {
            if (! in_array($presence->typing_conversation_id, $visible) || ! $presence->typing_expires_at?->isFuture()) {
                $presence->typing_conversation_id = null;
                $presence->typing_expires_at = null;
            }
        }

        return response()->json(['data' => $presences]);
    }

    public function update(Request $request, ChatService $chat)
    {
        abort_unless(config('chat.enabled'), 404);

        $data = $request->validate([
            'status' => ['nullable', 'in:online,offline,away,busy'],
            'typing_conversation_id' => ['nullable', 'integer', Rule::exists('chat_members', 'conversation_id')->where('user_id', $request->user()->id)->where('status', 'active')->whereNull('left_at')],
            'metadata' => ['nullable', 'array'],
        ]);

        if (! empty($data['typing_conversation_id'])) {
            $chat->ensureMember(Conversation::findOrFail($data['typing_conversation_id']), $request->user());
        }

        return response()->json(['data' => $chat->updatePresence($request->user(), $data)]);
    }
}
