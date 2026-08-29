<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class ReferralProgram extends Model implements Auditable
{
    use AuditableTrait;

    protected $fillable = ['name', 'description', 'start_date', 'end_date', 'is_active'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function milestones()
    {
        return $this->hasMany(ReferralProgramMilestone::class);
    }
}
