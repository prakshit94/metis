<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Core\Models\Village;
use App\Modules\Core\Models\VillageServiceMapping;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /**
     * Internal users responsible for providing this shipping service.
     */
    public function providers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'service_provider_users')
            ->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
