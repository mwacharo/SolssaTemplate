<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceRateRequest extends FormRequest
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
            'vendor_service_id'    => ['sometimes', 'integer', 'exists:vendor_services,id'],
            'service_condition_id' => ['sometimes', 'integer', 'exists:service_conditions,id'],
            'custom_rate'          => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'rate_type'            => ['sometimes', 'string', 'in:fixed,percentage'],
        ];
    }
}
