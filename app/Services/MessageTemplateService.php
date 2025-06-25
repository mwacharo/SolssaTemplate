<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MessageTemplateService
{
    public function process(string $template, $order = null, $contact = null): string
    {
        $placeholders = $this->buildPlaceholderData($order, $contact);
        $message = $this->replacePlaceholders($template, $placeholders);

        Log::debug('Processed template message', [
            'template' => $template,
            'placeholders' => $placeholders,
            'result' => $message
        ]);

        return $message;
    }

    private function buildPlaceholderData($order = null, $contact = null): array
    {
        $placeholders = [];

        if ($contact) {
            $placeholders['customer_name'] = $contact->name ?? 'Valued Customer';
            $placeholders['client_name'] = $contact->name ?? 'Valued Customer';
            $placeholders['customer_phone'] = $this->formatPhoneNumber($contact->phone ?? $contact->phone_number ?? '');
        }

        if ($order) {
            $placeholders['order_number'] = $order->order_number ?? $order->id ?? 'N/A';
            $placeholders['order_no'] = $order->order_number ?? $order->id ?? 'N/A';
            $placeholders['product_name'] = $order->product_name ?? $order->product ?? 'Product';
            $placeholders['price'] = $this->formatPrice($order->price ?? $order->amount ?? 0);
            $placeholders['tracking_id'] = $order->tracking_id ?? $order->tracking_number ?? 'Not assigned';
            $placeholders['delivery_date'] = $order->delivery_date ? Carbon::parse($order->delivery_date)->format('M d, Y') : 'TBD';
            $placeholders['status'] = ucfirst($order->status ?? 'processing');
            $placeholders['agent_name'] = $order->agent_name ?? 'Our team';
            $placeholders['zone'] = $order->zone ?? 'Your area';
            
            if ($order->vendor) {
                $placeholders['vendor_name'] = $order->vendor->name ?? 'Vendor';
                $placeholders['vendor_website'] = $order->vendor->website ?? 'Not available';
            }

            if ($order->client) {
                $placeholders['customer_name'] = $order->client->name ?? 'Valued Customer';
                $placeholders['client_name'] = $order->client->name ?? 'Valued Customer';
                $placeholders['customer_phone'] = $this->formatPhoneNumber(
                    $order->client->phone_number ?? $order->client->alt_phone_number ?? ''
                );
            }
        }

        $now = Carbon::now();
        $placeholders['current_date'] = $now->format('M d, Y');
        $placeholders['current_time'] = $now->format('h:i A');
        $placeholders['current_year'] = $now->format('Y');

        return $placeholders;
    }

    private function replacePlaceholders(string $template, array $placeholders): string
    {
        foreach ($placeholders as $key => $value) {
            $patterns = [
                '/\{\{\s*' . preg_quote($key, '/') . '\s*\}\}/',
                '/\{\{' . preg_quote($key, '/') . '\}\}/',
                '/\{' . preg_quote($key, '/') . '\}/',
            ];

            foreach ($patterns as $pattern) {
                $template = preg_replace($pattern, $value, $template);
            }
        }

        // Clean up any remaining placeholders
        $template = preg_replace('/\{\{[^}]*\}\}/', '[data not available]', $template);
        $template = preg_replace('/\{[^}]*\}/', '[data not available]', $template);

        return trim(preg_replace('/\s+/', ' ', $template));
    }

    private function formatPhoneNumber(string $phone): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        return strlen($cleaned) >= 10 ? '+' . $cleaned : ($phone ?: 'Not provided');
    }

    private function formatPrice($price): string
    {
        return '$' . number_format((float)$price, 2);
    }
}
