<?php

namespace App\Http\Controllers\Api\Chat;

use App\Models\Chat\Conversation;
use App\Models\Chat\Message;
use App\Modules\Core\Controllers\Controller;
use App\Services\Chat\ChatService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;

class ConversationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:chat-view'),
            new Middleware('permission:chat-create', only: ['store']),
        ];
    }

    public function index(Request $request, ChatService $chat)
    {
        abort_unless(config('chat.enabled'), 404);

        $request->validate(['page' => 'sometimes|integer|min:1', 'per_page' => 'sometimes|integer|min:1|max:100']);
        $perPage = $request->integer('per_page', 50);
        $page = $request->integer('page', 1);
        $total = Conversation::visibleTo($request->user())->count();
        $unread = Message::whereHas('conversation.activeMembers', fn ($q) => $q->where('user_id', $request->user()->id))
            ->where('sender_id', '!=', $request->user()->id)
            ->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $request->user()->id))->count();

        return response()->json(['data' => $chat->listConversations($request->user(), $perPage, $page),
            'meta' => ['current_page' => $page, 'per_page' => $perPage, 'total' => $total,
                'has_more' => $page * $perPage < $total, 'unread_messages' => $unread]]);
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
            'member_ids' => ['nullable', 'array', 'max:500'],
            'member_ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')->where('is_active', 1)->whereNull('deleted_at')],
        ]);

        abort_if($data['type'] === 'group' && ! $request->user()->can('chat-create-group'), 403);
        $conversation = $data['type'] === 'direct'
            ? $chat->createDirectConversation($request->user(), (int) $data['user_id'])
            : $chat->createGroup($request->user(), $data);

        $member = $conversation->activeMembers->firstWhere('user_id', $request->user()->id);
        $conversation->is_pinned = $member ? ! is_null($member->pinned_at) : false;
        $conversation->is_archived = $member ? ! is_null($member->archived_at) : false;

        return response()->json(['data' => $conversation], 201);
    }

    public function show(Request $request, Conversation $conversation, ChatService $chat)
    {
        abort_unless(config('chat.enabled'), 404);

        $chat->ensureMember($conversation, $request->user());

        $conversation->load(['activeMembers.user:id,name,email,photo', 'owner:id,name']);

        $member = $conversation->activeMembers->firstWhere('user_id', $request->user()->id);
        $conversation->is_pinned = $member ? ! is_null($member->pinned_at) : false;
        $conversation->is_archived = $member ? ! is_null($member->archived_at) : false;

        return response()->json([
            'data' => $conversation,
        ]);
    }

    public function messages(Request $request, Conversation $conversation, ChatService $chat)
    {
        abort_unless(config('chat.enabled'), 404);
        abort_unless($request->user()->can('messages-view'), 403);
        $messages = $chat->messages($request->user(), $conversation, 50);

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

    public function unreadCount(Request $request)
    {
        abort_unless(config('chat.enabled'), 404);

        return response()->json(app(ChatService::class)->unreadCounts($request->user()));
    }
}
