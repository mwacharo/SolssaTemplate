<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTemplateRequest extends FormRequest
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
            'channel' => 'sometimes|nullable|string|max:255',
            'module' => 'sometimes|nullable|string|max:255',
            'content' => 'required|string',
            'placeholders' => 'sometimes|nullable',
            // 'owner_type' => 'sometimes|nullable|string|max:255',
            // 'owner_id' => 'sometimes|nullable|integer',
        ];
    }
}
