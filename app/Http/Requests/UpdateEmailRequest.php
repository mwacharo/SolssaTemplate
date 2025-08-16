<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmailRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only logged-in users can update emails
        return true;

        // Or add role/permission checks if needed:
        // return auth()->user()?->can('update-email');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['sometimes', 'exists:clients,id'],
            'to'        => ['sometimes', 'email', 'max:255'],
            'subject'   => ['sometimes', 'string', 'max:255'],
            'body'      => ['sometimes', 'string'],
            'status'    => ['sometimes', 'in:draft,sent,scheduled'],
            'sent_at'   => ['nullable', 'date', 'after_or_equal:now'],
        ];
    }

    /**
     * Custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'client_id.exists'   => 'The selected client does not exist.',
            'to.email'           => 'The recipient email must be valid.',
            'subject.string'     => 'The subject must be a text value.',
            'sent_at.after_or_equal' => 'The send date must be in the future or now.',
        ];
    }
}
