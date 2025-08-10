<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow authenticated users to create tickets
        // return auth()->check();
        return true; // For simplicity, allow all users. Adjust as needed.
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Foreign key relationships (optional)
            'client_id' => ['nullable', 'exists:clients,id'],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],

            // Required client information
            'client_name' => ['required', 'string', 'max:255'],
            'client_phone' => ['nullable', 'string', 'max:20', 'regex:/^[\+\-\(\)\s\d]+$/'],
            'client_email' => ['nullable', 'email', 'max:255'],

            // Ticket details
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'category' => ['required', 'string', 'max:100'],
            'status' => ['sometimes', Rule::in(['open', 'in_progress', 'pending', 'resolved', 'closed'])],

            // Call information
            'call_duration' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'client_name.required' => 'Client name is required.',
            'client_phone.regex' => 'Please enter a valid phone number.',
            'client_email.email' => 'Please enter a valid email address.',
            'subject.required' => 'Ticket subject is required.',
            'subject.max' => 'Subject cannot exceed 255 characters.',
            'description.required' => 'Ticket description is required.',
            'description.max' => 'Description cannot exceed 5000 characters.',
            'priority.required' => 'Priority is required.',
            'priority.in' => 'Priority must be one of: low, medium, high, urgent.',
            'category.required' => 'Category is required.',
            'call_duration.integer' => 'Call duration must be a number.',
            'call_duration.min' => 'Call duration cannot be negative.',
            'call_duration.max' => 'Call duration cannot exceed 9999 minutes.',
            'client_id.exists' => 'Selected client does not exist.',
            'contact_id.exists' => 'Selected contact does not exist.',
            'vendor_id.exists' => 'Selected vendor does not exist.',
            'assigned_user_id.exists' => 'Selected user does not exist.',
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'client_name' => 'client name',
            'client_phone' => 'client phone',
            'client_email' => 'client email',
            'assigned_user_id' => 'assigned user',
            'call_duration' => 'call duration',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default status if not provided
        if (!$this->has('status')) {
            $this->merge([
                'status' => 'open'
            ]);
        }

        // Clean up phone number
        if ($this->has('client_phone') && $this->client_phone) {
            $this->merge([
                'client_phone' => preg_replace('/[^\+\-\(\)\s\d]/', '', $this->client_phone)
            ]);
        }

        // Convert empty strings to null for nullable fields
        $nullableFields = ['client_id', 'contact_id', 'vendor_id', 'assigned_user_id', 'client_phone', 'client_email', 'call_duration'];
        
        foreach ($nullableFields as $field) {
            if ($this->has($field) && $this->$field === '') {
                $this->merge([$field => null]);
            }
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Ensure at least one contact method is provided
            if (empty($this->client_phone) && empty($this->client_email)) {
                $validator->errors()->add('client_contact', 'Either client phone or email must be provided.');
            }

            // Validate that only one relationship type is set
            $relationships = collect(['client_id', 'contact_id', 'vendor_id'])
                ->filter(fn($field) => !empty($this->$field));

            if ($relationships->count() > 1) {
                $validator->errors()->add('relationships', 'A ticket can only belong to one entity (client, contact, or vendor).');
            }
        });
    }
}