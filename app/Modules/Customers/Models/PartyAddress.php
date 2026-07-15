<?php

declare(strict_types=1);

namespace App\Modules\Customers\Models;

use App\Modules\Core\Models\Village;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartyAddress extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'party_id',
        'label',
        'address_line_1',
        'address_line_2',
        'village_id',
        'city',
        'state',
        'pincode',
        'status',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'party_id');
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }
}
