<?php

namespace App\Models\Chat;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Conversation extends Model implements Auditable
{
    use AuditableTrait;
    use SoftDeletes;


    protected $table = 'chat_conversations';

    protected $fillable = [
        'uuid',
        'type',
        'name',
        'description',
        'image_path',
        'privacy',
        'owner_id',
        'created_by',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Conversation $conversation) {
            $conversation->uuid ??= (string) Str::uuid();
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function activeMembers(): HasMany
    {
        return $this->members()->where('status', 'active')->whereNull('left_at');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->latest();
    }

    public function latestMessage(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->whereHas('members', fn (Builder $member) => $member
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->whereNull('left_at'));
    }
}
