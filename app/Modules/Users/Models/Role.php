<?php

declare(strict_types=1);

namespace App\Modules\Users\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role as SpatieRole;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Role extends SpatieRole
 implements Auditable{
    use AuditableTrait;

    use SoftDeletes;

    protected $casts = [
        'deleted_at' => 'datetime',
    ];
}
