<?php

namespace App\Models;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

use Illuminate\Database\Eloquent\Model;


class CallTagFormField extends Model implements Auditable
{
    use AuditableTrait;
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
