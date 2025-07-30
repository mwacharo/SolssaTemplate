<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCallCenterSettingRequest extends FormRequest
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
            // 'country_id' => ['required', 'integer', 'exists:countries,id'],
            'username' => ['required', 'string', 'max:255'],
            'api_key' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'sandbox' => ['required', 'boolean'],
            'default_voice' => ['required', 'string', 'in:woman,man'],
            'timeout' => ['required', 'integer', 'min:1'],
            'recording_enabled' => ['required', 'boolean'],
            'welcome_message' => ['nullable', 'string'],
            'no_input_message' => ['nullable', 'string'],
            'invalid_option_message' => ['nullable', 'string'],
            'connecting_agent_message' => ['nullable', 'string'],
            'agents_busy_message' => ['nullable', 'string'],
            'voicemail_prompt' => ['nullable', 'string'],
            'callback_url' => ['nullable', 'string', 'max:255'],
            'event_callback_url' => ['nullable', 'string', 'max:255'],
            'ringback_tone' => ['nullable', 'string', 'max:255'],
            'voicemail_callback' => ['nullable', 'string', 'max:255'],
            'fallback_number' => ['nullable', 'string', 'max:255'],
            'default_forward_number' => ['nullable', 'string', 'max:255'],
            'debug_mode' => ['required', 'boolean'],
            'log_level' => ['required', 'string', 'in:info,debug,warning,error'],
        ];
    }
}
