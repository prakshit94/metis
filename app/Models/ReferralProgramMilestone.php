<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Activitylog\LogOptions;

class ReferralProgramMilestone extends Model implements Auditable
{
    use LogsActivity;
    use AuditableTrait;

    protected $fillable = ['referral_program_id', 'required_referrals', 'reward_type', 'reward_value', 'retired_at'];

    public function program()
    {
        return $this->belongsTo(ReferralProgram::class, 'referral_program_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
