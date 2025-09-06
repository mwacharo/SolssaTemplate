<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // return false;
            return auth()->check(); // Only allow authenticated users

            // return auth()->user()?->hasRole('admin'); // Only allow admins

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
            'client_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:255',
            'alt_number' => 'nullable|string|max:255',
            'country_code' => 'nullable|string|max:5',
            // 'language' => 'required|string|max:5',
            // 'is_active' => 'required|boolean',
            'status' => 'nullable|string|max:255',
            'timezone' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->user,
            // 'country_id' => 'required|exists:countries,id',
        ];
    }
}
