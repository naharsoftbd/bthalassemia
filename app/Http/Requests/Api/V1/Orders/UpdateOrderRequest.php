<?php

namespace App\Http\Requests\Api\V1\Orders;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware and controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $order = $this->route('order');

        return [
            // Order status updates (Admin/Vendor)
            'status' => [
                'sometimes',
                'string',
                Rule::in(['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded']),
                function ($attribute, $value, $fail) use ($order) {
                    // Prevent status regression
                    $statusHierarchy = [
                        'pending' => 1,
                        'confirmed' => 2,
                        'processing' => 3,
                        'shipped' => 4,
                        'delivered' => 5,
                        'cancelled' => 0,
                        'refunded' => 0,
                    ];

                    $currentStatusLevel = $statusHierarchy[$order->status] ?? 0;
                    $newStatusLevel = $statusHierarchy[$value] ?? 0;

                    if ($newStatusLevel < $currentStatusLevel && $value !== 'cancelled' && $value !== 'refunded') {
                        $fail('Cannot revert order status to a previous state.');
                    }
                },
            ],

            // Payment status updates (Admin)
            'payment_status' => [
                'sometimes',
                'string',
                Rule::in(['pending', 'paid', 'failed', 'refunded']),
            ],

            // Shipping information
            'tracking_number' => 'sometimes|nullable|string|max:100',
            'shipping_method' => 'sometimes|nullable|string|max:100',
            'shipping_notes' => 'sometimes|nullable|string|max:1000',

            // Admin notes
            'admin_notes' => 'sometimes|nullable|string|max:1000',

            // Customer information updates
            'customer_phone' => 'sometimes|string|max:20',
            'shipping_address' => 'sometimes|array',
            'shipping_address.street' => 'required_with:shipping_address|string|max:255',
            'shipping_address.city' => 'required_with:shipping_address|string|max:255',
            'shipping_address.state' => 'required_with:shipping_address|string|max:255',
            'shipping_address.country' => 'required_with:shipping_address|string|max:255',
            'shipping_address.zip_code' => 'required_with:shipping_address|string|max:20',

            'billing_address' => 'sometimes|array',
            'billing_address.street' => 'required_with:billing_address|string|max:255',
            'billing_address.city' => 'required_with:billing_address|string|max:255',
            'billing_address.state' => 'required_with:billing_address|string|max:255',
            'billing_address.country' => 'required_with:billing_address|string|max:255',
            'billing_address.zip_code' => 'required_with:billing_address|string|max:20',

            // Payment information
            'transaction_id' => 'sometimes|nullable|string|max:100',
            'payment_notes' => 'sometimes|nullable|string|max:1000',
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
            'status.in' => 'The selected status is invalid.',
            'payment_status.in' => 'The selected payment status is invalid.',
            'status.*' => 'Cannot revert order status to a previous state.',
            'shipping_address.required_with' => 'Shipping address fields are required when updating address.',
            'billing_address.required_with' => 'Billing address fields are required when updating address.',
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
            'status' => 'order status',
            'payment_status' => 'payment status',
            'tracking_number' => 'tracking number',
            'shipping_method' => 'shipping method',
            'shipping_notes' => 'shipping notes',
            'admin_notes' => 'admin notes',
            'customer_phone' => 'customer phone',
            'shipping_address.street' => 'street address',
            'shipping_address.city' => 'city',
            'shipping_address.state' => 'state',
            'shipping_address.country' => 'country',
            'shipping_address.zip_code' => 'zip code',
            'transaction_id' => 'transaction ID',
            'payment_notes' => 'payment notes',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
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

        // Trim string values
        $this->merge([
            'tracking_number' => $this->tracking_number ? trim($this->tracking_number) : null,
            'shipping_method' => $this->shipping_method ? trim($this->shipping_method) : null,
            'transaction_id' => $this->transaction_id ? trim($this->transaction_id) : null,
        ]);
    }

    /**
     * Configure the validator instance.
     */
    protected function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $order = $this->route('order');

            // Validate that order can be updated
            if (!$this->validateOrderCanBeUpdated($order)) {
                $validator->errors()->add('order', 'This order cannot be updated in its current state.');
            }

            // Validate role-based permissions
            $this->validateRoleBasedPermissions($validator, $order);
        });
    }

    /**
     * Validate that the order can be updated based on its current state.
     */
    protected function validateOrderCanBeUpdated($order): bool
    {
        // Cannot update cancelled or refunded orders
        if (in_array($order->status, ['cancelled', 'refunded'])) {
            return false;
        }

        // Specific validations based on what's being updated
        if ($this->has('status')) {
            return $this->canUpdateStatus($order, $this->status);
        }

        return true;
    }

    /**
     * Check if status update is allowed.
     */
    protected function canUpdateStatus($order, $newStatus): bool
    {
        // Customers can only cancel their own pending orders
        if (auth()->user()->hasRole('customer')) {
            return $newStatus === 'cancelled' && 
                   $order->status === 'pending' && 
                   $order->user_id === auth()->id();
        }

        // Vendors can only update fulfillment status through specific endpoints
        if (auth()->user()->hasRole('vendor')) {
            return false; // Vendors should use fulfillment status endpoints
        }

        return true; // Admin can update any status
    }

    /**
     * Validate role-based permissions for updates.
     */
    protected function validateRoleBasedPermissions($validator, $order): void
    {
        $user = auth()->user();

        // Customers can only update their own orders and only specific fields
        if ($user->hasRole('customer') && $order->user_id !== $user->id) {
            $validator->errors()->add('order', 'You can only update your own orders.');
            return;
        }

        if ($user->hasRole('customer')) {
            $allowedFields = ['customer_phone', 'shipping_address', 'billing_address'];
            $disallowedFields = array_diff(array_keys($this->all()), $allowedFields);
            
            if (!empty($disallowedFields)) {
                foreach ($disallowedFields as $field) {
                    $validator->errors()->add($field, 'You are not authorized to update this field.');
                }
            }
        }

        // Vendors can only update fulfillment-related fields through specific endpoints
        if ($user->hasRole('vendor')) {
            $validator->errors()->add('order', 'Vendors must use fulfillment endpoints to update order items.');
        }
    }
}