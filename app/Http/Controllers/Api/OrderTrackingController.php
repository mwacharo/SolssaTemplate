<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Order;




class OrderTrackingController extends Controller
{
    public function track($tracking)
    {
        $order = Order::with([
            'latestStatus.status',
            'statusTimestamps.status',
        ])
            ->where('order_no', $tracking)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Tracking number not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformOrder($order)
        ]);
    }

    private function transformOrder($order)
    {
        return [
            'tracking_number' => $order->order_no,
            'status' => data_get($order, 'latestStatus.status.name', 'Unknown'),
            'status_date' => optional($order->latestStatus)->created_at->format('Y-m-d H:i'),
            'estimated_delivery' => optional($order->delivery_date)->format('Y-m-d H:i'),
            'created_at' => $order->created_at->format('Y-m-d H:i'),

            'journey' => $order->statusTimestamps
                ->sortBy('created_at')
                ->map(function ($item) {
                    return [
                        'status' => optional($item->status)->name,
                        'notes' => $item->status_notes,
                        'timestamp' => $item->created_at->format('Y-m-d H:i'),
                    ];
                })
                ->values(),

        ];
    }

    private function maskPhone($phone)
    {
        if (!$phone) return null;

        return substr($phone, 0, 4) . '****' . substr($phone, -2);
    }
}
