<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFulfillmentHubRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:255'],
            // 'country_id'   => ['required', 'integer', 'exists:countries,id'],
            'vendor_ids'   => ['nullable', 'array'],
            'vendor_ids.*' => ['integer', 'exists:users,id'],
            'agent_ids'    => ['nullable', 'array'],
            'agent_ids.*'  => ['integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'       => 'The hub name is required.',
            'name.max'            => 'The hub name may not exceed 255 characters.',
            // 'country_id.required' => 'Please select a country.',
            // 'country_id.exists'   => 'The selected country does not exist.',
            'vendor_ids.array'    => 'Vendors must be provided as a list.',
            'vendor_ids.*.exists' => 'One or more selected vendors do not exist.',
            'agent_ids.array'     => 'Agents must be provided as a list.',
            'agent_ids.*.exists'  => 'One or more selected agents do not exist.',
        ];
    }
}
