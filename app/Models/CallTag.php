<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CallTag extends Model implements Auditable
{
    use LogsActivity;
    use AuditableTrait;

    protected $fillable = [
        'name',
        'parent_id',
        'level',
        'sort_order',
        'is_active',
    ];

    public function parent()
    {
        return $this->belongsTo(CallTag::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(CallTag::class, 'parent_id');
    }

    public function formFields()
    {
        return $this->hasMany(CallTagFormField::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
