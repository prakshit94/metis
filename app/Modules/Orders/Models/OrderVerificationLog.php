<?php

namespace App\Modules\Orders\Models;

use App\Modules\Users\Models\User;


use Illuminate\Database\Eloquent\Model;

class OrderVerificationLog extends Model
{
    public const OUTCOMES = [
        'call_not_picked'    => 'Call Not Picked',
        'customer_confirmed' => 'Customer Confirmed Order',
        'mark_processing'    => 'Mark as Processing',
        'dispatch_order'     => 'Dispatch Order',
        'mark_delivered'     => 'Mark as Delivered',
        'reschedule_delivery'=> 'Reschedule Delivery',
        'next_followup_call' => 'Next Follow-up Call',
        'cancel_order'       => 'Cancel Order',
        'return_order'       => 'Return Order',
        'wrong_number'       => 'Wrong Number',
        'other'              => 'Other',
    ];

    protected $fillable = [
        'order_id',
        'outcome',
        'remark',
        'follow_up_at',
        'created_by',
    ];

    protected $casts = [
        'follow_up_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getOutcomeLabelAttribute(): string
    {
        return self::OUTCOMES[$this->outcome] ?? ucfirst(str_replace('_', ' ', $this->outcome));
    }
}
