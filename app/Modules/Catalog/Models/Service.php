<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Core\Models\Village;
use App\Modules\Core\Models\VillageServiceMapping;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Service extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function villages(): BelongsToMany
    {
        return $this->belongsToMany(Village::class, 'village_service_mappings')
            ->withPivot(['is_available', 'priority'])
            ->withTimestamps();
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(VillageServiceMapping::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
