<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
// new 
use Illuminate\Support\Facades\Schedule;





Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::command('agents:mark-offline')->everyMinute();
// Artisan::command('app:mark-agents-offline', function () {
//     $this->call('app:mark-agents-offline');
// })->describe('Mark agents as offline if they have been inactive for more than 1 minute');