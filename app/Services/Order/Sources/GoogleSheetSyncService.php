<?php


// namespace App\Services;

namespace App\Services\Order\Sources;

use App\Models\Order;

// use App\Services\Order\Sources\GoogleSheetService;
use Illuminate\Support\Facades\Log;

class GoogleSheetSyncService
{




    public function sync($sheetIntegration, $repositoryOwner): void
    {
        Log::info("SheetSync: Starting sync", [
            'sheet_id' => $sheetIntegration->id
        ]);

        $vendorId      = $sheetIntegration->vendor_id;
        $spreadsheetId = $sheetIntegration->post_spreadsheet_id;

        // Fetch all sheet names for this spreadsheet
        $sheetNames = $repositoryOwner->fetchSheetNames($spreadsheetId);

        if (empty($sheetNames)) {
            Log::warning("SheetSync: No sheet names found", [
                'spreadsheet_id' => $spreadsheetId
            ]);
            return;
        }

        // Fetch orders once — reused across all sheets
        $orders = $repositoryOwner->fetchOrders($vendorId);

        if ($orders->isEmpty()) {
            Log::info("SheetSync: No orders found for vendor", [
                'vendor_id' => $vendorId
            ]);
            return;
        }

        $totalUpdated = 0;

        foreach ($sheetNames as $sheetName) {
            $updated = $this->syncSheet(
                $repositoryOwner,
                $spreadsheetId,
                $sheetName,
                $orders
            );

            $totalUpdated += $updated;
        }

        Log::info("SheetSync: All sheets synced", [
            'spreadsheet_id' => $spreadsheetId,
            'sheets_count'   => count($sheetNames),
            'total_updated'  => $totalUpdated,
        ]);
    }

    private function syncSheet(
        $repositoryOwner,
        string $spreadsheetId,
        string $sheetName,
        $orders
    ): int {
        Log::info("SheetSync: Processing sheet", [
            'spreadsheet_id' => $spreadsheetId,
            'sheet_name'     => $sheetName,
        ]);

        // Fetch existing sheet data
        $sheetMap = $repositoryOwner->fetchSheetOrders($spreadsheetId, $sheetName);

        // Detect changes
        $changes = $repositoryOwner->getChangedOrders($orders, $sheetMap);

        if (empty($changes)) {
            Log::info("SheetSync: No changes for sheet", [
                'sheet_name' => $sheetName
            ]);
            return 0;
        }

        // Push updates
        $repositoryOwner->batchUpdateSheet($spreadsheetId, $sheetName, $changes);

        Log::info("SheetSync: Sheet updated", [
            'sheet_name'   => $sheetName,
            'updated_rows' => count($changes),
        ]);

        return count($changes);
    }
}
