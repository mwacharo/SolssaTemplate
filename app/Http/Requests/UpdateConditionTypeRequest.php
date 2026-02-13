<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConditionTypeRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => ['sometimes', 'required', 'string', 'max:255', 'alpha_dash'],
            'input_type' => ['sometimes', 'required', 'string', 'in:text,numeric,select,checkbox,radio'],
            'supports_range' => ['sometimes', 'boolean'],
            'unit' => ['sometimes', 'nullable', 'string', 'max:50'],
            'meta' => ['sometimes', 'nullable', 'array'],
            'meta.min' => ['sometimes', 'nullable', 'numeric', 'gte:0'],
            'meta.step' => ['sometimes', 'nullable', 'numeric', 'gt:0'],
        ];
    }
}
