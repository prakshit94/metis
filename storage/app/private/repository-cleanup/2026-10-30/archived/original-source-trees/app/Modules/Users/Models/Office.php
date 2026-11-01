<?php

declare(strict_types=1);

namespace App\Modules\Users\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Office extends Model
{
    protected $fillable = ['team_id', 'name', 'code', 'address', 'city', 'pincode', 'description', 'is_active',
        'address_line_1', 'address_line_2', 'village_id', 'village_name', 'post_office', 'taluka', 'district', 'state'];
    protected $casts = ['is_active' => 'boolean', 'team_id' => 'integer', 'village_id' => 'integer'];

    public function team(): BelongsTo { return $this->belongsTo(Team::class); }
    public function members(): HasMany { return $this->hasMany(User::class); }
    public function orders(): HasMany { return $this->hasMany(\App\Modules\Orders\Models\Order::class); }
}
