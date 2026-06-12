<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\GoogleSheet;


class FlushGoogleSheetBufferJob implements ShouldQueue
{
    use Queueable;

    // /**
    //  * Create a new job instance.
    //  */
    // public function __construct()
    // {
    //     //
    // }

    // /**
    //  * Execute the job.
    //  */
    // public function handle(): void
    // {
    //     //
    // }



    public function __construct(
        public int $vendorId
    ) {}

    public function handle($repo): void
    {
        $keys = cache()->getRedis()->keys(
            "google_sync_buffer:{$this->vendorId}:*"
        );

        if (empty($keys)) return;

        $changes = [];

        foreach ($keys as $key) {
            $changes[] = cache()->get($key);
            cache()->forget($key);
        }

        if (empty($changes)) return;

        $sheet = GoogleSheet::where('vendor_id', $this->vendorId)->first();

        if (! $sheet) return;

        $repo->batchUpdateSheet(
            $sheet->post_spreadsheet_id,
            $sheet->sheet_name,
            $changes
        );

        $sheet->update([
            'last_order_synced' => now()
        ]);
    }
}
