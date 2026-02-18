<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
// new 
use Illuminate\Support\Facades\Schedule;

use App\Jobs\GenerateDailyTokensJob;
use App\Jobs\SyncGoogleSheetJob;
use App\Models\GoogleSheet;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::command('app:mark-agents-offline')->everyMinute();
Schedule::command('app:process-failed-calls-command')->everyMinute();



// Your NEW scheduled job (Laravel 11)
Schedule::job(new GenerateDailyTokensJob())
    ->dailyAt('00:00');
// ->everyMinute();
// ->everyTwoMinutes();


//call the SyncGoogleSheetJob every 30 minutes


// Schedule::call(function () {
//     $sheets = GoogleSheet::all();
//     foreach ($sheets as $sheet) {
//         SyncGoogleSheetJob::dispatch($sheet->id);
//     }
// })->everyThirtyMinutes()
//     ->name('sync-google-sheets');
//     // ->withoutOverlapping();



Schedule::job(new SyncGoogleSheetJob('16vEgiQ5fo_C41lVuX9CttygGMcUTjPlY8XyuxRtpfzg'))
    ->dailyAt('00:00');
// ->everyMinute();
// ->everyTwoMinutes();
