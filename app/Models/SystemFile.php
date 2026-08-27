<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemFile extends Model
{
    protected $fillable = [
        'original_name',
        'filename',
        'mime_type',
        'size',
        'path'
    ];
}
