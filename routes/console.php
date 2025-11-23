<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
// new 
use Illuminate\Support\Facades\Schedule;

use App\Jobs\GenerateDailyTokensJob;






Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::command('app:mark-agents-offline')->everyMinute();
Schedule::command('app:process-failed-calls-command')->everyMinute();



// Your NEW scheduled job (Laravel 11)
Schedule::job(new GenerateDailyTokensJob())
    // ->dailyAt('00:00');
    // ->everyMinute();
        ->everyTwoMinutes();




// Artisan::command('app:mark-agents-offline', function () {
//     $this->call('app:mark-agents-offline');
// })->describe('Mark agents as offline if they have been inactive for more than 1 minute');