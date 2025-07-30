<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIvrOptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'option_number'   => ['required', 'integer'],
            'description'     => ['required', 'string', 'max:255'],
            'forward_number'  => ['nullable', 'string', 'max:15'],
            'phone_number'    => ['nullable', 'string', 'max:255'],
            'status'          => ['nullable', 'string', 'max:255'],
            'branch_id'       => ['nullable', 'integer'],
            'country_id'      => ['nullable', 'integer'],
        ];
    }
}
