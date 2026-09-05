<?php

declare(strict_types=1);

namespace App\Modules\Core\Models;

use App\Modules\Catalog\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Village extends Model implements Auditable
{
    use LogsActivity;
    use AuditableTrait;
    use SoftDeletes;

    protected $fillable = [
        'village_name',
        'normalized_name',
        'pincode',
        'post_so_name',
        'taluka_name',
        'district_name',
        'state_name',
    ];

    /**
     * Mutator to normalize village name for faster searching
     */
    protected function setVillageNameAttribute($value)
    {
        $this->attributes['village_name'] = $value;
        $this->attributes['normalized_name'] = strtolower(trim($value));
    }

    /**
     * Optimized relationship to Services via Pivot
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'village_service_mappings')
            ->withPivot(['is_available', 'serviceable_from_date', 'serviceable_to_date', 'priority'])
            ->withTimestamps();
    }

    /**
     * Dedicated Pivot model relationship for advanced querying
     */
    public function mappings(): HasMany
    {
        return $this->hasMany(VillageServiceMapping::class);
    }

    public function scopeByPincode(Builder $query, string $pincode): Builder
    {
        return $query->where('pincode', $pincode);
    }

    public function scopeByDistrict(Builder $query, string $district): Builder
    {
        return $query->where('district_name', $district);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = strtolower(trim($term));

        return $query->where(function (Builder $q) use ($term) {
            $q->where('normalized_name', 'like', "%{$term}%")
              ->orWhere('pincode', 'like', "{$term}%");
        });
    }

    /**
     * Highly optimized check using EXISTS query instead of heavy relationship loading
     */
    public function hasService(string $serviceCode): bool
    {
        return $this->mappings()
            ->whereHas('service', fn ($q) => $q->where('code', $serviceCode))
            ->where('is_available', true)
            ->exists();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
