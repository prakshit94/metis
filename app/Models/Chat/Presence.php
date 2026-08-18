<?php

namespace App\Models\Chat;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Presence extends Model implements Auditable
{
    use AuditableTrait;
    protected $table = 'chat_presence';

    protected $fillable = [
        'user_id',
        'status',
        'last_seen_at',
        'typing_conversation_id',
        'typing_expires_at',
        'metadata',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'typing_expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
