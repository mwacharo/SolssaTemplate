<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVendorRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:vendors,email'],
            'billing_email' => ['nullable', 'string', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'alt_phone' => ['nullable', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'warehouse_location' => ['nullable', 'string', 'max:255'],
            'preferred_pickup_time' => ['nullable', 'string', 'max:255'],
            'contact_person_name' => ['nullable', 'string', 'max:255'],
            'business_type' => ['nullable', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:255'],
            'website_url' => ['nullable', 'string', 'max:255'],
            'social_media_links' => ['nullable', 'json'],
            'bank_account_info' => ['nullable', 'json'],
            'delivery_mode' => ['required', 'in:pickup,delivery,both'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'integration_id' => ['nullable', 'string', 'max:255'],
            // 'onboarding_stage' => ['required', 'in:pending,active,verified'],
            'last_active_at' => ['nullable', 'date'],
            // 'branch_id' => ['nullable', 'exists:branches,id'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'status' => ['boolean'],
            'notes' => ['nullable', 'string'],
            // 'user_id' => ['required', 'exists:users,id'],
        ];
    }
}
