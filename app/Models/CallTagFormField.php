<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallTagFormField extends Model
{
    protected $fillable = [
        'call_tag_id',
        'name',
        'label',
        'type',
        'options',
        'is_required',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    public function tag()
    {
        return $this->belongsTo(CallTag::class, 'call_tag_id');
    }
}
