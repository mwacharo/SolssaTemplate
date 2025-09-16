<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCityRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            // 'country_id' => 'required|integer|exists:countries,id',
            // 'state_id' => 'nullable|integer|exists:states,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'population' => 'nullable|integer',
        ];
    }
}
