<?php

namespace App\Modules\Users\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];
}
