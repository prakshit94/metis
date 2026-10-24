<?php

namespace App\Modules\Users\Models;

use App\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;

class AttendanceCorrection extends Model
{
    use LogsActivity;

    protected $fillable = [
        'user_id', 'attendance_id', 'date', 'check_in_time', 'check_out_time',
        'attendance_status', 'notes', 'reason', 'original_values', 'status',
        'reviewed_by', 'reviewed_at', 'reviewer_comment',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d', 'check_in_time' => 'datetime:Y-m-d\\TH:i',
        'check_out_time' => 'datetime:Y-m-d\\TH:i', 'original_values' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function attendance() { return $this->belongsTo(Attendance::class); }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
