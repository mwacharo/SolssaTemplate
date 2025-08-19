<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIResponderService
{

    /**
     * Interpret a customer query using OpenAI's API.
     *
     * @param string $message The customer query to interpret.
     * @return string|null The interpreted response or null on failure.
     */

    public function interpretCustomerQuery(string $message, array $recentOrders = []): ?string
    {
        $orderDetails = '';
        if (!empty($recentOrders)) {
            $orderDetails .= "Here are the customer's recent orders:\n";
            foreach ($recentOrders as $order) {
            $orderDetails .= "- Order #{$order['order_no']}: {$order['status']} (Delivery: {$order['delivery_date']})\n";
            // Vendor
            if (!empty($order['vendor']['name'])) {
                $orderDetails .= "  Vendor: {$order['vendor']['name']}\n";
            } else {
                $orderDetails .= "  Vendor: N/A\n";
            }
            // Rider
            if (!empty($order['rider']['name'])) {
                $orderDetails .= "  Rider: {$order['rider']['name']}\n";
            } else {
                $orderDetails .= "  Rider: N/A\n";
            }
            // Agent
            if (!empty($order['agent']['name'])) {
                $orderDetails .= "  Agent: {$order['agent']['name']}\n";
            } else {
                $orderDetails .= "  Agent: N/A\n";
            }
            // Client
            if (!empty($order['client']['name'])) {
                $orderDetails .= "  Client: {$order['client']['name']}";
                if (!empty($order['client']['phone_number'])) {
                $orderDetails .= " (Phone: {$order['client']['phone_number']})";
                }
                $orderDetails .= "\n";
            } else {
                $orderDetails .= "  Client: N/A\n";
            }
            // Items
            $orderDetails .= "  Items:\n";
            if (!empty($order['order_items'])) {
                foreach ($order['order_items'] as $item) {
                $orderDetails .= "    - " . (isset($item['name']) ? $item['name'] : 'Item') . " x{$item['quantity']}\n";
                }
            } else {
                $orderDetails .= "    - No items found\n";
            }
            }
            $orderDetails .= "\n";
        }

        $messageWithContext = $orderDetails . $message;

        Log::info('AIResponderService: interpretCustomerQuery called', [
            'message' => $message,
            'recentOrders' => $recentOrders,
            'messageWithContext' => $messageWithContext
        ]);

        try {
            $payload = [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a polite, knowledgeable customer support assistant for a courier company'],
                    ['role' => 'user', 'content' => $messageWithContext],
                ],
                'temperature' => 0.2,
            ];

            Log::info('AIResponderService: OpenAI payload', $payload);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', $payload);

            Log::info('AIResponderService: OpenAI response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->successful()) {
                $content = $response->json()['choices'][0]['message']['content'];
                Log::info('AIResponderService: OpenAI interpreted content', ['content' => $content]);
                return $content;
            }

            Log::error('OpenAI response failed', ['response' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('AIResponderService exception', ['message' => $e->getMessage()]);
            return null;
        }
    }




}
