<?php

namespace App\Http\Requests\Api\V1\Orders;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Order details
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'sometimes|numeric|min:0',
            'shipping_cost' => 'sometimes|numeric|min:0',
            'discount_amount' => 'sometimes|numeric|min:0',
            'total' => 'required|numeric|min:0.01',

            // Customer information
            'customer_phone' => 'sometimes|string|max:20',
            'shipping_address' => 'required|array',
            'shipping_address.street' => 'required|string|max:255',
            'shipping_address.city' => 'required|string|max:255',
            'shipping_address.state' => 'required|string|max:255',
            'shipping_address.country' => 'required|string|max:255',
            'shipping_address.zip_code' => 'required|string|max:20',

            'billing_address' => 'required|array',
            'billing_address.street' => 'required_with:billing_address|string|max:255',
            'billing_address.city' => 'required_with:billing_address|string|max:255',
            'billing_address.state' => 'required_with:billing_address|string|max:255',
            'billing_address.country' => 'required_with:billing_address|string|max:255',
            'billing_address.zip_code' => 'required_with:billing_address|string|max:20',

            // Payment information
            'payment_method' => 'sometimes|string|max:50',
            'customer_notes' => 'sometimes|string|max:1000',

            // Order items
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where(function ($query) {
                    $query->where('is_active', true);
                }),
            ],
            'items.*.product_variant_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('product_variants', 'id')->where(function ($query) {
                    $query->where('is_active', true);
                }),
            ],
            'items.*.vendor_id' => [
                'required',
                'integer',
                Rule::exists('vendors', 'id')->where('is_approved', true),
            ],
            'items.*.product_name' => 'required|string|max:255',
            'items.*.variant_name' => 'sometimes|nullable|string|max:255',
            'items.*.sku' => 'required|string|max:255',
            'items.*.attributes' => 'sometimes|nullable|array',
            'items.*.unit_price' => 'required|numeric|min:0.01',
            'items.*.compare_at_price' => 'sometimes|nullable|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'At least one order item is required.',
            'items.*.product_id.exists' => 'The selected product is not available or inactive.',
            'items.*.product_variant_id.exists' => 'The selected product variant is not available or inactive.',
            'items.*.vendor_id.exists' => 'The selected vendor is not approved or does not exist.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
            'items.*.quantity.max' => 'Quantity cannot exceed 1000.',
            'shipping_address.required' => 'Shipping address is required.',
            'total.min' => 'Order total must be greater than 0.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'subtotal' => 'subtotal amount',
            'tax_amount' => 'tax amount',
            'shipping_cost' => 'shipping cost',
            'discount_amount' => 'discount amount',
            'total' => 'total amount',
            'customer_phone' => 'customer phone',
            'shipping_address.street' => 'street address',
            'shipping_address.city' => 'city',
            'shipping_address.state' => 'state',
            'shipping_address.country' => 'country',
            'shipping_address.zip_code' => 'zip code',
            'items.*.product_id' => 'product',
            'items.*.product_variant_id' => 'product variant',
            'items.*.vendor_id' => 'vendor',
            'items.*.product_name' => 'product name',
            'items.*.variant_name' => 'variant name',
            'items.*.sku' => 'SKU',
            'items.*.unit_price' => 'unit price',
            'items.*.quantity' => 'quantity',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure numeric values are properly formatted
        $this->merge([
            'subtotal' => (float) $this->subtotal,
            'tax_amount' => (float) ($this->tax_amount ?? 0),
            'shipping_cost' => (float) ($this->shipping_cost ?? 0),
            'discount_amount' => (float) ($this->discount_amount ?? 0),
            'total' => (float) $this->total,
        ]);

        // Format addresses if they are objects
        if ($this->shipping_address && is_object($this->shipping_address)) {
            $this->merge([
                'shipping_address' => (array) $this->shipping_address,
            ]);
        }

        if ($this->billing_address && is_object($this->billing_address)) {
            $this->merge([
                'billing_address' => (array) $this->billing_address,
            ]);
        }

        // Format items array and ensure numeric values
        if ($this->items) {
            $formattedItems = array_map(function ($item) {
                return [
                    'product_id' => (int) $item['product_id'],
                    'product_variant_id' => isset($item['product_variant_id']) ? (int) $item['product_variant_id'] : null,
                    'vendor_id' => (int) $item['vendor_id'],
                    'product_name' => $item['product_name'],
                    'variant_name' => $item['variant_name'] ?? null,
                    'sku' => $item['sku'],
                    'attributes' => $item['attributes'] ?? null,
                    'unit_price' => (float) $item['unit_price'],
                    'compare_at_price' => isset($item['compare_at_price']) ? (float) $item['compare_at_price'] : null,
                    'quantity' => (int) $item['quantity'],
                ];
            }, $this->items);

            $this->merge(['items' => $formattedItems]);
        }
    }

    /**
     * Configure the validator instance.
     */
    protected function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate that total matches calculated total
            if (! $this->validateOrderTotal()) {
                $validator->errors()->add('total', 'The total amount does not match the calculated order total.');
            }

            // Validate stock availability for each item
            $this->validateStockAvailability($validator);
        });
    }

    /**
     * Validate that the order total matches calculated total.
     */
    protected function validateOrderTotal(): bool
    {
        $calculatedTotal = $this->subtotal
            + ($this->tax_amount ?? 0)
            + ($this->shipping_cost ?? 0)
            - ($this->discount_amount ?? 0);

        return abs($calculatedTotal - $this->total) < 0.01; // Allow small floating point differences
    }

    /**
     * Validate stock availability for order items.
     */
    protected function validateStockAvailability($validator): void
    {
        if (! is_array($this->items)) {
            return; // Skip if items are not provided (for required field validation)
        }

        foreach ($this->items as $index => $item) {
            if (isset($item['product_variant_id'])) {
                $variant = \App\Models\ProductVariant::find($item['product_variant_id']);
                if ($variant && $variant->stock < $item['quantity']) {
                    $validator->errors()->add(
                        "items.{$index}.quantity",
                        "Insufficient stock for {$item['product_name']}. Available: {$variant->stock}"
                    );
                }
            } else {
                $product = \App\Models\Product::find($item['product_id']);
                if ($product && $product->variants()->where('stock', '>=', $item['quantity'])->doesntExist()) {
                    $validator->errors()->add(
                        "items.{$index}.quantity",
                        "Insufficient stock for {$item['product_name']}"
                    );
                }
            }
        }
    }
}
