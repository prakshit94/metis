<?php

namespace App\Modules\Users\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    protected $fillable = [
        'name',
        'code',
        'state_name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * A canonical map from team code → the exact state_name value
     * stored in orders.shipping_state and villages.state_name.
     *
     * IMPORTANT: values must match the exact spelling in your database.
     * 'Maharastra' (not Maharashtra) because that is how it appears in villages.state_name.
     */
    public const STATE_NAME_MAP = [
        'GJ' => 'Gujarat',
        'RJ' => 'Rajasthan',
        'MH' => 'Maharastra',
        'MP' => 'Madhya Pradesh',
    ];

    /**
     * Get the full state name for this team code.
     * Falls back to the stored state_name column if code is not in the map.
     */
    public function getResolvedStateNameAttribute(): ?string
    {
        return static::STATE_NAME_MAP[$this->code] ?? $this->state_name;
    }

    /**
     * Users who have a role assigned to this team.
     */
    public function members(): HasMany
    {
        return $this->hasMany(
            User::class,
            'id',
            'id' // virtual — resolved via model_has_roles pivot
        );
    }

    /**
     * Scope to only active teams.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
