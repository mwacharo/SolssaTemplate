<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIResponderService
{


    // /**
    //  * Interpret a customer query using OpenAI's API.
    //  *
    //  * @param string $message The customer query to interpret.
    //  * @param array $recentOrders Recent orders associated with the customer.
    //  * @param array $customer Optional customer information (name, id, phone, language, etc.)
    //  * @param array $conversationHistory Optional conversation history for maintaining context.
    //  * @return string|null The interpreted response or null on failure.
    //  */
    // public function interpretCustomerQuery(
    //     string $message,
    //     array $recentOrders = [],
    //     array $customer = [],
    //     array $conversationHistory = []
    // ): ?string {
    //     $context = $this->buildContext($recentOrders, $customer);
    //     $messageWithContext = $context . "\n\n" . $message;

    //     // Build messages array with memory (chat history)
    //     $messages = [
    //         ['role' => 'system', 'content' => $this->getSystemPrompt($customer)],
    //     ];

    //     foreach ($conversationHistory as $entry) {
    //         $messages[] = [
    //             'role' => $entry['role'],
    //             'content' => $entry['content'],
    //         ];
    //     }

    //     // Append current user message
    //     $messages[] = ['role' => 'user', 'content' => $messageWithContext];

    //     try {
    //         $payload = [
    //             'model' => 'gpt-3.5-turbo',
    //             'messages' => $messages,
    //             'temperature' => 0.2,
    //         ];

    //         Log::info('AIResponderService: Requesting OpenAI', [
    //             'recentOrdersCount' => count($recentOrders),
    //             'customerId' => $customer['id'] ?? null,
    //             'messageSnippet' => substr($message, 0, 100),
    //         ]);

    //         $response = Http::withHeaders([
    //             'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
    //             'Content-Type' => 'application/json',
    //         ])->post('https://api.openai.com/v1/chat/completions', $payload);

    //         if ($response->successful()) {
    //             $content = $response->json()['choices'][0]['message']['content'] ?? null;

    //             if ($this->isFallbackResponse($content)) {
    //                 Log::warning('AIResponderService: AI gave weak response, suggesting escalation.');
    //                 return "I'm having trouble processing your request. Let me escalate this to a support agent.";
    //             }

    //             Log::info('AIResponderService: Successful AI response.');
    //             return $content;
    //         }

    //         Log::error('AIResponderService: Failed OpenAI response', ['body' => $response->body()]);
    //     } catch (\Exception $e) {
    //         Log::error('AIResponderService Exception', ['message' => $e->getMessage()]);
    //     }

    //     return null;
    // }

    // private function buildContext(array $orders, array $customer): string
    // {
    //     $lines = [];

    //     if (!empty($customer)) {
    //         $lines[] = "Customer Info:";
    //         $lines[] = "- Name: {$customer['name']}";
    //         $lines[] = "- ID: {$customer['id']}";
    //         if (!empty($customer['language'])) {
    //             $lines[] = "- Preferred Language: {$customer['language']}";
    //         }
    //         $lines[] = "";
    //     }

    //     if (!empty($orders)) {
    //         $lines[] = "Recent Orders:";
    //         foreach ($orders as $order) {
    //             $lines[] = "- Order #{$order['order_no']}: {$order['status']} (Delivery: {$order['delivery_date']})";
    //         }
    //     } else {
    //         $lines[] = "No recent orders found.";
    //     }

    //     return implode("\n", $lines);
    // }

    // private function getSystemPrompt(array $customer): string
    // {
    //     $base = "You are a polite, knowledgeable customer support assistant for a courier company.";
    //     if (!empty($customer['language']) && strtolower($customer['language']) === 'sw') {
    //         return $base . " Respond in Kiswahili if the user types in Kiswahili.";
    //     }
    //     return $base;
    // }

    // private function isFallbackResponse(?string $response): bool
    // {
    //     if (!$response) return true;

    //     $fallbackIndicators = [
    //         'I am not sure',
    //         'please contact support',
    //         'I don’t have enough information',
    //         'unable to assist',
    //     ];

    //     foreach ($fallbackIndicators as $phrase) {
    //         if (stripos($response, $phrase) !== false) {
    //             return true;
    //         }
    //     }

    //     return false;
    // }

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
                    ['role' => 'system', 'content' => 'You are a customer support assistant for a courier company.'],
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
