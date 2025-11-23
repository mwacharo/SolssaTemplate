<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContactRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Adjust as needed (e.g. policy checks or auth()->user()->can(...))
     */
    public function authorize(): bool
    {
        // Allow all authorized users to make this request.
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
            'name'                => ['sometimes', 'required', 'string', 'max:255'],
            'email'               => ['sometimes', 'nullable', 'string', 'email', 'max:255'],
            'phone'               => ['sometimes', 'nullable', 'string', 'max:50'],
            'alt_phone'           => ['sometimes', 'nullable', 'string', 'max:50'],
            'address'             => ['sometimes', 'nullable', 'string', 'max:500'],
            'country_name'        => ['sometimes', 'nullable', 'string', 'max:100'],
            'state_name'          => ['sometimes', 'nullable', 'string', 'max:100'],
            'city'                => ['sometimes', 'nullable', 'string', 'max:100'],
            'zip_code'            => ['sometimes', 'nullable', 'string', 'max:30'],
            'type'                => ['sometimes', 'nullable', 'string', 'max:100'],
            'company_name'        => ['sometimes', 'nullable', 'string', 'max:255'],
            'job_title'           => ['sometimes', 'nullable', 'string', 'max:255'],
            'whatsapp'            => ['sometimes', 'nullable', 'string', 'max:100'],
            'linkedin'            => ['sometimes', 'nullable', 'url', 'max:255'],
            'telegram'            => ['sometimes', 'nullable', 'string', 'max:255'],
            'facebook'            => ['sometimes', 'nullable', 'url', 'max:255'],
            'twitter'             => ['sometimes', 'nullable', 'url', 'max:255'],
            'instagram'           => ['sometimes', 'nullable', 'url', 'max:255'],
            'wechat'              => ['sometimes', 'nullable', 'string', 'max:255'],
            'snapchat'            => ['sometimes', 'nullable', 'string', 'max:255'],
            'tiktok'              => ['sometimes', 'nullable', 'url', 'max:255'],
            'youtube'             => ['sometimes', 'nullable', 'url', 'max:255'],
            'pinterest'           => ['sometimes', 'nullable', 'url', 'max:255'],
            'reddit'              => ['sometimes', 'nullable', 'url', 'max:255'],
            'consent_to_contact'  => ['sometimes', 'nullable', 'boolean'],
            'consent_given_at'    => ['sometimes', 'nullable', 'date'],
            // 'tags'                => ['sometimes', 'nullable', 'array'],
            // 'tags.*'              => ['sometimes', 'string', 'max:100'],
            'profile_picture'     => ['sometimes', 'nullable', 'image', 'max:2048'],
            'notes'               => ['sometimes', 'nullable', 'string', 'max:2000'],
            // 'status'              => ['sometimes', 'nullable', 'string', 'max:50'],

            // Ensure that if a contactable_type is provided, contactable_id must be present (and numeric),
            // and likewise if an id is provided the type must be present.
            // 'contactable_id'      => ['sometimes', 'nullable', 'required_with:contactable_type', 'integer'],
            // 'contactable_type'    => ['sometimes', 'nullable', 'required_with:contactable_id', 'string', 'max:255'],
        ];
    }
}
