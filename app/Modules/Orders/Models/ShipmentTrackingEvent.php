<?php

namespace App\Modules\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class ShipmentTrackingEvent extends Model implements Auditable
{
    use AuditableTrait;

    protected $fillable = [
        'shipment_id',
        'event_name',
        'location',
        'description',
        'reschedule_reason',
        'occurred_at',
        'provider',
        'provider_event_code',
        'provider_office_id',
        'provider_remarks',
        'provider_is_rts',
        'provider_payload',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'provider_is_rts' => 'boolean',
        'provider_payload' => 'array',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }
}
