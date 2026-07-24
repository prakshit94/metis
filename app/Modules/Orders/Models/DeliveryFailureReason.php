<?php

namespace App\Modules\Orders\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryFailureReason extends Model
{
    protected $fillable = [
        'reason',
        'is_active',
    ];
}
