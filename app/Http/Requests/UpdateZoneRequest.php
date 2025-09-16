<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateZoneRequest extends FormRequest
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
        //   'country_id' => 'required|integer|exists:countries,id',
          // 'state_id' => 'nullable|integer|exists:states,id',
          'city_id' => 'nullable|integer|exists:cities,id',
          'latitude' => 'nullable|numeric|between:-90,90',
          'longitude' => 'nullable|numeric|between:-180,180',
          'population' => 'nullable|integer|min:0',
       ];
    }
}
