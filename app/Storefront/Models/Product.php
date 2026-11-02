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
        return collect($this->imagePaths())->map(fn ($path, $index) => (object) [
            'id' => $index + 1,
            'url' => route('shop.image', [
                'id' => $this->id,
                'image' => $index + 1,
                'v' => substr(sha1($path.'|'.$this->updated_at), 0, 12),
            ], false),
        ]);
    }

    public function getCurrencyAttribute(): string
    {
        return 'INR';
    }
}
