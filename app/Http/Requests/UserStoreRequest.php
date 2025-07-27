<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserStoreRequest extends FormRequest
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
            //
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            // 'password' => 'required|min:6',
            'country_id' => 'required|exists:countries,id',
            // 'phone' => 'nullable|string|max:15',
            // 'roles' => 'array',
        ];
    }
}
