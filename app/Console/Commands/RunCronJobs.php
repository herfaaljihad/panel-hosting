<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CronSchedulerService;

class RunCronJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run all due cron jobs';

    /**
     * Execute the console command.
     */
    public function handle(CronSchedulerService $cronScheduler)
    {
        $this->info('Starting cron job scheduler...');
        
        $executed = $cronScheduler->runDueJobs();
        
        $this->info("Cron scheduler completed. Executed {$executed} jobs.");
        
        return Command::SUCCESS;
    }
}
