<?php

namespace App\Models;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

use Illuminate\Database\Eloquent\Model;


class CallLogMeta extends Model implements Auditable
{
    use AuditableTrait;
    protected $fillable = [
        'call_log_id',
        'key',
        'value',
    ];

    public function callLog()
    {
        return $this->belongsTo(CallLog::class);
    }
}
