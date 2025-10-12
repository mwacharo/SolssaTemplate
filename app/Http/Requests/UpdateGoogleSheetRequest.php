<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGoogleSheetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    // public function authorize(): bool
    // {
    //     // Update this with your actual authorization logic
    //     return auth()->check() && auth()->user()->can('manage_google_sheets');
    // }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sheet_name' => 'required|string|max:255',
            'post_spreadsheet_id' => 'sometimes|string|max:255',
            'active' => 'sometimes|boolean',
            'auto_sync' => 'sometimes|boolean',
            'sync_all' => 'sometimes|boolean',
            'sync_interval' => 'sometimes|integer|min:5|max:1440',
            'order_prefix' => 'nullable|string|max:50',
            'vendor_id' => 'sometimes|integer|exists:users,id',
            'is_current' => 'sometimes|boolean',
            'country_id' => 'sometimes|integer|exists:countries,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'sheet_name.required' => 'A sheet name is required',
            'post_spreadsheet_id.required' => 'A Google Spreadsheet ID is required',
            'sync_interval.min' => 'Sync interval must be at least 5 minutes',
            'sync_interval.max' => 'Sync interval cannot exceed 24 hours (1440 minutes)',
            'vendor_id.exists' => 'The selected vendor does not exist',
            'couintry_id.exists' => 'The selected country does not exist',
        ];
    }
}