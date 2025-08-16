<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmailRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // If you have authentication, check the user permission:
        return true; 
        
        // Or if only admins can send emails:
        // return auth()->user()?->can('send-email');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'to'        => ['required', 'email', 'max:255'],
            'subject'   => ['required', 'string', 'max:255'],
            'body'      => ['required', 'string'],
            'status'    => ['nullable', 'in:draft,sent,scheduled'],
            'sent_at'   => ['nullable', 'date', 'after_or_equal:now'],
        ];
    }

    /**
     * Custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'client_id.required' => 'The client is required.',
            'client_id.exists'   => 'The selected client does not exist.',
            'to.required'        => 'The recipient email is required.',
            'to.email'           => 'The recipient email must be valid.',
            'subject.required'   => 'The email subject is required.',
            'body.required'      => 'The email body cannot be empty.',
            'sent_at.after_or_equal' => 'The send date must be in the future or now.',
        ];
    }
}
