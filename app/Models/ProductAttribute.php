<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductAttribute extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'status',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }
}
