<?php

namespace App\Modules\Users\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class LeaveBalance extends Model
 implements Auditable{
    use AuditableTrait;

    protected $fillable = [
        'user_id',
        'leave_type',
        'total_leaves',
        'used_leaves',
        'balance',
        'is_active',
    ];

    protected $casts = [
        'total_leaves' => 'decimal:2',
        'used_leaves' => 'decimal:2',
        'balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
