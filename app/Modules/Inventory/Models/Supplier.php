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

class Supplier extends Model
 implements Auditable{
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
                $model->party_code = 'SUP-'.strtoupper(Str::random(6));
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
}
