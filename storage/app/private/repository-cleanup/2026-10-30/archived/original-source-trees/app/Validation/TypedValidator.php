<?php

declare(strict_types=1);

namespace App\Validation;

use Illuminate\Validation\Validator;

/** Convert only successfully validated integer fields; validation rules remain authoritative. */
class TypedValidator extends Validator
{
    public function validated(): array
    {
        $data = parent::validated();

        foreach ($this->getRules() as $attribute => $rules) {
            if (! $this->hasRule($attribute, ['Integer'])) {
                continue;
            }
            // Split before decoding escaped literal dots, keeping those keys intact.
            $segments = array_map(fn ($segment) => $this->replacePlaceholderInString($segment), explode('.', (string) $attribute));
            $value = &$data;
            foreach ($segments as $segment) {
                if (! is_array($value) || ! array_key_exists($segment, $value)) {
                    unset($value);
                    continue 2;
                }
                $value = &$value[$segment];
            }
            // Nullable/optional empty values retain Laravel's existing behavior.
            if ($value !== null && $value !== '') {
                $value = (int) $value;
            }
            unset($value);
        }

        return $data;
    }
}
