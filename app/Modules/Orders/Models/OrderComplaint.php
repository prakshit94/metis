<?php

declare(strict_types=1);

namespace App\Modules\Orders\Models;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\Customers\Models\Party;
use App\Modules\Users\Models\User;

class OrderComplaint extends Model implements Auditable
{
    use AuditableTrait;
    use SoftDeletes;

    protected $fillable = [
        'order_id',
        'customer_id',
        'assigned_to',
        'complaint_number',
        'category',
        'priority',
        'status',
        'subject',
        'description',
        'resolution_notes',
        'resolved_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function (self $complaint) {
            $complaint->created_by = $complaint->created_by ?? auth()->id();
            if (empty($complaint->complaint_number)) {
                $complaint->complaint_number = 'CMP-' . strtoupper(uniqid());
            }
        });

        static::updating(function (self $complaint) {
            $complaint->updated_by = auth()->id() ?? $complaint->updated_by;
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'customer_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function statusLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OrderComplaintStatusLog::class, 'order_complaint_id');
    }

    public function replies(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OrderComplaintReply::class, 'order_complaint_id');
    }
}
