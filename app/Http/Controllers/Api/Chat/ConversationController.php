<?php

namespace App\Http\Controllers\Api\Chat;

use App\Models\Chat\Conversation;
use App\Modules\Core\Controllers\Controller;
use App\Services\Chat\ChatService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConversationController extends Controller
{
    public function index(Request $request, ChatService $chat)
    {
        abort_unless(config('chat.enabled'), 404);

        return response()->json(['data' => $chat->listConversations($request->user())]);
    }

    public function store(Request $request, ChatService $chat)
    {
        abort_unless(config('chat.enabled'), 404);

        $data = $request->validate([
            'type' => ['required', 'in:direct,group'],
            'user_id' => ['required_if:type,direct', 'integer', Rule::exists('users', 'id')->where('is_active', 1)->whereNull('deleted_at')],
            'name' => ['required_if:type,group', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image_path' => ['nullable', 'string', 'max:1000'],
            'privacy' => ['nullable', 'in:public,private'],
            'settings' => ['nullable', 'array'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['integer', Rule::exists('users', 'id')->where('is_active', 1)->whereNull('deleted_at')],
        ]);

        $conversation = $data['type'] === 'direct'
            ? $chat->createDirectConversation($request->user(), (int) $data['user_id'])
            : $chat->createGroup($request->user(), $data);

        return response()->json(['data' => $conversation], 201);
    }

    public function show(Request $request, Conversation $conversation, ChatService $chat)
    {
        abort_unless(config('chat.enabled'), 404);

        $chat->ensureMember($conversation, $request->user());

        return response()->json([
            'data' => $conversation->load(['activeMembers.user:id,name,email,photo', 'owner:id,name']),
        ]);
    }

    public function messages(Request $request, Conversation $conversation, ChatService $chat)
    {
        abort_unless(config('chat.enabled'), 404);
        $chat->ensureMember($conversation, $request->user());

        $messages = $conversation->messages()
            ->with(['sender', 'reads', 'parent'])
            ->paginate(50);

        return response()->json(['data' => $messages]);
    }

    public function archive(Request $request, Conversation $conversation, ChatService $chat)
    {
        abort_unless(config('chat.enabled'), 404);

        $chat->ensureMember($conversation, $request->user());

        $conversation->members()
            ->where('user_id', $request->user()->id)
            ->update(['archived_at' => $request->boolean('archived', true) ? now() : null]);

        return response()->json(['message' => 'Archive preference updated.']);
    }

    public function pin(Request $request, Conversation $conversation, ChatService $chat)
    {
        abort_unless(config('chat.enabled'), 404);

        $chat->ensureMember($conversation, $request->user());

        $conversation->members()
            ->where('user_id', $request->user()->id)
            ->update(['pinned_at' => $request->boolean('pinned', true) ? now() : null]);

        return response()->json(['message' => 'Pin preference updated.']);
    }
}
