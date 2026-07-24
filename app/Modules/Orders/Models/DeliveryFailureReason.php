<?php

namespace App\Modules\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Users\Models\User;
use Illuminate\Support\Facades\Auth;

class DeliveryFailureReason extends Model
{
    protected $fillable = [
        'reason',
        'is_active',
        'created_by',
        'updated_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by = Auth::id();
                $model->updated_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });
    }
}
