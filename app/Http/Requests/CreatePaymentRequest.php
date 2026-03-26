<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_category' => 'required|string|max:50',
            'customer_no' => 'required|string|max:50',
            'customer_name' => 'required|string|max:100',
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:255',
            'provider' => 'nullable|string|in:BRI,BNI,MANDIRI,BSI',
        ];
    }

    public function messages(): array
    {
        return [
            'service_category.required' => 'Service category is required',
            'customer_no.required' => 'Customer number is required',
            'customer_name.required' => 'Customer name is required',
            'amount.required' => 'Amount is required',
            'amount.numeric' => 'Amount must be a number',
            'amount.min' => 'Amount must be at least 1',
            'provider.in' => 'Provider must be one of: BRI, BNI, MANDIRI, BSI',
        ];
    }
}
