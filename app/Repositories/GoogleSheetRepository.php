<?php

namespace App\Repositories;

use App\Models\GoogleSheet;
use App\Repositories\Interfaces\GoogleSheetRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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


    // add create method
    /**
     * Create a new Google Sheet record
     *
     * @param array $data
     * @return GoogleSheet
     */
    public function create(array $data)
    {
        // return GoogleSheet::create($data);

           try {
            Log::info('Creating Google Sheet with data:', $data);
            
            $sheet = GoogleSheet::create($data);
            
            Log::info('Google Sheet created successfully:', ['id' => $sheet->id]);
            
            return $sheet;
        } catch (\Exception $e) {
            Log::error('Failed to create Google Sheet in repository: ' . $e->getMessage());
            Log::error('Data that failed:', $data);
            throw $e;
        }
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


    /**
     * Delete a Google Sheet record
     *
     * @param GoogleSheet $sheet
     * @return bool|null
     * @throws \Exception
     */
    public function destroy(GoogleSheet $sheet)
    {
        return $sheet->delete();
    }

}