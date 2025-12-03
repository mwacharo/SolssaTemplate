<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSellerExpenseRequest extends FormRequest
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
            'vendor_id'              => 'required|integer|exists:users,id',
            'description'            => 'nullable|string|max:500',
            'amount'                 => 'required|numeric|min:0',
            'expense_type'           => 'required|string|in:transport,warehouse,purchase,other,expense,income',
            'remittance_id'          => 'nullable|integer|exists:remittance,id',
            'country_id'             => 'required|integer|exists:countries,id',
            'source_country_id'      => 'nullable|integer|exists:countries,id',
            'destination_country_id' => 'nullable|integer|exists:countries,id',
            'status'                 => 'required|string|in:pending,approved,rejected,paid,applied,not_applied',
            'incurred_on'            => 'required|date|before_or_equal:today',
        ];
    }
}
