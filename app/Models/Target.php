<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Target extends Model implements Auditable
{
    use SoftDeletes, AuditableTrait;

    protected $fillable = [
        'targetable_id',
        'targetable_type',
        'metric_type',
        'period_type',
        'start_date',
        'end_date',
        'target_amount',
        'achieved_amount',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'target_amount' => 'decimal:2',
        'achieved_amount' => 'decimal:2',
    ];

    public function targetable(): MorphTo
    {
        return $this->morphTo();
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(TargetAchievement::class);
    }

    protected function achievementPercentage(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->target_amount <= 0) return $this->achieved_amount > 0 ? 100 : 0;
                return round(($this->achieved_amount / $this->target_amount) * 100, 2);
            }
        );
    }
}
