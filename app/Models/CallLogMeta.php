<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

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
