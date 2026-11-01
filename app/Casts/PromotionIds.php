<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/** Read legacy double-encoded targets while writing only canonical JSON arrays. */
class PromotionIds implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): array
    {
        for ($depth = 0; is_string($value) && $depth < 3; $depth++) {
            $value = json_decode($value, true);
        }
        return is_array($value) ? array_values(array_unique(array_map('intval', $value))) : [];
    }

    public function set($model, string $key, $value, array $attributes): ?string
    {
        return $value === null ? null : json_encode($this->get($model, $key, $value, $attributes), JSON_THROW_ON_ERROR);
    }
}
