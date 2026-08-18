<?php

namespace App\Modules\Users\Models;

use Illuminate\Database\Eloquent\Model;

class EmploymentType extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = ['name', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
