<?php

namespace App\Modules\Orders\Models;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

use Illuminate\Database\Eloquent\Model;


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
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }
}
