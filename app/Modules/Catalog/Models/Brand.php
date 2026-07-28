<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model implements Auditable
{
    use AuditableTrait;
    use SoftDeletes;


    protected $fillable = [
        'name',
        'slug',
        'image',
        'logo',
        'status',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
