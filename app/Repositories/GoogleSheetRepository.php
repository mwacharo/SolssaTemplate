<?php

namespace App\Repositories;

use App\Models\GoogleSheet;
use App\Repositories\Interfaces\GoogleSheetRepositoryInterface;
use Carbon\Carbon;

class GoogleSheetRepository implements GoogleSheetRepositoryInterface
{
    /**
     * Find a Google Sheet by ID
     *
     * @param string $id
     * @return GoogleSheet|null
     */
    public function findById($id)
    {
        return GoogleSheet::find($id);
    }

    /**
     * Update the last order sync information
     *
     * @param GoogleSheet $sheet
     * @param string|null $lastOrderNumber
     * @return bool
     */
    public function updateLastOrderSync(GoogleSheet $sheet, $lastOrderNumber = null)
    {
        $sheet->last_order_synced = Carbon::now();
        
        if ($lastOrderNumber) {
            $sheet->last_updated_order_number = $lastOrderNumber;
        }
        
        return $sheet->save();
    }

    /**
     * Update the last product sync information
     *
     * @param GoogleSheet $sheet
     * @return bool
     */
    public function updateLastProductSync(GoogleSheet $sheet)
    {
        $sheet->last_product_synced = Carbon::now();
        return $sheet->save();
    }

    /**
     * Track product sync
     *
     * @param GoogleSheet $sheet
     * @return bool
     */
    public function trackProductSync(GoogleSheet $sheet)
    {
        $sheet->last_product_synced = Carbon::now();
        return $sheet->save();
    }

    public function update(GoogleSheet $sheet)
    {
        return $sheet->save();
    }
}