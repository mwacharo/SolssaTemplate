<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmailTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only authenticated users can create templates
        return true;

        // Or if you use role/permission system:
        // return auth()->user()?->can('create-email-template');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:100', 'unique:email_templates,name'],
            'subject'      => ['required', 'string', 'max:255'],
            'body'         => ['required', 'string'],
            'placeholders' => ['nullable', 'array'],
            'placeholders.*' => ['string', 'max:50'], // each placeholder name
        ];
    }

    /**
     * Custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'name.required'     => 'The template name is required.',
            'name.unique'       => 'A template with this name already exists.',
            'subject.required'  => 'The subject line is required.',
            'body.required'     => 'The template body cannot be empty.',
            'placeholders.array'=> 'Placeholders must be an array of field names.',
        ];
    }
}
