<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWaybillSettingRequest extends FormRequest
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
          'country_id'    => ['required', 'integer', 'exists:countries,id'],
          'template_name' => ['required', 'string', 'max:255'],
          'options'       => ['nullable', 'array'],
          'name'          => ['required', 'string', 'max:255'],
          'phone'         => ['required', 'string', 'max:20'],
          'email'         => ['required', 'email', 'max:255'],
          'address'       => ['required', 'string', 'max:500'],
          'terms'         => ['nullable', 'string'],
          'footer'        => ['nullable', 'string'],
       ];
    }
}
