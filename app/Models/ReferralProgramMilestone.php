<?php

namespace App\Models;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

use Illuminate\Database\Eloquent\Model;


class ReferralProgramMilestone extends Model implements Auditable
{
    use AuditableTrait;
    protected $fillable = ['referral_program_id', 'required_referrals', 'reward_type', 'reward_value'];

    public function program()
    {
        return $this->belongsTo(ReferralProgram::class, 'referral_program_id');
    }
}
