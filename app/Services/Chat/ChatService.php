<?php

namespace App\Services\Chat;

use App\Jobs\Chat\ProcessMessageDeliveries;
use App\Models\Chat\AuditLog;
use App\Models\Chat\Conversation;
use App\Models\Chat\Member;
use App\Models\Chat\Message;
use App\Models\Chat\MessageRead;
use App\Models\Chat\Presence;
use App\Modules\Users\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChatService
{
    private const ONLINE_WINDOW_MINUTES = 5;

    public function listConversations(User $user): Collection
    {
        return Conversation::query()
            ->visibleTo($user)
            ->with([
                'activeMembers.user:id,name,first_name,last_name,email,photo',
                'messages' => fn ($query) => $query->with('sender:id,name')->limit(1),
            ])
            ->withCount([
                'messages as unread_count' => fn ($query) => $query
                    ->whereDoesntHave('reads', fn ($read) => $read->where('user_id', $user->id))
                    ->where('sender_id', '!=', $user->id),
            ])
            ->orderByDesc(
                Member::query()
                    ->select('pinned_at')
                    ->whereColumn('chat_members.conversation_id', 'chat_conversations.id')
                    ->where('user_id', $user->id)
                    ->limit(1)
            )
            ->latest('updated_at')
            ->get();
    }

    public function createDirectConversation(User $creator, int $recipientId): Conversation
    {
        if ($creator->id === $recipientId) {
            throw ValidationException::withMessages(['user_id' => 'Choose another user for private chat.']);
        }

        $existing = Conversation::query()
            ->where('type', 'direct')
            ->whereHas('members', fn ($query) => $query->where('user_id', $creator->id))
            ->whereHas('members', fn ($query) => $query->where('user_id', $recipientId))
            ->first();

        if ($existing) {
            return $existing->load('activeMembers.user');
        }

        return DB::transaction(function () use ($creator, $recipientId) {
            $conversation = Conversation::create([
                'type' => 'direct',
                'privacy' => 'private',
                'created_by' => $creator->id,
            ]);

            $this->attachMember($conversation, $creator->id, 'owner');
            $this->attachMember($conversation, $recipientId, 'member');
            $this->audit($creator, 'conversation.created', $conversation);

            return $conversation->load('activeMembers.user');
        }, 3);
    }

    public function createGroup(User $creator, array $data): Conversation
    {
        return DB::transaction(function () use ($creator, $data) {
            $conversation = Conversation::create([
                'type' => 'group',
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'image_path' => $data['image_path'] ?? null,
                'privacy' => $data['privacy'] ?? 'private',
                'owner_id' => $creator->id,
                'created_by' => $creator->id,
                'settings' => $data['settings'] ?? [],
            ]);

            $this->attachMember($conversation, $creator->id, 'owner');

            foreach (array_unique($data['member_ids'] ?? []) as $memberId) {
                if ((int) $memberId !== $creator->id) {
                    $this->attachMember($conversation, (int) $memberId, 'member');
                }
            }

            $this->audit($creator, 'group.created', $conversation, null, $conversation->toArray());

            return $conversation->load('activeMembers.user');
        }, 3);
    }

    public function sendMessage(User $sender, Conversation $conversation, array $data): Message
    {
        $this->ensureMember($conversation, $sender);

        return DB::transaction(function () use ($sender, $conversation, $data) {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'parent_id' => $data['parent_id'] ?? null,
                'forwarded_from_id' => $data['forwarded_from_id'] ?? null,
                'type' => $data['type'] ?? 'text',
                'content' => $data['content'] ?? null,
                'attachments' => $data['attachments'] ?? null,
                'metadata' => [
                    'client_id' => $data['client_id'] ?? null,
                    'virus_scan_status' => ! empty($data['attachments']) ? 'pending' : null,
                ],
            ]);

            $conversation->touch();

            $preview = str($message->content ?? $message->type)->limit(120)->toString();
            ProcessMessageDeliveries::dispatch(
                $message->id,
                $conversation->id,
                $sender->id,
                $sender->name,
                $preview
            );

            MessageRead::updateOrCreate(
                ['message_id' => $message->id, 'user_id' => $sender->id],
                ['read_at' => now()]
            );

            $this->audit($sender, 'message.sent', $conversation, null, $message->only(['id', 'type', 'content']));

            return $message->load('sender:id,name,photo');
        }, 3);
    }

    public function messages(User $user, Conversation $conversation, int $perPage = 30): LengthAwarePaginator
    {
        $this->ensureMember($conversation, $user);

        return $conversation->messages()
            ->with(['sender:id,name,photo', 'parent:id,content,sender_id,attachments', 'parent.sender:id,name,photo', 'reads:user_id,message_id,read_at'])
            ->paginate(min(max($perPage, 10), 100));
    }

    public function updateMessage(User $user, Message $message, string $content): Message
    {
        $this->ensureMember($message->conversation, $user);

        if ($message->sender_id !== $user->id) {
            abort(403, 'Only the sender can edit this message.');
        }

        $before = $message->only(['content', 'edited_at']);
        $message->forceFill(['content' => $content, 'edited_at' => now()])->save();
        $this->audit($user, 'message.edited', $message->conversation, $message, $before, $message->only(['content', 'edited_at']));

        return $message->fresh('sender:id,name,photo');
    }

    public function deleteMessage(User $user, Message $message): void
    {
        $member = $this->ensureMember($message->conversation, $user);

        if ($message->sender_id !== $user->id && ! $member->canManageMembers()) {
            abort(403, 'You cannot delete this message.');
        }

        $before = $message->only(['content', 'type', 'attachments']);
        $message->forceFill(['deleted_by' => $user->id])->save();
        $message->delete();
        $this->audit($user, 'message.deleted', $message->conversation, $message, $before);
    }

    public function markRead(User $user, Conversation $conversation, ?int $messageId = null): void
    {
        $this->ensureMember($conversation, $user);

        if ($messageId && ! $conversation->messages()->whereKey($messageId)->exists()) {
            abort(404);
        }

        $query = $conversation->messages()->where('sender_id', '!=', $user->id);

        if ($messageId) {
            $query->where('id', '<=', $messageId);
        }

        $ids = $query->pluck('id');
        if ($ids->isNotEmpty()) {
            $now = now();
            $reads = $ids->map(fn ($id) => [
                'message_id' => $id,
                'user_id' => $user->id,
                'read_at' => $now,
            ])->toArray();

            MessageRead::upsert($reads, ['message_id', 'user_id'], ['read_at']);
        }

        Member::where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->update(['last_read_message_id' => $messageId]);
    }

    public function attachMember(Conversation $conversation, int $userId, string $role = 'member'): Member
    {
        return Member::updateOrCreate([
            'conversation_id' => $conversation->id,
            'user_id' => $userId,
        ], [
            'role' => $role,
            'status' => 'active',
            'joined_at' => now(),
            'left_at' => null,
            'notification_preferences' => ['in_app' => true, 'push' => true, 'browser' => true],
        ]);
    }

    public function removeMember(User $actor, Conversation $conversation, int $userId): void
    {
        $actorMember = $this->ensureMember($conversation, $actor);
        $targetMember = Member::where('conversation_id', $conversation->id)->where('user_id', $userId)->where('status', 'active')->firstOrFail();

        if (! $actorMember->canManageMembers() && $actor->id !== $userId) {
            abort(403, 'You cannot remove members from this chat.');
        }
        if ($targetMember->role === 'owner') {
            abort(422, 'Transfer ownership before removing the owner.');
        }

        $targetMember->forceFill(['status' => 'left', 'left_at' => now()])->save();

        $this->audit($actor, 'member.removed', $conversation, null, ['user_id' => $userId]);
    }

    public function updateRole(User $actor, Conversation $conversation, int $userId, string $role): Member
    {
        $actorMember = $this->ensureMember($conversation, $actor);

        if (! $actorMember->canManageSettings()) {
            abort(403, 'Only owners and admins can update roles.');
        }

        $member = Member::where('conversation_id', $conversation->id)->where('user_id', $userId)->firstOrFail();
        if ($member->role === 'owner' && $role !== 'owner') {
            abort(422, 'Transfer ownership before changing the owner role.');
        }
        if ($role === 'owner' && $actorMember->role !== 'owner') {
            abort(403, 'Only the owner can transfer ownership.');
        }

        $before = $member->only(['role']);
        $member->forceFill(['role' => $role])->save();
        $this->audit($actor, 'member.role_updated', $conversation, null, $before, $member->only(['role']));

        return $member->fresh('user');
    }

    public function updatePresence(User $user, array $data): Presence
    {
        return Presence::updateOrCreate(['user_id' => $user->id], [
            'status' => $data['status'] ?? 'online',
            'last_seen_at' => now(),
            'typing_conversation_id' => $data['typing_conversation_id'] ?? null,
            'typing_expires_at' => ! empty($data['typing_conversation_id']) ? now()->addSeconds(8) : null,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    public function listUsersWithPresence(User $viewer, ?string $term = null, bool $includeSelf = false): SupportCollection
    {
        $lastSessionActivity = DB::table('sessions')
            ->selectRaw('max(last_activity)')
            ->whereColumn('sessions.user_id', 'users.id');

        $lastSessionUserAgent = DB::table('sessions')
            ->select('user_agent')
            ->whereColumn('sessions.user_id', 'users.id')
            ->orderByDesc('last_activity')
            ->limit(1);

        $lastTokenActivity = DB::table('personal_access_tokens')
            ->selectRaw('max(COALESCE(last_used_at, created_at))')
            ->where('tokenable_type', User::class)
            ->whereColumn('tokenable_id', 'users.id');

        $presenceStatus = DB::table('chat_presence')
            ->select('status')
            ->whereColumn('chat_presence.user_id', 'users.id')
            ->limit(1);

        $presenceLastSeen = DB::table('chat_presence')
            ->select('last_seen_at')
            ->whereColumn('chat_presence.user_id', 'users.id')
            ->limit(1);

        $todayOrdersCount = DB::table('orders')
            ->selectRaw('count(*)')
            ->whereColumn('orders.created_by', 'users.id')
            ->whereDate('created_at', now()->toDateString());

        $todayRevenue = DB::table('orders')
            ->selectRaw('sum(total_amount)')
            ->whereColumn('orders.created_by', 'users.id')
            ->whereDate('created_at', now()->toDateString());

        $users = User::query()
            ->select('id', 'name', 'first_name', 'last_name', 'email', 'employee_id', 'photo', 'is_active as status', 'village_name', 'district', 'state')
            ->selectSub($lastSessionActivity, 'last_session_activity')
            ->selectSub($lastSessionUserAgent, 'last_session_user_agent')
            ->selectSub($lastTokenActivity, 'last_token_activity')
            ->selectSub($presenceStatus, 'presence_status')
            ->selectSub($presenceLastSeen, 'presence_last_seen_at')
            ->selectSub($todayOrdersCount, 'today_orders')
            ->selectSub($todayRevenue, 'today_revenue')
            ->where('is_active', 1)
            ->when(! $includeSelf, fn ($query) => $query->where('id', '!=', $viewer->id))
            ->when($term, fn ($query) => $query->where(function ($nested) use ($term) {
                $nested->where('name', 'like', "%{$term}%")
                    ->orWhere('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('employee_id', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            }))
            ->orderBy('name')
            ->limit(250)
            ->get();

        return $users->map(function (User $user) {
            $lastSessionAt = $user->last_session_activity
                ? now()->setTimestamp((int) $user->last_session_activity)
                : null;
            $lastTokenAt = $user->last_token_activity
                ? Carbon::parse($user->last_token_activity)
                : null;
            $presenceLastSeenAt = $user->presence_last_seen_at
                ? Carbon::parse($user->presence_last_seen_at)
                : null;
            $lastSeenAt = collect([$lastSessionAt, $lastTokenAt, $presenceLastSeenAt])
                ->filter()
                ->sortDesc()
                ->first();
            $isOnline = $lastSessionAt?->greaterThanOrEqualTo(now()->subMinutes(self::ONLINE_WINDOW_MINUTES)) === true
                || $lastTokenAt?->greaterThanOrEqualTo(now()->subMinutes(self::ONLINE_WINDOW_MINUTES)) === true
                || ($user->presence_status === 'online' && $presenceLastSeenAt?->greaterThanOrEqualTo(now()->subMinutes(self::ONLINE_WINDOW_MINUTES)) === true);

            $location = collect([$user->village_name, $user->district, $user->state])->filter()->implode(', ');

            $activeDevice = $this->deviceFromUserAgent($user->last_session_user_agent);
            if ($lastTokenAt && (!$lastSessionAt || $lastTokenAt->greaterThan($lastSessionAt))) {
                $activeDevice = 'mobile';
            }

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'employee_id' => $user->employee_id,
                'photo' => $user->photo,
                'location' => $location,
                'is_online' => $isOnline,
                'presence_status' => $isOnline ? 'online' : 'offline',
                'last_seen_at' => $lastSeenAt?->toIso8601String(),
                'last_seen_label' => $isOnline ? 'Active now' : ($lastSeenAt ? 'Last seen '.$lastSeenAt->diffForHumans() : 'Not seen yet'),
                'active_device' => $activeDevice,
                'today_orders' => (int) $user->today_orders,
                'today_revenue' => (float) $user->today_revenue,
            ];
        })->sortBy([
            ['is_online', 'desc'],
            ['name', 'asc'],
        ])->values();
    }

    public function search(User $user, array $filters): array
    {
        $term = trim((string) ($filters['q'] ?? ''));

        $messages = Message::query()
            ->whereHas('conversation.members', fn ($query) => $query->where('user_id', $user->id)->where('status', 'active'))
            ->when($term !== '', fn ($query) => $query->where('content', 'like', "%{$term}%"))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['conversation_id'] ?? null, fn ($query, $id) => $query->where('conversation_id', $id))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->latest()
            ->limit(25)
            ->get();

        $groups = Conversation::query()
            ->visibleTo($user)
            ->where('type', 'group')
            ->when($term !== '', fn ($query) => $query->where('name', 'like', "%{$term}%"))
            ->limit(15)
            ->get();

        $users = $this->listUsersWithPresence($user, $term ?: null)->take(15)->values();

        return compact('messages', 'groups', 'users');
    }

    public function locationForUser(User $user): ?string
    {
        return collect([$user->village_name, $user->district, $user->state])->filter()->implode(', ');
    }

    public function ensureMember(Conversation $conversation, User $user): Member
    {
        return $conversation->members()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->whereNull('left_at')
            ->firstOrFail();
    }

    private function deviceFromUserAgent(?string $userAgent): ?string
    {
        if (! $userAgent) {
            return null;
        }

        if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $userAgent)) {
            return 'mobile';
        }

        return 'desktop';
    }

    public function audit(?User $actor, string $action, ?Conversation $conversation = null, ?Message $message = null, ?array $before = null, ?array $after = null, ?Request $request = null): void
    {
        AuditLog::create([
            'actor_id' => $actor?->id,
            'conversation_id' => $conversation?->id,
            'message_id' => $message?->id,
            'action' => $action,
            'before' => $before,
            'after' => $after,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
