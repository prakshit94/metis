<?php

namespace App\Models\Chat;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
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
