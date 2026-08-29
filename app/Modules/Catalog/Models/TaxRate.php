<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class TaxRate extends Model implements Auditable
{
    use AuditableTrait;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'rate',
        'status',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'float',
        'is_active' => 'boolean',
    ];
}
