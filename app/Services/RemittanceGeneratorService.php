<?php

namespace App\Services\Remittance;

use App\Models\Order;
use App\Models\Remittance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RemittanceGeneratorService
{
    /**
     * Generate a new remittance for a seller.
     *
     * @param int $sellerId
     * @param string|null $periodStart
     * @param string|null $periodEnd
     * @return Remittance
     */
    public function generate(int $sellerId, ?string $periodStart = null, ?string $periodEnd = null): Remittance
    {
        $periodStart = $periodStart ? Carbon::parse($periodStart) : Carbon::now()->startOfDay();
        $periodEnd   = $periodEnd ? Carbon::parse($periodEnd) : Carbon::now()->endOfDay();

        return DB::transaction(function () use ($sellerId, $periodStart, $periodEnd) {

            // 1. Fetch seller orders that should be included
            $orders = Order::where('vendor_id', $sellerId)
                ->whereNull('invoice_id')
                ->where('shipping_status_id', 1272) // delivered
                ->whereBetween('delivered_at', [$periodStart, $periodEnd])
                ->get();

            if ($orders->isEmpty()) {
                throw new \Exception("No orders available for remittance.");
            }

            // 2. Calculate totals
            $totalCollected = $orders->sum('total_price');

            // Example: calculate fees
            $confirmationFee      = $this->calcConfirmationFee($orders);
            $shippingFee          = $this->calcShippingFee($orders);
            $returnFee            = $this->calcReturnFee($orders);
            $percentageFee        = $totalCollected * 0.029; // 2.9%
            $additionalFees       = 0; // Set rules if needed

            $totalFees =
                $confirmationFee +
                $shippingFee +
                $returnFee +
                $percentageFee +
                $additionalFees;

            $amountToPaySeller = $totalCollected - $totalFees;

            // 3. Create the remittance
            $remittance = Remittance::create([
                'invoice_number'        => $this->generateInvoiceNumber(),
                'invoice_date'          => Carbon::now(),
                'payment_period_start'  => $periodStart,
                'payment_period_end'    => $periodEnd,
                'approval_status'       => 'draft',

                'vendor_id'             => $sellerId,

                'total_amount'          => $amountToPaySeller,
                'total_amount_mad'      => $amountToPaySeller / 140, // example conversion rate

                'confirmation_fee'      => $confirmationFee,
                'shipping_fee'          => $shippingFee,
                'return_fee'            => $returnFee,
                'percentage_fee'        => $percentageFee,
                'additional_fees'       => $additionalFees,

                'payment_status'        => 'unpaid',
                'payment_method'        => 'cash',
                'country_id'            => 15,
            ]);

            // 4. Attach orders to this remittance
            Order::whereIn('id', $orders->pluck('id'))->update([
                'invoice_id' => $remittance->id,
                'seller_payment_status' => 'paid',
                'payed_at' => Carbon::now(),
            ]);

            return $remittance;
        });
    }


    /**
     * Generate invoice number format: KE-xxxxx
     */
    private function generateInvoiceNumber(): string
    {
        return 'KE-' . strtoupper(str()->random(10));
    }


    /**
     * Fee calculations (customize rules as needed)
     */
    private function calcConfirmationFee($orders): float
    {
        return $orders->count() * 0; // example: free confirmation
    }

    private function calcShippingFee($orders): float
    {
        return $orders->count() * 0; // example: included in total
    }

    private function calcReturnFee($orders): float
    {
        return 0; // no returns counted here
    }
}
