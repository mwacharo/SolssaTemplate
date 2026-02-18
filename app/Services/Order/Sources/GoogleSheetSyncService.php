<?php


// namespace App\Services;

namespace App\Services\Order\Sources;

use App\Models\Order;

// use App\Services\Order\Sources\GoogleSheetService;
use Illuminate\Support\Facades\Log;

class GoogleSheetSyncService
{
    public function sync($sheetIntegration, $repositoryOwner)
    {
        Log::info("SheetSync: Starting sync", [
            'sheet_id' => $sheetIntegration->id
        ]);

        $vendorId = $sheetIntegration->vendor_id;
        $spreadsheetId = $sheetIntegration->post_spreadsheet_id;
        $sheetName = $sheetIntegration->sheet_name;

        // Fetch orders
        $orders = $repositoryOwner->fetchOrders($vendorId);

        if ($orders->isEmpty()) {
            Log::info("SheetSync: No orders changed");
            return;
        }

        // Fetch sheet map
        $sheetMap = $repositoryOwner->fetchSheetOrders(
            $spreadsheetId,
            $sheetName
        );

        // Detect changes
        $changes = $repositoryOwner->getChangedOrders(
            $orders,
            $sheetMap
        );

        if (empty($changes)) {
            Log::info("SheetSync: No changes to push");
            return;
        }

        // Push update
        $repositoryOwner->batchUpdateSheet(
            $spreadsheetId,
            $sheetName,
            $changes
        );

        Log::info("SheetSync: Sync completed", [
            'updated_rows' => count($changes)
        ]);
    }
}
