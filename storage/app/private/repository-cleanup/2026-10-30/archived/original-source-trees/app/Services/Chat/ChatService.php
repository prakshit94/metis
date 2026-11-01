<?php

namespace App\Services\Chat;

use App\Jobs\Chat\ProcessMessageDeliveries;
use App\Models\Chat\AuditLog;
use App\Models\Chat\ChatNotification;
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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChatService
{
    private const ONLINE_WINDOW_MINUTES = 5;

    /** Keep legacy unread-conversation totals and message badges consistent. */
    public function unreadCounts(User $user): array
    {
        $counts = Message::query()
            ->whereHas('conversation', fn ($query) => $query->visibleTo($user))
            ->where('sender_id', '!=', $user->id)
            ->whereDoesntHave('reads', fn ($query) => $query->where('user_id', $user->id))
            ->toBase()->selectRaw('COUNT(*) as messages, COUNT(DISTINCT conversation_id) as conversations')->first();

        return ['unread' => (int) $counts->conversations, 'unread_messages' => (int) $counts->messages];
    }

    public function listConversations(User $user, ?int $perPage = null, int $page = 1): Collection
    {
        $mayRead = $user->can('messages-view');

        return Conversation::query()
            ->visibleTo($user)
            ->with([
                'activeMembers.user:id,name,first_name,last_name,email,photo,gender',
                'latestMessage.sender:id,name',
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
            ->orderByDesc('id')
            ->when($perPage !== null, fn ($query) => $query->skip((max(1, $page) - 1) * min(max($perPage, 1), 100))->take(min(max($perPage, 1), 100)))
            ->get()
            ->map(function ($conversation) use ($user, $mayRead) {
                if (! $mayRead) {
                    $conversation->unsetRelation('latestMessage');
                }
                $member = $conversation->activeMembers->firstWhere('user_id', $user->id);
                $conversation->is_pinned = $member ? ! is_null($member->pinned_at) : false;
                $conversation->is_archived = $member ? ! is_null($member->archived_at) : false;

                return $conversation;
            });
    }

    public function createDirectConversation(User $creator, int $recipientId): Conversation
    {
        if ($creator->id === $recipientId) {
            throw ValidationException::withMessages(['user_id' => 'Choose another user for private chat.']);
        }

        return DB::transaction(function () use ($creator, $recipientId) {
            $users = User::whereIn('id', [$creator->id, $recipientId])->where('is_active', 1)->orderBy('id')->lockForUpdate()->get(['id']);
            if ($users->count() !== 2) {
                throw ValidationException::withMessages(['user_id' => 'Choose an active user.']);
            }
            $existing = Conversation::where('type', 'direct')
                ->whereHas('activeMembers', fn ($query) => $query->where('user_id', $creator->id))
                ->whereHas('activeMembers', fn ($query) => $query->where('user_id', $recipientId))->first();
            if ($existing) {
                return $existing->load('activeMembers.user:id,name,first_name,last_name,email,photo,gender');
            }
            $conversation = Conversation::create([
                'type' => 'direct',
                'privacy' => 'private',
                'created_by' => $creator->id,
            ]);

            $this->attachMember($conversation, $creator->id, 'owner');
            $this->attachMember($conversation, $recipientId, 'member');
            $this->audit($creator, 'conversation.created', $conversation);

            return $conversation->load('activeMembers.user:id,name,first_name,last_name,email,photo,gender');
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

            return $conversation->load('activeMembers.user:id,name,first_name,last_name,email,photo,gender');
        }, 3);
    }

    public function sendMessage(User $sender, Conversation $conversation, array $data): Message
    {
        $this->ensureMember($conversation, $sender);

        return DB::transaction(function () use ($sender, $conversation, $data) {
            $conversation = Conversation::lockForUpdate()->findOrFail($conversation->id);
            $this->ensureMember($conversation, $sender);
            if (! empty($data['client_id'])) {
                $existing = $conversation->messages()->withTrashed()->where('sender_id', $sender->id)->where('metadata->client_id', $data['client_id'])->first();
                if ($existing) {
                    abort_if($existing->trashed(), 409, 'The original message was deleted. Refresh the conversation before sending again.');

                    return $existing->load('sender:id,name,photo,gender');
                }
            }
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
            DB::afterCommit(function () use ($message, $conversation, $sender, $preview) {
                ProcessMessageDeliveries::dispatchAfterResponse($message->id, $conversation->id, $sender->id, $sender->name, $preview);
            });

            MessageRead::updateOrCreate(
                ['message_id' => $message->id, 'user_id' => $sender->id],
                ['read_at' => now()]
            );

            $this->audit($sender, 'message.sent', $conversation, null, $message->only(['id', 'type', 'content']));

            return $message->load('sender:id,name,photo,gender');
        }, 3);
    }

    public function messages(User $user, Conversation $conversation, int $perPage = 30, ?int $beforeId = null, ?int $afterId = null): LengthAwarePaginator
    {
        $this->ensureMember($conversation, $user);

        $query = $conversation->messages()->reorder()
            ->orderBy('created_at', $afterId ? 'asc' : 'desc')->orderBy('id', $afterId ? 'asc' : 'desc');
        foreach ([[$beforeId, '<'], [$afterId, '>']] as [$cursorId, $operator]) {
            if (! $cursorId) {
                continue;
            }
            $anchor = $conversation->messages()->withTrashed()->findOrFail($cursorId, ['id', 'created_at']);
            $query->where(function ($window) use ($anchor, $operator) {
                if ($anchor->created_at === null) {
                    $window->where(fn ($tie) => $tie->whereNull('created_at')->where('id', $operator, $anchor->id));
                    if ($operator === '>') {
                        $window->orWhereNotNull('created_at');
                    }
                } else {
                    $window->where('created_at', $operator, $anchor->created_at)
                        ->orWhere(fn ($tie) => $tie->where('created_at', $anchor->created_at)->where('id', $operator, $anchor->id));
                    if ($operator === '<') {
                        $window->orWhereNull('created_at');
                    }
                }
            });
        }

        return $query
            ->with(['sender:id,name,photo,gender', 'parent:id,content,sender_id,attachments', 'parent.sender:id,name,photo,gender', 'reads:user_id,message_id,read_at'])
            ->paginate(min(max($perPage, 10), 100));
    }

    public function updateMessage(User $user, Message $message, string $content): Message
    {
        return DB::transaction(function () use ($user, $message, $content) {
            $message = Message::lockForUpdate()->findOrFail($message->id);
            $this->ensureMember($message->conversation, $user);

            if ($message->sender_id !== $user->id) {
                abort(403, 'Only the sender can edit this message.');
            }

            $before = $message->only(['content', 'edited_at']);
            $message->forceFill(['content' => $content, 'edited_at' => now()])->save();
            $this->audit($user, 'message.edited', $message->conversation, $message, $before, $message->only(['content', 'edited_at']));

            return $message->fresh('sender:id,name,photo,gender');
        }, 3);
    }

    public function deleteMessage(User $user, Message $message): void
    {
        DB::transaction(function () use ($user, $message) {
            $message = Message::lockForUpdate()->findOrFail($message->id);
            $member = $this->ensureMember($message->conversation, $user);

            if ($message->sender_id !== $user->id && ! $member->canManageMembers()) {
                abort(403, 'You cannot delete this message.');
            }

            $before = $message->only(['content', 'type', 'attachments']);
            $message->forceFill(['deleted_by' => $user->id])->save();
            $message->delete();
            $this->audit($user, 'message.deleted', $message->conversation, $message, $before);
        }, 3);
    }

    public function markRead(User $user, Conversation $conversation, ?int $messageId = null): void
    {
        $this->ensureMember($conversation, $user);

        if ($messageId && ! $conversation->messages()->whereKey($messageId)->exists()) {
            abort(404);
        }

        $boundary = $messageId ?? $conversation->messages()->max('id');
        if (! $boundary) {
            return;
        }
        $conversation->messages()->reorder()->where('sender_id', '!=', $user->id)->where('id', '<=', $boundary)
            ->whereDoesntHave('reads', fn ($query) => $query->where('user_id', $user->id))
            ->select('id')->chunkById(500, function ($messages) use ($user) {
                MessageRead::insertOrIgnore($messages->map(fn ($message) => ['message_id' => $message->id, 'user_id' => $user->id, 'read_at' => now(), 'created_at' => now(), 'updated_at' => now()])->all());
            });
        Member::where('conversation_id', $conversation->id)->where('user_id', $user->id)
            ->where('status', 'active')->whereNull('left_at')
            ->where(fn ($query) => $query->whereNull('last_read_message_id')->orWhere('last_read_message_id', '<', $boundary))
            ->update(['last_read_message_id' => DB::raw('GREATEST(COALESCE(last_read_message_id, 0), '.(int) $boundary.')')]);
        ChatNotification::where('conversation_id', $conversation->id)->where('user_id', $user->id)
            ->where('message_id', '<=', $boundary)->whereNull('read_at')->update(['read_at' => now()]);
    }

    public function attachMember(Conversation $conversation, int $userId, string $role = 'member'): Member
    {
        $existing = Member::where('conversation_id', $conversation->id)->where('user_id', $userId)->first();
        if ($existing && $existing->status === 'active' && $existing->left_at === null) {
            return $existing;
        }

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
        DB::transaction(function () use ($actor, $conversation, $userId) {
            $conversation = Conversation::lockForUpdate()->findOrFail($conversation->id);
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
        }, 3);
    }

    public function updateRole(User $actor, Conversation $conversation, int $userId, string $role): Member
    {
        return DB::transaction(function () use ($actor, $conversation, $userId, $role) {
            $conversation = Conversation::lockForUpdate()->findOrFail($conversation->id);
            $actorMember = $this->ensureMember($conversation, $actor);

            if (! $actorMember->canManageSettings()) {
                abort(403, 'Only owners and admins can update roles.');
            }

            $member = $conversation->activeMembers()->where('user_id', $userId)->firstOrFail();
            if ($member->role === 'owner' && $role !== 'owner') {
                abort(422, 'Transfer ownership before changing the owner role.');
            }
            if ($role === 'owner' && $actorMember->role !== 'owner') {
                abort(403, 'Only the owner can transfer ownership.');
            }

            $before = $member->only(['role']);
            $member->forceFill(['role' => $role])->save();
            $this->audit($actor, 'member.role_updated', $conversation, null, $before, $member->only(['role']));

            return $member->fresh('user:id,name,first_name,last_name,email,photo,gender');
        }, 3);
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

    public function listUsersWithPresence(User $viewer, ?string $term = null, bool $includeSelf = false, bool $loggedInToday = false): SupportCollection
    {
        $users = User::query()
            ->select('id', 'name', 'first_name', 'last_name', 'email', 'employee_id', 'photo', 'gender', 'is_active as status', 'village_name', 'district', 'state')
            ->where('is_active', 1)
            ->when(! $includeSelf, fn ($query) => $query->where('id', '!=', $viewer->id))
            ->when($term, fn ($query) => $query->where(function ($nested) use ($term) {
                $nested->where('name', 'like', "%{$term}%")
                    ->orWhere('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('employee_id', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            }))
            ->when($loggedInToday, fn ($query) => $query->whereExists(function ($logins) {
                $logins->selectRaw('1')->from('login_histories')
                    ->whereColumn('login_histories.user_id', 'users.id')
                    ->where('login_histories.status', 'success')
                    ->where('login_histories.attempted_at', '>=', today())
                    ->where('login_histories.attempted_at', '<', today()->addDay());
            }))
            ->when($includeSelf, fn ($query) => $query->orderByRaw('id = ? DESC', [$viewer->id]))
            ->orderBy('name')
            ->when(! $loggedInToday, fn ($query) => $query->limit($includeSelf ? 251 : 250))
            ->get();

        $userIds = $users->pluck('id');

        if ($userIds->isEmpty()) {
            return collect();
        }

        // Display assigned LOBs in one query without changing permission/team context.
        $lobAssignments = DB::table(config('permission.table_names.model_has_roles').' as assignments')
            ->join('teams', 'teams.id', '=', 'assignments.team_id')
            ->where('assignments.model_type', $viewer->getMorphClass())
            ->whereIn('assignments.'.config('permission.column_names.model_morph_key'), $userIds)
            ->select('assignments.'.config('permission.column_names.model_morph_key').' as user_id', 'teams.name')
            ->distinct()
            ->orderBy('teams.name')
            ->get()
            ->groupBy('user_id');

        $presences = DB::table('chat_presence')
            ->select('user_id', 'status', 'last_seen_at')
            ->whereIn('user_id', $userIds)
            ->get()
            ->keyBy('user_id');

        $todayOrders = Cache::remember('chat_today_orders_'.now()->toDateString(), 60, function () {
            return DB::table('orders')
                ->selectRaw('created_by, count(*) as today_orders, sum(net_amount) as today_revenue')
                ->whereNotIn('status', ['cancelled', 'future_order'])
                ->where('order_date', '>=', today())
                ->where('order_date', '<', today()->addDay())
                ->groupBy('created_by')
                ->get()
                ->keyBy('created_by');
        });

        $tokens = DB::table('personal_access_tokens')
            ->selectRaw('tokenable_id, max(COALESCE(last_used_at, created_at)) as last_token_activity')
            ->where('tokenable_type', User::class)
            ->whereIn('tokenable_id', $userIds)
            ->groupBy('tokenable_id')
            ->get()
            ->keyBy('tokenable_id');

        $latestSessions = DB::table('sessions')->select('user_id')->selectRaw('MAX(last_activity) as latest_activity')
            ->whereIn('user_id', $userIds)->groupBy('user_id');
        $sessions = DB::table('sessions')->joinSub($latestSessions, 'latest_sessions', fn ($join) => $join
            ->on('sessions.user_id', '=', 'latest_sessions.user_id')->on('sessions.last_activity', '=', 'latest_sessions.latest_activity'))
            ->select('sessions.user_id', 'sessions.last_activity', 'sessions.user_agent')
            ->orderByDesc('sessions.last_activity')->get()->unique('user_id')->keyBy('user_id');

        return $users->map(function (User $user) use ($presences, $todayOrders, $tokens, $sessions, $lobAssignments) {
            $session = $sessions->get($user->id);
            $token = $tokens->get($user->id);
            $presence = $presences->get($user->id);
            $order = $todayOrders->get($user->id);

            $lastSessionAt = $session
                ? now()->setTimestamp((int) $session->last_activity)
                : null;
            $lastTokenAt = $token && $token->last_token_activity
                ? Carbon::parse($token->last_token_activity)
                : null;
            $presenceLastSeenAt = $presence && $presence->last_seen_at
                ? Carbon::parse($presence->last_seen_at)
                : null;
            $presenceStatus = $presence->status ?? null;

            $lastSeenAt = collect([$lastSessionAt, $lastTokenAt, $presenceLastSeenAt])
                ->filter()
                ->sortDesc()
                ->first();

            $isOnline = $lastSessionAt?->greaterThanOrEqualTo(now()->subMinutes(self::ONLINE_WINDOW_MINUTES)) === true
                || $lastTokenAt?->greaterThanOrEqualTo(now()->subMinutes(self::ONLINE_WINDOW_MINUTES)) === true
                || ($presenceStatus === 'online' && $presenceLastSeenAt?->greaterThanOrEqualTo(now()->subMinutes(self::ONLINE_WINDOW_MINUTES)) === true);

            $location = $this->locationForUser($user);

            $activeDevice = $this->deviceFromUserAgent($session->user_agent ?? null);
            if ($lastTokenAt && (! $lastSessionAt || $lastTokenAt->greaterThan($lastSessionAt))) {
                $activeDevice = 'mobile';
            }

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'employee_id' => $user->employee_id,
                'photo' => $user->photo,
                'gender' => $user->gender,
                'location' => $location,
                'lob_team_name' => $lobAssignments->get($user->id, collect())->pluck('name')->implode(', ') ?: 'Global',
                'is_online' => $isOnline,
                'presence_status' => $isOnline ? 'online' : 'offline',
                'last_seen_at' => $lastSeenAt?->toIso8601String(),
                'last_seen_label' => $isOnline ? 'Active now' : ($lastSeenAt ? 'Last seen '.$lastSeenAt->diffForHumans() : 'Not seen yet'),
                'active_device' => $activeDevice,
                'today_orders' => (int) ($order->today_orders ?? 0),
                'today_revenue' => (float) ($order->today_revenue ?? 0),
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
            ->when(! $user->can('messages-view'), fn ($query) => $query->whereRaw('1 = 0'))
            ->whereHas('conversation.activeMembers', fn ($query) => $query->where('user_id', $user->id))
            ->when($term !== '', fn ($query) => $query->where('content', 'like', "%{$term}%"))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['conversation_id'] ?? null, fn ($query, $id) => $query->where('conversation_id', $id))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->where('created_at', '>=', Carbon::parse($date)->startOfDay()))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->where('created_at', '<', Carbon::parse($date)->startOfDay()->addDay()))
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
        return trim((string) $user->village_name) ?: null;
    }

    public function ensureMember(?Conversation $conversation, User $user): Member
    {
        abort_if(! $conversation || $conversation->trashed(), 404);

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
