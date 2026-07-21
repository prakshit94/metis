<?php

namespace App\Http\Controllers\Api\Chat;

use App\Modules\Core\Controllers\Controller;
use App\Models\Chat\Conversation;
use App\Models\Chat\Member;
use App\Services\Chat\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GroupController extends Controller
{
    public function update(Request $request, Conversation $conversation, ChatService $chat)
    {
        abort_unless(config('chat.enabled'), 404);
        abort_unless($conversation->type === 'group', 404);

        $member = $chat->ensureMember($conversation, $request->user());
        abort_unless($member->canManageSettings(), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image_path' => ['nullable', 'string', 'max:1000'],
            'privacy' => ['nullable', 'in:public,private'],
            'settings' => ['nullable', 'array'],
        ]);

        $before = $conversation->only(array_keys($data));
        $conversation->update($data);
        $chat->audit($request->user(), 'group.updated', $conversation, null, $before, $conversation->only(array_keys($data)), $request);

        return response()->json(['data' => $conversation->fresh('activeMembers.user')]);
    }

    public function destroy(Request $request, Conversation $conversation, ChatService $chat)
    {
        abort_unless(config('chat.enabled'), 404);
        abort_unless($conversation->type === 'group', 404);

        $member = $chat->ensureMember($conversation, $request->user());
        abort_unless($member->role === 'owner', 403);

        $conversation->delete();
        $chat->audit($request->user(), 'group.deleted', $conversation, null, $conversation->only(['id', 'name']), null, $request);

        return response()->json(['message' => 'Group deleted.']);
    }

    public function addMember(Request $request, Conversation $conversation, ChatService $chat)
    {
        abort_unless(config('chat.enabled'), 404);
        abort_unless($conversation->type === 'group', 404);

        $actor = $chat->ensureMember($conversation, $request->user());
        abort_unless($actor->canManageMembers(), 403);

        $data = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', Rule::exists('users', 'id')->where('is_active', 1)->whereNull('deleted_at')],
            'role' => ['nullable', 'in:admin,moderator,member'],
        ]);
        if (($data['role'] ?? 'member') !== 'member') {
            abort_unless($actor->canManageSettings(), 403, 'Only owners and admins can add managers.');
        }

        $members = collect();
        foreach ($data['user_ids'] as $userId) {
            $member = $chat->attachMember($conversation, (int) $userId, $data['role'] ?? 'member');
            $chat->audit($request->user(), 'member.added', $conversation, null, null, $member->only(['user_id', 'role']), $request);
            $members->push($member->load('user'));
        }

        return response()->json(['data' => $members, 'conversation' => $conversation->fresh('activeMembers.user')], 201);
    }

    public function removeMember(Request $request, Conversation $conversation, ChatService $chat)
    {
        abort_unless(config('chat.enabled'), 404);
        abort_unless($conversation->type === 'group', 404);

        $data = $request->validate(['user_id' => ['required', 'integer', 'exists:users,id']]);
        $chat->removeMember($request->user(), $conversation, (int) $data['user_id']);

        return response()->json(['message' => 'Member removed.']);
    }

    public function updateRole(Request $request, Conversation $conversation, ChatService $chat)
    {
        abort_unless(config('chat.enabled'), 404);
        abort_unless($conversation->type === 'group', 404);

        $data = $request->validate(['user_id' => ['required', 'integer', 'exists:users,id'], 'role' => ['required', 'in:admin,moderator,member']]);

        return response()->json(['data' => $chat->updateRole($request->user(), $conversation, (int) $data['user_id'], $data['role']), 'conversation' => $conversation->fresh('activeMembers.user')]);
    }

    public function transferOwner(Request $request, Conversation $conversation, ChatService $chat)
    {
        abort_unless(config('chat.enabled'), 404);
        abort_unless($conversation->type === 'group', 404);

        $member = $chat->ensureMember($conversation, $request->user());
        abort_unless($member->role === 'owner', 403);

        $data = $request->validate(['user_id' => ['required', 'integer', 'exists:users,id']]);
        abort_unless($conversation->activeMembers()->where('user_id', $data['user_id'])->exists(), 422, 'New owner must be an active group member.');

        $newOwner = DB::transaction(function () use ($chat, $conversation, $data, $request) {
            $member = $chat->updateRole($request->user(), $conversation, (int) $data['user_id'], 'owner');

            Member::where('conversation_id', $conversation->id)
                ->where('user_id', $request->user()->id)
                ->update(['role' => 'admin']);

            $conversation->update(['owner_id' => $data['user_id']]);

            return $member;
        });

        return response()->json(['data' => $newOwner, 'conversation' => $conversation->fresh('activeMembers.user')]);
    }

    public function leave(Request $request, Conversation $conversation, ChatService $chat)
    {
        abort_unless(config('chat.enabled'), 404);
        abort_unless($conversation->type === 'group', 404);

        $chat->removeMember($request->user(), $conversation, $request->user()->id);

        return response()->json(['message' => 'You left the group.']);
    }
}
