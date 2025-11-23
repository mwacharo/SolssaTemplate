<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow all authorized users to make this request.
        // Adjust as needed (e.g. policy checks or auth()->user()->can(...))
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
            'name'                => ['required', 'string', 'max:255'],
            'email'               => ['nullable', 'string', 'email', 'max:255'],
            'phone'               => ['nullable', 'string', 'max:50'],
            'alt_phone'           => ['nullable', 'string', 'max:50'],
            'address'             => ['nullable', 'string', 'max:500'],
            'country_name'        => ['nullable', 'string', 'max:100'],
            'state_name'          => ['nullable', 'string', 'max:100'],
            'city'                => ['nullable', 'string', 'max:100'],
            'zip_code'            => ['nullable', 'string', 'max:30'],
            'type'                => ['nullable', 'string', 'max:100'],
            'company_name'        => ['nullable', 'string', 'max:255'],
            'job_title'           => ['nullable', 'string', 'max:255'],
            'whatsapp'            => ['nullable', 'string', 'max:100'],
            'linkedin'            => ['nullable', 'url', 'max:255'],
            'telegram'            => ['nullable', 'string', 'max:255'],
            'facebook'            => ['nullable', 'url', 'max:255'],
            'twitter'             => ['nullable', 'url', 'max:255'],
            'instagram'           => ['nullable', 'url', 'max:255'],
            'wechat'              => ['nullable', 'string', 'max:255'],
            'snapchat'            => ['nullable', 'string', 'max:255'],
            'tiktok'              => ['nullable', 'url', 'max:255'],
            'youtube'             => ['nullable', 'url', 'max:255'],
            'pinterest'           => ['nullable', 'url', 'max:255'],
            'reddit'              => ['nullable', 'url', 'max:255'],
            'consent_to_contact'  => ['nullable', 'boolean'],
            'consent_given_at'    => ['nullable', 'date'],
            // 'tags'                => ['nullable', 'array'],
            'tags.*'              => ['string', 'max:100'],
            'profile_picture'     => ['nullable', 'image', 'max:2048'], // max in KB
            'notes'               => ['nullable', 'string', 'max:2000'],
            'status'              => ['nullable', 'string', 'max:50'],
            // 'contactable_id'      => ['nullable', 'integer'],
            // 'contactable_type'    => ['nullable', 'string', 'max:255'],

            // Ensure that if a contactable_type is provided, contactable_id must be present (and numeric),
            // and likewise if an id is provided the type must be present. This prevents inserting NULL
            // into a non-nullable contactable_id DB column.
            'contactable_id'      => ['required_with:contactable_type', 'integer'],
            'contactable_type'    => ['required_with:contactable_id', 'string', 'max:255'],
            
        ];
    }
}
