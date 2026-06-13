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


// Schedule::call(function () {

//     GoogleSheet::query()
//         ->where('active', true)
//         ->select('id')
//         ->chunkById(100, function ($sheets) {

//             foreach ($sheets as $sheet) {

//                 SyncGoogleSheetJob::dispatch(
//                     $sheet->id
//                 );
//             }
//         });
// })
//     ->everyThirtyMinutes();
    // ->everyTwoMinutes();


// Schedule::call(function () {

//     GoogleSheet::query()
//         ->where('active', true)
//         ->select('id')
//         ->chunkById(100, function ($sheets) {

//             foreach ($sheets as $sheet) {
//                 // SyncGoogleSheetJob::dispatch($sheet->id);

//                        SyncGoogleSheetJob::dispatch(
//                 $sheet->id
//             )->onQueue('google-sync');
//             }
//         });
// })
//     ->everyThirtyMinutes()
//     ->withoutOverlapping();
