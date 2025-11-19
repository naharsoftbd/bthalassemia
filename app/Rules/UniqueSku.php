<?php

namespace App\Rules;

use App\Models\ProductVariant;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueSku implements ValidationRule
{
    // To track SKUs within the same request
    private static array $seen = [];

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // 1️⃣ Check duplicate in request itself
        if (in_array($value, self::$seen)) {
            $fail("The SKU '{$value}' is duplicated in the request.");

            return;
        }
        self::$seen[] = $value;

        // 2️⃣ Check duplicate in database
        if (ProductVariant::where('sku', $value)->exists()) {
            $fail("The SKU '{$value}' already exists in the database.");
        }
    }
}
