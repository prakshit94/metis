<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class ProductAttribute extends Model implements Auditable
{
    use AuditableTrait;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'status',
        'is_filterable',
    ];

    protected $casts = [
        'is_filterable' => 'boolean',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }
}
