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
            //


            'name' => 'sometimes|string',
            'email' => 'sometimes|email|unique:users,email,' . $this->user,
            'password' => 'nullable|min:6',
        ];
    }
}
