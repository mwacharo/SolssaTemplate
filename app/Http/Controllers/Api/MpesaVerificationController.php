<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\Models\Order;
use App\Models\OrderPayment;

class MpesaVerificationController extends Controller
{
    /**
     * Verify a specific M-Pesa receipt code for an order
     */
    public function verify($orderId, Request $request)

    // public function verify(Request $request)

    {
        try {

            $request->validate([
                'mpesa_code' => 'required|string'
            ]);

            $mpesaCode = $request->input('mpesa_code');

            /**
             * 1. Find order
             */
            $order = Order::find($orderId);

            if (!$order) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Order not found'
                ], 404);
            }

            /**
             * 2. Find transaction by receipt code + order
             */
            $transaction = OrderPayment::where('mpesa_receipt', $mpesaCode)
                ->where('method', 'C2B')
                ->first();

            /**
             * 3. If not found at all
             */
            if (!$transaction) {
                return response()->json([
                    'status' => 'invalid',
                    'message' => 'M-Pesa code not found'
                ]);
            }

            /**
             * 4. Check if already used for another order
             */
            if ($transaction->order_id && $transaction->order_id != $order->id) {
                return response()->json([
                    'status' => 'fraud_warning',
                    'message' => 'This code is already used for another order'
                ]);
            }

            /**
             * 5. Attach transaction to order if not yet linked
             */
            if (!$transaction->order_id) {
                $transaction->order_id = $order->id;
                $transaction->save();
            }

            /**
             * 6. Validate amount
             */
            $validAmount = $transaction->amount >= $order->total_amount;

            return response()->json([
                'status' => $validAmount ? 'valid_payment' : 'partial_payment',
                'order_number' => $order->order_number,
                'mpesa_code' => $transaction->mpesa_receipt_number,
                'amount_paid' => $transaction->amount,
                'expected_amount' => $order->total_amount,
                'phone' => $transaction->msisdn,
                'valid_amount' => $validAmount,
                'paid_at' => $transaction->created_at
            ]);
        } catch (\Throwable $e) {

            Log::error('MPesa verification error', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Server error'
            ], 500);
        }
    }
}
