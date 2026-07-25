<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallTag extends Model
{
    protected $fillable = [
        'name',
        'parent_id',
        'level',
        'sort_order',
        'is_active',
    ];

    public function parent()
    {
        return $this->belongsTo(CallTag::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(CallTag::class, 'parent_id');
    }

    public function formFields()
    {
        return $this->hasMany(CallTagFormField::class);
    }
}
