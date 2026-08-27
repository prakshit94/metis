<?php

declare(strict_types=1);

namespace App\Modules\Orders\Models;

use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderComplaintReply extends Model
{
    protected $fillable = [
        'order_complaint_id',
        'user_id',
        'message',
    ];

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(OrderComplaint::class, 'order_complaint_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
