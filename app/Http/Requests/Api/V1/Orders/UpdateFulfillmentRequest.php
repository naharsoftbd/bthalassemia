<?php

namespace App\Http\Requests\Api\V1\Orders;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFulfillmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->hasRole('vendor');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fulfillment_status' => [
                'required',
                'string',
                Rule::in(['confirmed', 'processing', 'shipped', 'delivered', 'cancelled']),
            ],
            'tracking_number' => [
                'required_if:fulfillment_status,shipped',
                'nullable',
                'string',
                'max:100',
            ],
            'fulfillment_notes' => 'sometimes|nullable|string|max:1000',
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
            'fulfillment_status.required' => 'Fulfillment status is required.',
            'fulfillment_status.in' => 'The selected fulfillment status is invalid.',
            'tracking_number.required_if' => 'Tracking number is required when marking as shipped.',
            'tracking_number.max' => 'Tracking number may not be greater than 100 characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Trim and format values
        $this->merge([
            'tracking_number' => $this->tracking_number ? trim($this->tracking_number) : null,
            'fulfillment_notes' => $this->fulfillment_notes ? trim($this->fulfillment_notes) : null,
        ]);
    }
}