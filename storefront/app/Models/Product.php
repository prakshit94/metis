<?php

namespace Storefront\Models;

class Product extends \App\Modules\Catalog\Models\Product
{
    public function getDisplayPriceAttribute(): float
    {
        return $this->selling_price_inc_gst;
    }

    public function getImagesAttribute()
    {
        return $this->image_path ? collect([(object) ['id' => 1]]) : collect();
    }

    public function getCurrencyAttribute(): string
    {
        return 'INR';
    }
}
