<?php

namespace App\Http\Requests\Api\V1\Products;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class ImportProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('create products') || auth()->user()->can('create own products');
    }

    public function rules(): array
    {
        return [
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
            'vendor_id' => 'sometimes|exists:vendors,id',
            'update_existing' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'csv_file.required' => 'CSV file is required.',
            'csv_file.mimes' => 'File must be a CSV file.',
            'csv_file.max' => 'File size must not exceed 10MB.',
            'vendor_id.exists' => 'Selected vendor does not exist.',
        ];
    }

    /**
     * Force JSON response instead of redirect
     */
    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator, response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422));
    }

    /**
     * Force JSON response for unauthorized access
     */
    protected function failedAuthorization()
    {
        throw new AuthorizationException('You are not authorized to import products.');
    }
}
