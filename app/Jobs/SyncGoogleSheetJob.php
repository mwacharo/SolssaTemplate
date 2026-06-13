<?php

namespace App\Jobs;

use App\Repositories\GoogleSheetRepository;
use App\Services\Order\Sources\GoogleSheetSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class SyncGoogleSheetJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, Queueable;

    protected int $sheetId;

    /**
     * Prevent duplicate jobs for same sheet.
     */
    public $uniqueFor = 1800; // 30 minutes

    public function __construct(int $sheetId)
    {
        $this->sheetId = $sheetId;
    }

    public function uniqueId(): string
    {
        return (string) $this->sheetId;
    }

    public function handle(
        GoogleSheetRepository $repository,
        GoogleSheetSyncService $syncService
    ): void {
        $sheet = $repository->findById($this->sheetId);

        if (!$sheet) {
            Log::warning('Sheet not found', [
                'sheet_id' => $this->sheetId,
            ]);

            return;
        }

        $syncService->sync($sheet, $repository);
    }
}
