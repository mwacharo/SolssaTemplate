<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseCategoryRequest extends FormRequest
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
        $expenseCategory = $this->route('expense_category') ?? $this->route('expenseCategory') ?? $this->route('id');
        $id = is_object($expenseCategory) ? $expenseCategory->id : $expenseCategory;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('expense_categories', 'name')->ignore($id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
