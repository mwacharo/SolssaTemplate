<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRateRequest extends FormRequest
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
            'vendor_service_id'     => ['required', 'integer', 'exists:vendor_services,id'],
            'service_condition_id'  => ['required', 'integer', 'exists:service_conditions,id'],
            'custom_rate'           => ['required', 'numeric', 'min:0'],
            'rate_type'             => ['required', 'string', 'in:fixed,percentage'],
        ];
    }
}
