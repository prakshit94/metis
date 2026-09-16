<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Modules\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Supplier extends Model
 implements Auditable{
    use LogsActivity;
    use AuditableTrait;

    use HasFactory, SoftDeletes;

    protected $table = 'suppliers';

    protected $fillable = [
        'uuid',
        'party_code',
        'firstname',
        'lastname',
        'email',
        'phone',
        'company_name',
        'gst_no',
        'pan_no',
        'credit_limit',
        'credit_days',
        'status',
        'is_active',
        'internal_notes',
        'address_line_1',
        'address_line_2',
        'village_id',
        'village_name',
        'post_office',
        'taluka',
        'district',
        'city',
        'state',
        'pincode',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'credit_limit' => 'decimal:2',
        'credit_days' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->party_code)) {
                $lastSupplier = static::whereRaw("party_code REGEXP '^SUP-[0-9]+$'")
                    ->orderByRaw("CAST(SUBSTRING(party_code, 5) AS UNSIGNED) DESC")
                    ->first();
                $nextSeq = 1;
                if ($lastSupplier && preg_match('/^SUP-(\d+)$/', (string) $lastSupplier->party_code, $matches)) {
                    $nextSeq = intval($matches[1]) + 1;
                }
                $model->party_code = 'SUP-'.str_pad((string)$nextSeq, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'supplier_id');
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'supplier_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
