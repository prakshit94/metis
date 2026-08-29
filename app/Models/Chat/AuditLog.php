<?php

namespace App\Models\Chat;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class AuditLog extends Model implements Auditable
{
    use AuditableTrait;

    protected $table = 'chat_audit_logs';

    protected $fillable = [
        'actor_id',
        'conversation_id',
        'message_id',
        'action',
        'before',
        'after',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
    ];
}
