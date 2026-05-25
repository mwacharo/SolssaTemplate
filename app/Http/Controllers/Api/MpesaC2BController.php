<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

// use App\Models\;
use App\Models\Order;
// use App\Models\Wallet;

use App\Models\OrderPayment;


class MpesaC2BController extends Controller
{
    /**
     * =========================
     * VALIDATION URL
     * =========================
     * Safaricom calls this BEFORE completing payment
     */
    public function validation(Request $request)
    {
        Log::info('C2B Validation Request', $request->all());

        try {
            $data = $request->all();

            $validator = Validator::make($data, [
                'TransAmount'   => 'required|numeric|min:1',
                'MSISDN'        => 'required|string',
                'TransID'       => 'required|string',
                'BillRefNumber' => 'required|string',
            ]);

            if ($validator->fails()) {
                Log::warning('C2B validation failed', $validator->errors()->toArray());

                return response()->json([
                    'ResultCode' => 1,
                    'ResultDesc' => 'Rejected'
                ]);
            }

            /**
             * IMPORTANT: Validate order/account exists
             */
            $order = Order::where('order_no', $data['BillRefNumber'])->first();

            if (!$order) {
                Log::warning('Invalid BillRefNumber', $data);

                return response()->json([
                    'ResultCode' => 1,
                    'ResultDesc' => 'Invalid Account'
                ]);
            }

            return response()->json([
                'ResultCode' => 0,
                'ResultDesc' => 'Accepted'
            ]);
        } catch (\Throwable $e) {
            Log::error('Validation Error', ['error' => $e->getMessage()]);

            return response()->json([
                'ResultCode' => 1,
                'ResultDesc' => 'System Error'
            ]);
        }
    }

    /**
     * =========================
     * CONFIRMATION URL
     * =========================
     * Safaricom calls this AFTER successful payment
     */
    public function confirmation(Request $request)
    {
        Log::info('C2B Confirmation Received', $request->all());

        try {

            $data = $request->all();

            $validator = Validator::make($data, [
                'TransID'       => 'required|string',
                'TransAmount'   => 'required|numeric',
                'MSISDN'        => 'required|string',
                'BillRefNumber' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'ResultCode' => 1,
                    'ResultDesc' => 'Invalid Payload'
                ]);
            }

            $transId = $data['TransID'];
            $amount  = (float) $data['TransAmount'];
            $msisdn  = $data['MSISDN'];
            $billRef = $data['BillRefNumber'];

            /**
             * IDENTITY PROTECTION (VERY IMPORTANT)
             * Prevent duplicate credit
             */
            if (OrderPayment::where('mpesa_receipt', $transId)->exists()) {
                return response()->json([
                    'ResultCode' => 0,
                    'ResultDesc' => 'Already Processed'
                ]);
            }

            DB::transaction(function () use ($data, $transId, $amount, $msisdn, $billRef) {

                /**
                 * Match order (courier use-case)
                 */
                $order = Order::where('order_no', $billRef)->first();

                /**
                 * Save transaction
                 */
                OrderPayment::create([
                    'method' => 'C2B',
                    'mpesa_receipt' => $transId,
                    'msisdn' => $msisdn,
                    'amount' => $amount,
                    'bill_ref_number' => $billRef,
                    'order_id' => $order?->id,
                    'status' => 'success',
                    'raw' => $data,
                ]);

                /**
                 * Update order payment status
                 */
                if ($order) {
                    $order->update([
                        'payment_status' => 'paid',
                        'paid_at' => now(),
                    ]);
                }

                /**
                 * Optional: wallet credit (company float)
                 */
                // $wallet = Wallet::firstOrCreate([
                //     'name' => env('APP_WALLET_NAME', 'Main Wallet')
                // ]);

                // $wallet->credit($amount);
            });

            return response()->json([
                'ResultCode' => 0,
                'ResultDesc' => 'Accepted'
            ]);
        } catch (\Throwable $e) {

            Log::error('C2B Confirmation Error', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'ResultCode' => 1,
                'ResultDesc' => 'System Error'
            ]);
        }
    }
}
