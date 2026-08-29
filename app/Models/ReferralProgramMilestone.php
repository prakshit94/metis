<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class ReferralProgramMilestone extends Model implements Auditable
{
    use AuditableTrait;

    protected $fillable = ['referral_program_id', 'required_referrals', 'reward_type', 'reward_value'];

    public function program()
    {
        return $this->belongsTo(ReferralProgram::class, 'referral_program_id');
    }
}
