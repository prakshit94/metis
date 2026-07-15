<?php

namespace App\Modules\Orders\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    protected $fillable = [
        'shipment_no',
        'order_id',
        'carrier_name',
        'tracking_no',
        'status',
        'shipped_at',
        'delivered_at',
    ];

    protected $casts = [
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function events()
    {
        return $this->hasMany(ShipmentTrackingEvent::class);
    }

    public static function generateShipmentNo(): string
    {
        $prefix = 'SHP-' . now()->format('dmYHi');
        $lastShipment = self::where('shipment_no', 'like', $prefix . '-%')
            ->orderBy('shipment_no', 'desc')
            ->first();

        if ($lastShipment) {
            $parts = explode('-', $lastShipment->shipment_no);
            $lastNum = (int) end($parts);
            $nextNum = str_pad($lastNum + 1, 2, '0', STR_PAD_LEFT);
            return $prefix . '-' . $nextNum;
        }

        return $prefix . '-01';
    }
}
