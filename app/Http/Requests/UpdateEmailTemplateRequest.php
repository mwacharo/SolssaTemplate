<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmailTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only authenticated users can update templates
        return true;

        // Or if you use role/permission system:
        // return auth()->user()?->can('update-email-template');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'         => ['sometimes', 'string', 'max:100', 'unique:email_templates,name,' . $this->route('email_template')],
            'subject'      => ['sometimes', 'string', 'max:255'],
            'body'         => ['sometimes', 'string'],
            'placeholders' => ['nullable', 'array'],
            'placeholders.*' => ['string', 'max:50'],
        ];
    }

    /**
     * Custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'name.unique'       => 'A template with this name already exists.',
            'placeholders.array'=> 'Placeholders must be an array of field names.',
        ];
    }
}
