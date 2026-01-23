<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow all authenticated users to create expense categories.
        // Adjust as needed (e.g. check permissions/roles).


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
            'name' => [
                'required',
                'string',
                'max:255',
                // Ensure the name is unique on the expense_categories table.
                // Change the table/column name if your migrations use a different name.
                Rule::unique('expense_categories', 'name'),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
