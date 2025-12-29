<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\OrderStatusTimestamp;
use App\Models\Status;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class MpesaStkPushController extends Controller
{


    /**
     * Initiate STK Push
     */
    public function initiate(Request $request)
    {
        $request->validate([
            // 'order_id' => 'required|exists:orders,id',
            'order_no' => 'required|exists:orders,order_no',
            'phone'    => 'required|string',
            'amount'   => 'required|numeric|min:1',
            'description' => 'nullable|string'
        ]);

        Log::info('STK Push Initiation Request', $request->all());

        // $order = Order::findOrFail($request->order_id);

        $order = Order::where('order_no', $request->order_no)->firstOrFail();



        // 🔐 Prevent double payment
        if ($order->payment_status === 'paid') {
            return response()->json([
                'message' => 'Order already paid'
            ], 409);
        }

        $phone  = $this->formatPhoneNumber($request->phone);
        $amount = $order->total_price; // 🔒 lock amount from order


        $amount = (int) $order->total_price;


        // 1️⃣ Create pending transaction FIRST
        // $transaction = Transaction::create([
        $transaction = OrderPayment::create([

            'type'      => 'order_payment',
            'order_id'  => $order->id,
            'amount'    => $amount,
            'phone'     => $phone,
            'status'    => 0, // pending
            'purpose'   => 'Order Payment',
            'method ' => 'mpesa_stk_push',
        ]);

        // 2️⃣ Get access token
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return response()->json(['message' => 'Failed to get access token'], 500);
        }

        // 3️⃣ Build STK payload
        $shortcode = env('MPESA_SHORTCODE');
        $passkey   = env('MPESA_PASSKEY');
        $timestamp = now()->format('YmdHis');
        $password  = base64_encode($shortcode . $passkey . $timestamp);

        $payload = [
            'BusinessShortCode' => $shortcode,
            'Password'          => $password,
            'Timestamp'         => $timestamp,
            'TransactionType'   => 'CustomerPayBillOnline',
            'Amount'            => $amount,
            'PartyA'            => $phone,
            'PartyB'            => $shortcode,
            'PhoneNumber'       => $phone,
            // 'CallBackURL'       => url('api/mpesa/stk/callback'),
            'CallBackURL'       => env('MPESA_STK_CALLBACK_URL', url('api/mpesa/stk/callback')),


            'AccountReference'  => $order->order_no,
            'TransactionDesc'   => $request->description ?? 'Courier Order Payment'
        ];

        Log::info('STK Push Payload', $payload);

        // 4️⃣ Send STK Push
        $url = env('MPESA_ENV') === 'production'
            ? 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest'
            : 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

        $response = Http::withToken($accessToken)
            ->timeout(30)
            ->post($url, $payload);

        $responseData = $response->json();
        $transaction->update([
            // 'status' => 'completed',
            'mpesa_receipt' => $metadata['MpesaReceiptNumber'] ?? null,
            // 'transaction_date' => $metadata['TransactionDate'] ?? null,
            'result_code' => 0,
            'result_desc' => 'Success',
            // 'result_data' => json_encode($stkCallback),
        ]);

        Log::info('STK Push Response', $responseData);

        // 5️⃣ Store identifiers for callback reconciliation
        if (isset($responseData['CheckoutRequestID'])) {
            $transaction->update([
                'checkout_request_id' => $responseData['CheckoutRequestID'],
                'merchant_request_id'  => $responseData['MerchantRequestID'] ?? null,
                'raw_response'    => json_encode($responseData),
            ]);
        } else {
            $transaction->update([
                'status' => 'failed',
                'raw_response' => json_encode($responseData),
            ]);
        }

        return response()->json($responseData);
    }





    /**
     * STK Callback (Safaricom)
     */
    public function callback(Request $request)
    {
        Log::info('STK Callback Received', $request->all());

        $stkCallback = $request->input('Body.stkCallback');

        // Early exit if no callback
        if (!$stkCallback) {
            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        $checkoutId = $stkCallback['CheckoutRequestID'] ?? null;
        $resultCode = $stkCallback['ResultCode'] ?? null;
        $resultDesc = $stkCallback['ResultDesc'] ?? null;

        // Find transaction by CheckoutRequestID
        $transaction = OrderPayment::where('checkout_request_id', $checkoutId)->first();

        if (!$transaction) {
            Log::warning('Transaction not found for CheckoutRequestID', [$checkoutId]);
            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        // Handle failed or cancelled payments
        if ($resultCode != 0) {
            $transaction->update([
                'status' => OrderPayment::STATUS_FAILED,
                'result_code' => $resultCode,
                'result_desc' => $resultDesc,
                'meta' => json_encode($stkCallback),
                'raw_callback' => json_encode($stkCallback),
            ]);

            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        // Extract metadata safely
        $metadata = collect($stkCallback['CallbackMetadata']['Item'] ?? [])
            ->pluck('Value', 'Name');

        // Convert TransactionDate to proper timestamp if exists
        $transactionDate = null;
        if (isset($metadata['TransactionDate'])) {
            $transactionDate = \Carbon\Carbon::createFromFormat('YmdHis', $metadata['TransactionDate'])->toDateTimeString();
        }

        // Amount verification
        if (isset($metadata['Amount']) && (float) $metadata['Amount'] !== (float) $transaction->amount) {
            Log::critical('Amount mismatch detected', [
                'expected' => $transaction->amount,
                'paid' => $metadata['Amount']
            ]);

            $transaction->update([
                'status' => OrderPayment::STATUS_FLAGGED,
                'meta' => json_encode($stkCallback),
                'raw_callback' => json_encode($stkCallback),
            ]);

            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        // Update transaction as completed
        $transaction->update([
            'status' => OrderPayment::STATUS_COMPLETED,
            'mpesa_receipt' => $metadata['MpesaReceiptNumber'] ?? null,
            'transaction_date' => $transactionDate,
            'result_code' => 0,
            'result_desc' => 'Success',
            'meta' => json_encode($stkCallback),
            'raw_callback' => json_encode($stkCallback),
            'balance' => $metadata['Balance'] ?? null,
            'phone' => $metadata['PhoneNumber'] ?? $transaction->phone,
            'paid_at' => now(),
        ]);

        // Mark order as paid
        $order = Order::find($transaction->order_id);
        if ($order) {



            $deliveredStatus = Status::where('name', 'Delivered')->first();

            if ($deliveredStatus && $order) {
                OrderStatusTimestamp::create([
                    'order_id' => $order->id,
                    'status_id' => $deliveredStatus->id,
                    // 'status_category_id' => $deliveredStatus->status_category_id ?? null,
                    // 'status_notes' => 'Order delivered after successful payment',
                ]);
            }
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }


    /**
     * Get Mpesa Access Token
     */
    private function getAccessToken()
    {
        $url = env('MPESA_ENV') === 'production'
            ? 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
            : 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

        try {
            $response = Http::withBasicAuth(
                env('MPESA_CONSUMER_KEY'),
                env('MPESA_CONSUMER_SECRET')
            )->timeout(15)->get($url);

            return $response->successful()
                ? $response->json()['access_token']
                : null;
        } catch (\Exception $e) {
            Log::error('Access Token Error', [$e->getMessage()]);
            return null;
        }
    }

    /**
     * Normalize phone number
     */
    private function formatPhoneNumber($phone)
    {
        $phone = preg_replace('/\D/', '', $phone);

        if (strlen($phone) === 10 && str_starts_with($phone, '07')) {
            return '254' . substr($phone, 1);
        }

        if (strlen($phone) === 9) {
            return '254' . $phone;
        }

        return $phone;
    }
}
