<?php

namespace App\Services;

use App\Models\CallTagFormField;
use App\Models\Crop;

class CallTagFields
{
    public static function forTag(int $tagId)
    {
        return CallTagFormField::where('call_tag_id', $tagId)->orderBy('sort_order')->orderBy('id')->get()->map(function ($field) {
            if (strtolower($field->name) === 'crop') {
                $field->options = json_encode(Crop::where('is_active', true)->orderBy('name')->pluck('name')->all());
                $field->type = 'multi_select';
            }

            return $field;
        });
    }
}
