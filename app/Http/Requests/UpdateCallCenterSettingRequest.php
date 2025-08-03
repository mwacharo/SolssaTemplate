<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCallCenterSettingRequest extends FormRequest
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
        //   'country_id' => 'required|exists:countries,id',
          'username' => 'required|string|max:255',
          'api_key' => 'required|string|max:255',
          'phone' => 'required|string|max:20',
          'sandbox' => 'boolean',

          'default_voice' => 'required|string|in:woman,man',
          'timeout' => 'required|integer|min:1',
          'recording_enabled' => 'boolean',

          'welcome_message' => 'nullable|string',
          'no_input_message' => 'nullable|string',
          'invalid_option_message' => 'nullable|string',
          'connecting_agent_message' => 'nullable|string',
          'agents_busy_message' => 'nullable|string',
          'voicemail_prompt' => 'nullable|string',

          'callback_url' => 'nullable|url',
          'event_callback_url' => 'nullable|url',
          'ringback_tone' => 'nullable|string|max:255',
          'voicemail_callback' => 'nullable|url',

          'fallback_number' => 'nullable|string|max:20',
          'default_forward_number' => 'nullable|string|max:20',

          'debug_mode' => 'boolean',
          'log_level' => 'required|string|in:info,debug,warning,error',
       ];
    }
}
