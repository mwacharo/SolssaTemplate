<?php

namespace App\Jobs;

use App\Repositories\GoogleSheetRepository;
// use App\Services\GoogleSheetSyncService;

    // /home/mwacharo/Desktop/Projects/CustomerSupport/app/Services/Order/Sources/GoogleSheetSyncService.php


use App\Services\Order\Sources\GoogleSheetSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SyncGoogleSheetJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    protected $sheetId;

    public function __construct($sheetId)
    {
        $this->sheetId = $sheetId;
    }

    public function handle(
        GoogleSheetRepository $repository,
        GoogleSheetSyncService $syncService
    ) {

        $sheet = $repository->findById($this->sheetId);

        if (!$sheet) {
            return;
        }

        $syncService->sync($sheet, $repository);
    }
}
