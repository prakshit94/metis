<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class SystemFile extends Model
 implements Auditable{
    use AuditableTrait;

    protected $fillable = [
        'original_name',
        'filename',
        'mime_type',
        'size',
        'path',
    ];
}
