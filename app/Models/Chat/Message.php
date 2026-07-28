<?php

namespace App\Models\Chat;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Message extends Model implements Auditable
{
    use AuditableTrait;
    use SoftDeletes;


    protected $table = 'chat_messages';

    protected $fillable = [
        'uuid',
        'conversation_id',
        'sender_id',
        'parent_id',
        'forwarded_from_id',
        'type',
        'content',
        'attachments',
        'metadata',
        'edited_at',
        'deleted_by',
    ];

    protected $casts = [
        'attachments' => 'array',
        'metadata' => 'array',
        'edited_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Message $message) {
            $message->uuid ??= (string) Str::uuid();
        });
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'parent_id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(MessageRead::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(MessageDelivery::class);
    }
}
