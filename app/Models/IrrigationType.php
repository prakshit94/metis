<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class IrrigationType extends Model implements Auditable
{
    use AuditableTrait;

    protected $fillable = ['name', 'is_active'];
}
