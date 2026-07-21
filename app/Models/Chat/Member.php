<?php

namespace App\Models\Chat;

use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Member extends Model
{
    protected $table = 'chat_members';

    protected $fillable = [
        'conversation_id',
        'user_id',
        'role',
        'status',
        'last_read_message_id',
        'muted_until',
        'archived_at',
        'pinned_at',
        'joined_at',
        'left_at',
        'location_visibility',
        'notification_preferences',
    ];

    protected $casts = [
        'muted_until' => 'datetime',
        'archived_at' => 'datetime',
        'pinned_at' => 'datetime',
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'notification_preferences' => 'array',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function canManageMembers(): bool
    {
        return in_array($this->role, ['owner', 'admin', 'moderator'], true);
    }

    public function canManageSettings(): bool
    {
        return in_array($this->role, ['owner', 'admin'], true);
    }
}
