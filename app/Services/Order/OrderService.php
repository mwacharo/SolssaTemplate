<?php
namespace App\Services\Order;

use App\Services\Order\Contracts\OrderImporterInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class OrderService
{
    protected array $importers = [];

    public function __construct()
    {
        // $this->registerImporter('default', new DefaultOrderImporter());
    }

    public function registerImporter(string $key, OrderImporterInterface $importer): void
    {
        $this->importers[$key] = $importer;
    }

    public function import(string $source): Collection
    {
        if (!isset($this->importers[$source])) {
            throw new \InvalidArgumentException("No importer registered for source: $source");
        }

        return $this->importers[$source]->fetchOrders();
    }



    public function getOrdersForSync($sheet, array $filters = []): Collection
    {
        // Log the start of the sync process
        Log::debug('Starting getOrdersForSync', [
            'sheet_id' => $sheet->id ?? null,
            'filters' => $filters,
        ]);

        // Determine which source to use based on the sheet configuration
        $source = $sheet->source ?? 'default';
        Log::debug('Using source for import', ['source' => $source]);

        // Get all orders from the specified source
        $orders = $this->import($source);
        Log::debug('Fetched orders from source', ['count' => $orders->count()]);

        // Apply filters if specified
        if (!empty($filters)) {
            $orders = $this->applyFilters($orders, $filters);
            Log::debug('Applied filters to orders', ['filtered_count' => $orders->count()]);
        }

        // If the sheet has a last synced order, only return orders after that
        if ($sheet->last_order_synced) {
            $orders = $orders->filter(function ($order) use ($sheet) {
                return $order->order_number > $sheet->last_order_synced;
            });
            Log::debug('Filtered orders after last synced order', [
                'last_order_synced' => $sheet->last_order_synced,
                'final_count' => $orders->count(),
            ]);
        }

        Log::debug('Returning orders for sync', ['total' => $orders->count()]);

        return $orders;
    }

    /**
     * Apply filters to the collection of orders
     *
     * @param Collection $orders Collection of orders
     * @param array $filters Filters to apply
     * @return Collection Filtered collection of orders
     */
    protected function applyFilters(Collection $orders, array $filters): Collection
    {
        // Example filter implementation
        // This is a simplified version - expand as needed for your specific requirements

        if (isset($filters['date_from'])) {
            $orders = $orders->filter(function ($order) use ($filters) {
                return $order->created_at >= $filters['date_from'];
            });
        }

        if (isset($filters['date_to'])) {
            $orders = $orders->filter(function ($order) use ($filters) {
                return $order->created_at <= $filters['date_to'];
            });
        }

        if (isset($filters['status'])) {
            $orders = $orders->filter(function ($order) use ($filters) {
                return $order->status === $filters['status'];
            });
        }

        // Add more filters as needed

        return $orders;
    }
}
