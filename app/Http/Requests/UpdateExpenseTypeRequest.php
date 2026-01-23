<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseTypeRequest extends FormRequest
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
            'expense_category_id' => ['sometimes', 'required', 'integer', 'exists:expense_categories,id'],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                'alpha_dash',
            ],
            'display_name' => ['sometimes', 'required', 'string', 'max:255'],
            'is_order_level' => ['sometimes', 'boolean'],
            'default_vat_rate' => ['sometimes', 'nullable', 'numeric', 'between:0,100'],
        ];
    }
}
