<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallLog extends Model
{
    protected $fillable = [
        'customer_id',
        'agent_id',
        'tag_l1_id',
        'tag_l2_id',
        'tag_l3_id',
        'notes',
    ];

    public function meta()
    {
        return $this->hasMany(CallLogMeta::class);
    }

    public function metas()
    {
        return $this->hasMany(CallLogMeta::class);
    }

    public function agent()
    {
        return $this->belongsTo(\App\Modules\Users\Models\User::class, 'agent_id');
    }

    public function tagL1()
    {
        return $this->belongsTo(CallTag::class, 'tag_l1_id');
    }

    public function tagL2()
    {
        return $this->belongsTo(CallTag::class, 'tag_l2_id');
    }

    public function tagL3()
    {
        return $this->belongsTo(CallTag::class, 'tag_l3_id');
    }
}
