<?php

namespace Database\Factories;

use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        $direction = $this->faker->randomElement(['incoming', 'outgoing']);
        $timestamp = $this->faker->dateTimeBetween('-3 days', 'now');
        $isMedia = $this->faker->boolean(20);

        return [
            'messageable_type' => 'App\Models\User',
            'messageable_id' => $this->faker->numberBetween(1, 10),
            'channel' => 'whatsapp',
            // 'provider' => $this->faker->randomElement(['waatchat', 'meta', 'whapi']),
            'recipient_name' => $this->faker->name,
            'recipient_phone' => $this->faker->e164PhoneNumber,
            'content' => $isMedia ? null : $this->faker->sentence(),
            'status' => 'sent',
            'sent_at' => $timestamp,
            'response_payload' => json_encode(['provider_response' => 'Message sent']),
            'from' => $direction === 'outgoing' ? 'Boxleo' : $this->faker->name,
            'to' => $direction === 'outgoing' ? $this->faker->e164PhoneNumber : 'Boxleo',
            'body' => $isMedia ? null : $this->faker->paragraph,
            'message_type' => $isMedia ? 'media' : 'text',
            'media_url' => $isMedia ? $this->faker->imageUrl() : null,
            'media_mime_type' => $isMedia ? 'image/jpeg' : null,
            'message_status' => $this->faker->randomElement(['pending', 'delivered', 'read']),
            'external_message_id' => Str::uuid(),
            'reply_to_message_id' => null,
            'error_message' => null,
            'timestamp' => $timestamp,
            'direction' => $direction,
            'delivered_at' => $direction === 'outgoing' ? $timestamp : null,
            'read_at' => $direction === 'outgoing' ? $this->faker->dateTimeBetween($timestamp, 'now') : null,
            'failed_at' => null,
            // 'template_id' => $this->faker->boolean(10) ? Str::uuid() : null,
            // 'is_template' => $this->faker->boolean(10),
            // 'whatsapp_message_type' => $this->faker->randomElement(['text', 'image', 'template']),
        ];
    }
}
