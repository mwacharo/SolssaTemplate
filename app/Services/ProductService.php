<?php

namespace App\Services;

use App\Models\Product;
use App\Models\GoogleSheet;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

class ProductService
{
    /**
     * Get products for synchronization based on various filters
     *
     * @param GoogleSheet $sheet The Google Sheet integration details
     * @param array $filters Optional filters for product selection
     * @return Collection
     */
    public function getProductsForSync(GoogleSheet $sheet, array $filters = []): Collection
    {
        // Start with base query
        $query = Product::query();

        // Filter by vendor if specified in the sheet
        if ($sheet->vendor_id) {
            $query->where('vendor_id', $sheet->vendor_id);
        }

        // Apply additional filters
        if (!empty($filters)) {
            // Filter by product category
            if (isset($filters['category_id'])) {
                $query->where('category_id', $filters['category_id']);
            }

            // Filter by product status
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            // Filter by date range
            if (isset($filters['start_date'])) {
                $query->where('created_at', '>=', $filters['start_date']);
            }

            if (isset($filters['end_date'])) {
                $query->where('created_at', '<=', $filters['end_date']);
            }

            // Limit number of products if specified
            if (isset($filters['limit'])) {
                $query->limit($filters['limit']);
            }
        }

        // If last sync exists, only get products created/updated after last sync
        if ($sheet->last_product_synced) {
            $query->where('updated_at', '>', $sheet->last_product_synced);
        }

        // Order by most recently updated
        $query->orderBy('updated_at', 'desc');

        // Retrieve and log the products
        $products = $query->get();
        
        Log::info('Products retrieved for sync', [
            'total_products' => $products->count(),
            'sheet_id' => $sheet->id
        ]);

        return $products;
    }

    /**
     * Prepare product data for Google Sheets synchronization
     *
     * @param Collection $products Collection of products to prepare
     * @return array Formatted product data for Google Sheets
     */
    public function prepareProductsForSync(Collection $products): array
    {
        return $products->map(function ($product) {
            return [
                $product->id,
                $product->name,
                $product->sku,
                $product->price,
                $product->stock_quantity,
                $product->category ? $product->category->name : '',
                $product->description,
                $product->created_at,
                $product->updated_at
            ];
        })->toArray();
    }

    /**
     * Validate product data before synchronization
     *
     * @param Collection $products Products to validate
     * @return bool
     */
    public function validateProductsForSync(Collection $products): bool
    {
        // Basic validation checks
        return $products->every(function ($product) {
            // Ensure essential fields are not null
            return 
                !empty($product->id) && 
                !empty($product->name) && 
                isset($product->price) && 
                isset($product->stock_quantity);
        });
    }

    /**
     * Update local products after synchronization
     *
     * @param GoogleSheet $sheet The Google Sheet integration details
     * @param Collection $products Products that were synchronized
     */
    public function markProductsSynced(GoogleSheet $sheet, Collection $products)
    {
        // Update last sync timestamp
        $sheet->last_product_synced = now();
        $sheet->save();

        // Optionally, mark products as synced if you have such a mechanism
        $products->each(function ($product) {
            $product->is_synced = true;
            $product->save();
        });

        Log::info('Products marked as synced', [
            'total_products' => $products->count(),
            'sheet_id' => $sheet->id
        ]);
    }

    /**
     * Handle any post-sync operations or logging
     *
     * @param GoogleSheet $sheet The Google Sheet integration details
     * @param Collection $products Products that were synchronized
     * @param bool $syncSuccess Whether the sync was successful
     */
    public function handlePostSyncOperations(GoogleSheet $sheet, Collection $products, bool $syncSuccess)
    {
        if ($syncSuccess) {
            $this->markProductsSynced($sheet, $products);
        } else {
            Log::error('Product sync failed', [
                'sheet_id' => $sheet->id,
                'total_products' => $products->count()
            ]);
        }
    }
}