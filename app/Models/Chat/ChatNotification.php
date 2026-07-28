<?php

namespace App\Models\Chat;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class ChatNotification extends Model implements Auditable
{
    use AuditableTrait;
    protected $table = 'chat_notifications';

    protected $fillable = ['user_id', 'conversation_id', 'message_id', 'type', 'payload', 'read_at'];

    protected $casts = [
        'payload' => 'array',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
