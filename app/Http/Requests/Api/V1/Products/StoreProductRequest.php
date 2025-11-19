<?php

namespace App\Http\Requests\Api\V1\Products;

use App\Rules\UniqueSku;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:products,slug',
            'description' => 'nullable|string',
            'base_price' => 'nullable|numeric|min:0',
            'is_active' => 'sometimes|boolean',
            'variants' => 'nullable|array',
            'variants.*.sku' => ['required', 'string', 'max:255', new UniqueSku],
            'variants.*.name' => 'required|string',
            'variants.*.price' => 'nullable|numeric',
            'variants.*.stock' => 'nullable|integer',
            'variants.*.low_stock_threshold' => 'nullable|integer',
            'variants.*.attributes' => 'nullable|array',
        ];
    }
}
