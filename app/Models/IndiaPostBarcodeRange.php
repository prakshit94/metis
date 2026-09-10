<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IndiaPostBarcodeRange extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'office_id',
        'prefix',
        'start_sequence',
        'end_sequence',
        'current_sequence',
        'status',
    ];
}
