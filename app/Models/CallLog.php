<?php

namespace App\Models;

use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class CallLog extends Model implements Auditable
{
    use AuditableTrait;

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
        return $this->belongsTo(User::class, 'agent_id');
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
