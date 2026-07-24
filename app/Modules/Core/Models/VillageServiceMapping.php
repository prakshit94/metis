<?php

declare(strict_types=1);

namespace App\Modules\Core\Models;

use App\Modules\Catalog\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VillageServiceMapping extends Model
{
    protected $table = 'village_service_mappings';

    protected $fillable = [
        'village_id',
        'service_id',
        'is_available',
        'serviceable_from_date',
        'serviceable_to_date',
        'remarks',
        'priority',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'serviceable_from_date' => 'date',
        'serviceable_to_date' => 'date',
        'priority' => 'integer',
    ];

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_available', true);
    }

    public function scopeForService(Builder $query, int|string $service): Builder
    {
        if (is_numeric($service)) {
            return $query->where('service_id', $service);
        }

        return $query->whereHas('service', fn ($q) => $q->where('code', $service));
    }

    public function scopeActiveDateRange(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->where(function ($q) use ($today) {
            $q->whereNull('serviceable_from_date')
                ->orWhere('serviceable_from_date', '<=', $today);
        })->where(function ($q) use ($today) {
            $q->whereNull('serviceable_to_date')
                ->orWhere('serviceable_to_date', '>=', $today);
        });
    }
}
