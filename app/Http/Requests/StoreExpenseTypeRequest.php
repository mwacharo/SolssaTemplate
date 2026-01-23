<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseTypeRequest extends FormRequest
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
            'expense_category_id' => ['required', 'integer', 'exists:expense_categories,id'],
            'slug' => ['required', 'string', 'max:255', 'unique:expense_types,slug'],
            'display_name' => ['required', 'string', 'max:255'],
            'is_order_level' => ['sometimes', 'boolean'],
            'default_vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
