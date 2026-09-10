<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndiaPostAwbTracking extends Model
{
    protected $fillable = [
        'range_id',
        'office_id',
        'barcode',
        'order_id',
        'status',
    ];
}
