<?php

namespace App\Models\Chat;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class MessageDelivery extends Model implements Auditable
{
    use AuditableTrait;
    protected $table = 'chat_message_deliveries';

    protected $fillable = ['message_id', 'user_id', 'delivered_at'];

    protected $casts = ['delivered_at' => 'datetime'];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
