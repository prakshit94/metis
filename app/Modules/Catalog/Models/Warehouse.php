<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

use App\Modules\Core\Models\Village;
use App\Modules\Inventory\Models\Stock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model implements Auditable
{
    use AuditableTrait;
    use SoftDeletes;


    protected $fillable = [
        'name', 'company_name', 'gstin', 'phone', 'email', 'reference_no', 'seed_lic_no', 'pesti_lic_no',
        'code', 'address', 'address_line_1', 'address_line_2',
        'village_id', 'village_name', 'post_office', 'taluka', 'city',
        'state', 'pincode', 'status', 'is_default', 'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function village()
    {
        return $this->belongsTo(Village::class);
    }

    public function orders()
    {
        return $this->hasMany(\App\Modules\Orders\Models\Order::class);
    }
}
