<?php

namespace App\Console\Commands;

use App\Jobs\HandleFailedCallsJob;
use Illuminate\Console\Command;

class ProcessFailedCallsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-failed-calls-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for failed calls and trigger WhatsApp/SMS fallback';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //


          HandleFailedCallsJob::dispatch();
        $this->info('Failed calls job dispatched successfully.');
    }
}
