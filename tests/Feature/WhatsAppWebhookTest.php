<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;



class WhatsAppWebhookTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }


    public function testWebhookStoresIncomingMessage()
{
    $payload = [
        "typeWebhook" => "incomingMessageReceived",
        "senderData" => [
            "chatId" => "254712345678@c.us"
        ],
        "messageData" => [
            "textMessageData" => [
                "textMessage" => "Testing from feature test"
            ]
        ]
    ];

    $response = $this->postJson('/api/v1/webhook/whatsapp', $payload);

    $response->assertStatus(200);
    $this->assertDatabaseHas('messages', [
        'to' => '254712345678@c.us',
        'message' => 'Testing from feature test'
    ]);
}

}
