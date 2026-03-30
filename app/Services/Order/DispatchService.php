<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\OrderStatusHistory;
use App\Models\OrderStatusTimestamp;
use App\Models\Status;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;


use App\Services\AdvantaSmsService;
use App\Jobs\AdvantaSmsJob;
use App\Services\TemplateService;
use Illuminate\Support\Facades\Log;



class DispatchService
{


    // public function __construct(
    //     protected AdvantaSmsService $smsService,

    //     protected TemplateService $templateService // ✅ add this


    // )
    //  {}
    /**
     * Dispatch a batch of orders.
     *
     * - Sets city_from, zone_from, city_to, zone_to, rider_id, courrier_id
     * - Stamps dispatched_at
     * - Appends a "Dispatched" status history entry for each order
     *
     * @param  array $orderIds
     * @param  array $payload  { city_from, zone_from, city_to, zone_to, rider_id, courrier_id }
     * @return int  Number of orders dispatched
     */
    public function dispatch(array $orderIds, array $payload): int
    {
        $dispatchedStatus = OrderStatus::findBySlug(OrderStatus::DISPATCHED);

        if (! $dispatchedStatus) {
            throw new \RuntimeException('Order status "Dispatched" not found. Please seed the order_statuses table.');
        }

        $count = 0;

        DB::transaction(function () use ($orderIds, $payload, $dispatchedStatus, &$count) {
            // Fetch only orders that are currently "Awaiting Dispatch"
            $orders = Order::whereIn('id', $orderIds)
                ->awaitingDispatch()
                ->lockForUpdate()
                ->get();

            foreach ($orders as $order) {
                // Update routing & assignment
                $order->update([
                    'city_from_id'  => $payload['city_from'],
                    'zone_from_id'  => $payload['zone_from'] ?? null,
                    'city_to_id'    => $payload['city_to'],
                    'zone_to_id'    => $payload['zone_to'] ?? null,
                    'rider_id'      => $payload['rider_id'],
                    'courrier_id'   => $payload['courrier_id'] ?? null,
                    'dispatched_at' => now(),
                ]);

                // Append status history entry
                // OrderStatusHistory::create([
                //     'order_id'   => $order->id,
                //     'status_id'  => $dispatchedStatus->id,
                //     'changed_by' => Auth::id(),
                //     'note'       => 'Dispatched via Dispatch List',
                // ]);



                // 3. Resolve customer phone
                $phone = $order->customer?->phone
                    ?? $order->customer?->alt_phone
                    ?? null;

                if (! $phone) {
                    Log::warning("No phone found for order #{$order->id}, skipping SMS.");
                    $dispatched[] = $order->id;
                    continue;
                }

                // 4. Generate message using template + order_id
                // $result = $templateService->generateMessage(
                //     phone: $phone,
                //     templateSlug: $templateSlug,
                //     additionalData: [
                //         'order_id' => $order->id,
                //     ]
                // );

                // // 5. Dispatch SMS job
                // AdvantaSmsJob::dispatch(
                //     $phone,
                //     $result['message'],
                //     // $validated['rider_id']   // userId — the acting rider/user

                //     // pass user_id 1
                //     1,
                // );

                $dispatched[] = $order->id;

                $count++;
            }
        });

        return $count;
    }

    /**
     * Move selected dispatched orders to "In Transit".
     *
     * @param  array $orderIds
     * @return int
     */
    public function sendToInTransit(array $orderIds): int
    {

        $inTransitStatus = Status::where('name', 'In Transit')->first();

        $count = 0;

        if (! $inTransitStatus) {
            throw new \RuntimeException('status "In Transit" not found.');
        }


        DB::transaction(function () use ($orderIds, $inTransitStatus, &$count) {
            $orders = Order::whereIn('id', $orderIds)
                ->whereHas(
                    'latestStatus',
                    fn($q) =>
                    $q->whereHas(
                        'status',
                        fn($q2) =>
                        $q2->where('name', 'Dispatched')
                    )
                )
                ->lockForUpdate()
                ->get();

            foreach ($orders as $order) {
                // $order->update(['shipped_at' => now()]);


                OrderStatusTimestamp::create([
                    'order_id'  => $order->id,
                    'status_id' => $inTransitStatus->id,
                    'status_notes' => 'Sent to In Transit from Dispatch List',
                ]);




                // OrderStatusHistory::create([
                //     'order_id'   => $order->id,
                //     'status_id'  => $inTransitStatus->id,
                //     'changed_by' => Auth::id(),
                //     'note'       => 'Sent to In Transit from Dispatch List',
                // ]);

                $count++;
            }
        });

        return $count;
    }


    public function generateDispatchPDF(array $orderIds)
    {
        $orders = Order::whereIn('id', $orderIds)->get();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('dispatch.pdf', ['orders' => $orders]);
        return $pdf->download('dispatch.pdf');
    }

    public function generateDispatchExcel(array $orderIds)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\DispatchExport($orderIds),
            'dispatch.xlsx'
        );
    }
}
