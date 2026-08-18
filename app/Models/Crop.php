<?php

namespace App\Models;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

use Illuminate\Database\Eloquent\Model;


class Crop extends Model implements Auditable
{
    use AuditableTrait;
    protected $fillable = ['name', 'is_active'];
}
