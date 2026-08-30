<?php

namespace App\Modules\Users\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Holiday extends Model
 implements Auditable{
    use AuditableTrait;

    protected $fillable = [
        'name',
        'date',
        'type',
    ];

    protected $casts = [
        'date' => 'date',
    ];
}
