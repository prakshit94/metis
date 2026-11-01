<?php

declare(strict_types=1);

namespace App\Modules\Orders\Models;

use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class OrderStatusLog extends Model implements Auditable
{
    use AuditableTrait;

    protected $fillable = [
        'order_id',
        'status',
        'notes',
        'changed_by',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
