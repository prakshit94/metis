<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class TargetAchievement extends Model implements Auditable
{
    use AuditableTrait;
    protected $fillable = [
        'target_id',
        'achieved_value',
        'achievement_date',
        'notes',
    ];

    protected $casts = [
        'achievement_date' => 'date',
        'achieved_value' => 'decimal:2',
    ];

    public function target(): BelongsTo
    {
        return $this->belongsTo(Target::class);
    }
}
