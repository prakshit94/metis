<?php

namespace App\Storefront\Models;

class Product extends \App\Modules\Catalog\Models\Product
{
    public function getDisplayPriceAttribute(): float
    {
        return $this->selling_price_inc_gst;
    }

    public function getImagesAttribute()
    {
        if (! $this->image_path) return collect();

        return collect([(object) [
            'id' => 1,
            'url' => route('shop.image', [
                'id' => $this->id,
                'image' => 1,
                'v' => substr(sha1($this->image_path.'|'.$this->updated_at), 0, 12),
            ], false),
        ]]);
    }

    public function getCurrencyAttribute(): string
    {
        return 'INR';
    }
}
