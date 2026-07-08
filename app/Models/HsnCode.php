<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HsnCode extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'description',
        'status',
    ];
}
