<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFulfillmentHubRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                => ['sometimes', 'required', 'string', 'max:255'],
            'last_call_agent_id'  => ['nullable', 'integer', 'exists:users,id'],
            'vendor_ids'          => ['nullable', 'array'],
            'vendor_ids.*'        => ['integer', 'exists:users,id'],
            'agent_ids'           => ['nullable', 'array'],
            'agent_ids.*'         => ['integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'              => 'The hub name is required.',
            'name.max'                   => 'The hub name may not exceed 255 characters.',
            'last_call_agent_id.exists'  => 'The selected last-call agent does not exist.',
            'vendor_ids.array'           => 'Vendors must be provided as a list.',
            'vendor_ids.*.exists'        => 'One or more selected vendors do not exist.',
            'agent_ids.array'            => 'Agents must be provided as a list.',
            'agent_ids.*.exists'         => 'One or more selected agents do not exist.',
        ];
    }
}
