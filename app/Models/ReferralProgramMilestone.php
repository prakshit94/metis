<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralProgramMilestone extends Model
{
    protected $fillable = ['referral_program_id', 'required_referrals', 'reward_type', 'reward_value'];

    public function program()
    {
        return $this->belongsTo(ReferralProgram::class, 'referral_program_id');
    }
}
