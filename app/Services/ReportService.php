<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Vendor;
use App\Models\Product;
use App\Models\Category;
use App\Models\Zone;
use App\Models\City;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportExport;

class ReportService
{
    /**
     * Get filter options for dropdowns
     */
    public function getFilterOptions(): array
    {
        return [
            'merchants' => Vendor::select('id', 'name')
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(),

            'products' => Product::select('id', 'product_name', 'sku')
                ->whereNull('deleted_at')
                ->orderBy('product_name')
                ->get(),

            'categories' => Category::select('id', 'name')
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(),

            'zones' => Zone::select('id', 'name')
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(),

            'cities' => City::select('id', 'name')
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(),

            'riders' => User::select('id', 'name')
                ->whereHas('roles', function ($q) {
                    $q->where('name', 'Delivery Agent');
                })
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(),

            // add callagents

            'callAgents' => User::select('id', 'name')
                ->whereHas('roles', function ($q) {
                    $q->where('name', 'CallAgent');
                })
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(),
        ];
    }

    /**
     * Generate report based on type and filters
     */
    public function generateReport(string $reportType, array $filters, int $page = 1, int $perPage = 25): array
    {
        $query = $this->buildBaseQuery($reportType);
        $query = $this->applyFilters($query, $filters, $reportType);

        // Get total count before pagination
        $total = $query->count();

        // Apply pagination
        $data = $query
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        // Format data based on report type
        $formattedData = $this->formatReportData($data, $reportType);

        return [
            'data' => $formattedData,
            'total' => $total
        ];
    }

    /**
     * Build base query for different report types
     */
    protected function buildBaseQuery(string $reportType)
    {
        $query = Order::with([
            'customer',
            'vendor',
            'warehouse',
            // 'zone',
            // 'city',
            'country',
            'orderItems.product',
            'assignments.user',
            'latest_status.status'
        ]);

        // Apply specific conditions based on report type
        switch ($reportType) {
            case 'delivery':
                $query->whereHas('latest_status', function ($q) {
                    $q->whereHas('status', function ($sq) {
                        $sq->where('name', 'Delivered');
                    });
                });
                break;

            case 'returns':
                $query->whereHas('latest_status', function ($q) {
                    $q->whereHas('status', function ($sq) {
                        $sq->where('name', 'Returned');
                    });
                });
                break;

            case 'dispatch':
                $query->whereHas('latest_status', function ($q) {
                    $q->whereHas('status', function ($sq) {
                        $sq->where('name', 'Dispatched');
                    });
                });
                break;

            case 'out_scan':
                $query->whereHas('latest_status', function ($q) {
                    $q->whereHas('status', function ($sq) {
                        $sq->where('name', 'Out for Delivery');
                    });
                });
                break;

            case 'undispatched':
                $query->whereHas('latest_status', function ($q) {
                    $q->whereHas('status', function ($sq) {
                        $sq->whereNotIn('name', ['Dispatched', 'Delivered', 'Returned']);
                    });
                });
                break;
        }

        return $query;
    }

    /**
     * Apply filters to query
     */
    protected function applyFilters($query, array $filters, string $reportType)
    {
        // Merchant filter
        if (!empty($filters['merchant'])) {
            $query->where('vendor_id', $filters['merchant']);
        }

        // Product filter
        if (!empty($filters['product'])) {
            $query->whereHas('orderItems', function ($q) use ($filters) {
                $q->where('product_id', $filters['product']);
            });
        }

        // Category filter
        if (!empty($filters['category'])) {
            $query->whereHas('orderItems.product', function ($q) use ($filters) {
                $q->where('category_id', $filters['category']);
            });
        }

        // Zone filter
        // Zone filter (customer's zone)
        if (!empty($filters['zone'])) {
            $query->whereHas('customer', function ($q) use ($filters) {
                $q->where('zone_id', $filters['zone']);
            });
        }

        // City filter (customer's city)
        if (!empty($filters['city'])) {
            $query->whereHas('customer', function ($q) use ($filters) {
                $q->where('city_id', $filters['city']);
            });
        }

        // Rider filter
        if (!empty($filters['rider'])) {
            $query->whereHas('assignments', function ($q) use ($filters) {
                $q->where('user_id', $filters['rider'])
                    ->where('role', 'Delivery Agent');
            });
        }

        // Confirmation Status filter
        if (!empty($filters['confirmationStatus'])) {
            $query->whereHas('latestStatus.status', function ($q) use ($filters) {
                $q->where('id', $filters['confirmationStatus']);
            });
        }

        // Shipping Status filter
        if (!empty($filters['shippingStatus'])) {
            $query->whereHas('latest_status.status', function ($q) use ($filters) {
                $q->where('name', $filters['shippingStatus']);
            });
        }

        // Order Date range
        if (!empty($filters['orderDate'])) {
            if (!empty($filters['orderDate']['start'])) {
                $query->whereDate('created_at', '>=', $filters['orderDate']['start']);
            }
            if (!empty($filters['orderDate']['end'])) {
                $query->whereDate('created_at', '<=', $filters['orderDate']['end']);
            }
        }

        // Delivery Date range
        if (!empty($filters['deliveryDate'])) {
            if (!empty($filters['deliveryDate']['start'])) {
                $query->whereDate('delivery_date', '>=', $filters['deliveryDate']['start']);
            }
            if (!empty($filters['deliveryDate']['end'])) {
                $query->whereDate('delivery_date', '<=', $filters['deliveryDate']['end']);
            }
        }


        // status date range filter
        if (!empty($filters['statusDate'])) {
            if (!empty($filters['statusDate']['start'])) {
                $query->whereHas('latest_status', function ($q) use ($filters) {
                    $q->whereDate('created_at', '>=', $filters['statusDate']['start']);
                });
            }
            if (!empty($filters['statusDate']['end'])) {
                $query->whereHas('latest_status', function ($q) use ($filters) {
                    $q->whereDate('created_at', '<=', $filters['statusDate']['end']);
                });
            }
        }

        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Format report data based on type
     */
    protected function formatReportData($orders, string $reportType): array
    {
        return $orders->map(function ($order) use ($reportType) {
            // Get product items as string
            $productItems = $order->orderItems->map(function ($item) {
                return sprintf(
                    '%s × %s (%s)',
                    $item->quantity,
                    $item->product->product_name ?? $item->name ?? 'Unknown',
                    $item->sku ?? 'N/A'
                );
            })->join(', ');

            // Get rider from assignments
            $rider = $order->assignments->firstWhere('role', 'Delivery Agent');
            $agent = $order->assignments->firstWhere('role', 'CallAgent');

            // Base data (common for all reports)
            $data = [
                'order_no' => $order->order_no,
                'product_items' => $productItems,
                'receiver_name' => $order->customer->full_name ?? 'N/A',
                'receiver_phone' => $order->customer->phone ?? 'N/A',
                'receiver_address' => $order->customer->address ?? 'N/A',
                'receiver_town' => $order->customer->city->name ?? 'N/A',
                'receiver_city' => $order->customer->city->name ?? 'N/A',
                'cash_on_delivery' => number_format($order->total_price, 2),
                'total_qty' => $order->orderItems->sum('quantity'),
                'order_status' => $order->latest_status->status->name ?? 'N/A',
                'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                'scheduled_date' => $order->delivery_date ? \Carbon\Carbon::parse($order->delivery_date)->format('Y-m-d') : 'N/A',
                'delivery_date' => $order->delivery_date ? \Carbon\Carbon::parse($order->delivery_date)->format('Y-m-d') : 'N/A',
                'zone' => $order->zone->name ?? 'N/A',
            ];

            // Add rider info if applicable
            if (in_array($reportType, ['delivery', 'returns', 'out_scan', 'merchant'])) {
                $data['rider'] = $rider ? $rider->user->name : 'N/A';
            }

            // Add merchant-specific fields
            if ($reportType === 'merchant') {
                $data['special_instructions'] = $order->customer_notes ?? 'N/A';
                $data['agent'] = $agent ? $agent->user->name : 'N/A';
            }

            return $data;
        })->toArray();
    }

    /**
     * Download report as Excel
     */
    public function downloadReport(string $reportType, array $filters, string $format = 'xlsx')
    {
        // Get all data without pagination
        $query = $this->buildBaseQuery($reportType);
        $query = $this->applyFilters($query, $filters, $reportType);

        $orders = $query->get();
        $data = $this->formatReportData($orders, $reportType);

        // Create export
        $export = new ReportExport($data, $reportType);

        $fileName = sprintf(
            '%s_report_%s.%s',
            $reportType,
            now()->format('Y-m-d_His'),
            $format
        );

        return Excel::download($export, $fileName);
    }

    /**
     * Get report summary/statistics
     */
    public function getReportSummary(string $reportType, array $filters): array
    {
        $query = $this->buildBaseQuery($reportType);
        $query = $this->applyFilters($query, $filters, $reportType);

        return [
            'total_orders' => $query->count(),
            'total_amount' => $query->sum('total_price'),
            'total_items' => DB::table('orderItems')
                ->whereIn('order_id', $query->pluck('id'))
                ->sum('quantity'),
            'avg_order_value' => $query->avg('total_price'),
        ];
    }
}
