<?php

namespace App\Modules\Orders\Models;

use App\Modules\Catalog\Models\Service;
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
        'delivered_by',
        'delivery_attempts',
        'next_followup_date',
        'reschedule_reason',
    ];

    protected $casts = [
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'next_followup_date' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'carrier_name', 'name');
    }

    public function events()
    {
        return $this->hasMany(ShipmentTrackingEvent::class);
    }

    public static function generateShipmentNo(?Order $order = null): string
    {
        if ($order && $order->order_no) {
            $baseNo = str_replace('ORD-', 'SHP-', $order->order_no);
            if ($baseNo === $order->order_no) {
                $baseNo = 'SHP-'.$order->order_no;
            }
            $count = self::where('order_id', $order->id)->count();
            if ($count > 0) {
                return $baseNo.'-'.($count + 1);
            }

            return $baseNo;
        }

        $prefix = 'SHP-'.now()->format('dmYHi');
        $lastShipment = self::where('shipment_no', 'like', $prefix.'-%')
            ->orderBy('shipment_no', 'desc')
            ->first();

        if ($lastShipment) {
            $parts = explode('-', $lastShipment->shipment_no);
            $lastNum = (int) end($parts);
            $nextNum = str_pad($lastNum + 1, 2, '0', STR_PAD_LEFT);

            return $prefix.'-'.$nextNum;
        }

        return $prefix.'-01';
    }
}
