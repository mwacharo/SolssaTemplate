<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSellerExpenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow all authenticated users (adjust if needed)
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'vendor_id'              => 'sometimes|integer|exists:users,id',
            'description'            => 'nullable|string|max:500',
            'amount'                 => 'sometimes|numeric|min:0',
            'expense_type'           => 'sometimes|string|in:transport,warehouse,purchase,other,expense,income',
            'remittance_id'          => 'nullable|integer|exists:remittance,id',
            'country_id'             => 'sometimes|integer|exists:countries,id',
            'source_country_id'      => 'nullable|integer|exists:countries,id',
            // 'destination_country_id' => 'sometimes|integer|exists:countries,id',
            'status'                 => 'sometimes|string|in:pending,approved,rejected,paid,applied,not_applied',
            'incurred_on'            => 'sometimes|date|before_or_equal:today',
        ];
    }
}
