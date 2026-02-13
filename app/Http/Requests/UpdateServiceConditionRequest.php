<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceConditionRequest extends FormRequest
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
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'condition_type_id' => ['required', 'integer', 'exists:condition_types,id'],
            'min_value' => ['nullable', 'numeric', 'lte:max_value'],
            'max_value' => ['nullable', 'numeric', 'gte:min_value'],
            'operator' => ['nullable', 'string', 'in:=,>,<,>=,<=,!='],
            'rate' => ['nullable', 'numeric'],
            'rate_type' => ['nullable', 'string', 'in:fixed,percentage'],
            'value' => ['nullable', 'numeric'],
            'unit' => ['nullable', 'string', 'max:50'],
            'priority' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
