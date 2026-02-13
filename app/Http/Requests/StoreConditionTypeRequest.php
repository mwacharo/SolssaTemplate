<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConditionTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow request by default; change to permission checks as needed.
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
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', 'alpha_dash'],
            'input_type' => ['required', 'string', 'in:text,numeric,select,checkbox,radio'],
            'supports_range' => ['required', 'boolean'],
            'unit' => ['nullable', 'string', 'max:50'],
            'meta' => ['nullable', 'array'],
            'meta.min' => ['nullable', 'numeric', 'gte:0'],
            'meta.step' => ['nullable', 'numeric', 'gt:0'],
        ];
    }
}
