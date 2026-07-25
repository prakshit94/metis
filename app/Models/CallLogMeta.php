<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallLogMeta extends Model
{
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
