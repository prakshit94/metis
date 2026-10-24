<?php

namespace App\Modules\Users\Models;

use App\Concerns\LogsActivity;
use App\Modules\Users\Services\AutomaticCoRequest;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Activitylog\LogOptions;

class Attendance extends Model implements Auditable
{
    use AuditableTrait;
    use LogsActivity;
    use SoftDeletes;

    // Synthetic end-of-day closure must not count as an employee checkout for CO.
    public bool $suppressAutomaticCoRequest = false;

    protected static function booted(): void
    {
        static::saved(function (self $attendance) {
            if (! $attendance->suppressAutomaticCoRequest && $attendance->wasChanged(['user_id', 'date', 'check_in', 'check_out', 'status'])) {
                app(AutomaticCoRequest::class)->forAttendance($attendance);
            } elseif (! $attendance->suppressAutomaticCoRequest && $attendance->wasRecentlyCreated) {
                app(AutomaticCoRequest::class)->forAttendance($attendance);
            }
        });
    }

    protected $appends = ['total_time'];

    protected $fillable = [
        'user_id',
        'date',
        'check_in',
        'check_out',
        'status',
        'notes',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'check_in' => 'datetime:H:i',
        'check_out' => 'datetime:H:i',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTotalTimeAttribute(): ?string
    {
        if ($this->check_in && $this->check_out) {
            $checkIn = Carbon::parse($this->check_in);
            $checkOut = Carbon::parse($this->check_out);

            if ($checkOut->lessThan($checkIn)) {
                $checkOut->addDay(); // Handle night shifts
            }

            $diff = $checkIn->diff($checkOut);

            $hours = $diff->h + ($diff->days * 24);
            $minutes = str_pad((string) $diff->i, 2, '0', STR_PAD_LEFT);

            return "{$hours}h {$minutes}m";
        }

        return null;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
