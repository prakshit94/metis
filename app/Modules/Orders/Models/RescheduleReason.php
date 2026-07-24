<?php

namespace App\Modules\Orders\Models;

use Illuminate\Database\Eloquent\Model;

class RescheduleReason extends Model
{
    protected $fillable = [
        'reason',
        'is_active',
    ];
}
