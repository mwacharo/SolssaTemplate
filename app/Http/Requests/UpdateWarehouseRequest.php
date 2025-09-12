<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWarehouseRequest extends FormRequest
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
          'country_id'     => ['required', 'integer', 'exists:countries,id'],
          'city_id'        => ['required', 'integer', 'exists:cities,id'],
          'zone_id'        => ['required', 'integer', 'exists:zones,id'],
          'name'           => ['required', 'string', 'max:255'],
          'location'       => ['nullable', 'string', 'max:255'],
          'contact_person' => ['nullable', 'string', 'max:255'],
          'phone'          => ['nullable', 'string', 'max:20'],
       ];
    }
}
