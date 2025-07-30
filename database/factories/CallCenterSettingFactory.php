<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CallCenterSetting>
 */
class CallCenterSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    // public function definition(): array
    // {
    //     return [
    //         //
    //     ];
    // }



    public function definition(): array
{
    return [
        'country_id' => \App\Models\Country::factory(),
        'username' => 'sandbox',
        'api_key' => 'fakeapikey',
        'phone' => '+254711000000',
        'sandbox' => true,

        'default_voice' => 'woman',
        'timeout' => 3,
        'recording_enabled' => true,

        'welcome_message' => 'Welcome to your company. Your trusted service provider.',
        'no_input_message' => 'We did not receive any input. Goodbye.',
        'invalid_option_message' => 'Invalid option selected. Please try again.',
        'connecting_agent_message' => 'Connecting you to an agent.',
        'agents_busy_message' => 'All agents are currently busy. Please leave a message.',
        'voicemail_prompt' => 'Please leave a message after the tone.',

        'callback_url' => 'https://yourdomain.com/api/v1/africastalking-handle-callback',
        'event_callback_url' => 'https://yourdomain.com/api/v1/africastalking-handle-event',
        'ringback_tone' => 'https://yourdomain.com/storage/ringtones/office_phone.mp3',
        'voicemail_callback' => 'https://yourdomain.com/api/v1/africastalking-handle-event',

        'fallback_number' => '+254700000000',
        'default_forward_number' => '+254700000001',

        'debug_mode' => false,
        'log_level' => 'info',
    ];
}

}
