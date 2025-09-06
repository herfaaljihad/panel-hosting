<?php

namespace App\Services;

use App\Models\CronJob;
use App\Jobs\ExecuteCronJob;
use Cron\CronExpression;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CronSchedulerService
{
    /**
     * Run all due cron jobs
     */
    public function runDueJobs()
    {
        $activeCronJobs = CronJob::where('is_active', true)
            ->with('domain')
            ->get();

        $now = Carbon::now();
        $executed = 0;

        foreach ($activeCronJobs as $cronJob) {
            try {
                $cron = new CronExpression($cronJob->schedule);
                
                if ($cron->isDue($now)) {
                    ExecuteCronJob::dispatch($cronJob);
                    $executed++;
                    
                    Log::channel('performance')->info('Cron job dispatched', [
                        'cron_job_id' => $cronJob->id,
                        'command' => $cronJob->command,
                        'schedule' => $cronJob->schedule,
                        'domain' => $cronJob->domain->domain
                    ]);
                }
            } catch (\Exception $e) {
                Log::channel('security')->error('Invalid cron expression', [
                    'cron_job_id' => $cronJob->id,
                    'schedule' => $cronJob->schedule,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::channel('performance')->info('Cron scheduler completed', [
            'executed_jobs' => $executed,
            'total_active_jobs' => $activeCronJobs->count()
        ]);

        return $executed;
    }

    /**
     * Get next run time for a cron job
     */
    public function getNextRunTime(CronJob $cronJob): ?Carbon
    {
        try {
            $cron = new CronExpression($cronJob->schedule);
            return Carbon::instance($cron->getNextRunDate());
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Validate cron expression
     */
    public function validateCronExpression(string $expression): bool
    {
        try {
            new CronExpression($expression);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get human readable description of cron expression
     */
    public function getHumanReadable(string $expression): string
    {
        try {
            $cron = new CronExpression($expression);
            
            // Basic interpretation
            $parts = explode(' ', $expression);
            if (count($parts) !== 5) {
                return 'Invalid expression';
            }

            [$minute, $hour, $day, $month, $weekday] = $parts;

            if ($expression === '* * * * *') {
                return 'Every minute';
            }
            if ($expression === '0 * * * *') {
                return 'Every hour';
            }
            if ($expression === '0 0 * * *') {
                return 'Daily at midnight';
            }
            if ($expression === '0 0 * * 0') {
                return 'Weekly on Sunday';
            }
            if ($expression === '0 0 1 * *') {
                return 'Monthly on the 1st';
            }

            return "At $minute:$hour on day $day of month $month, weekday $weekday";
        } catch (\Exception $e) {
            return 'Invalid expression';
        }
    }

    /**
     * Get common cron patterns
     */
    public function getCommonPatterns(): array
    {
        return [
            '* * * * *' => 'Every minute',
            '*/5 * * * *' => 'Every 5 minutes',
            '*/15 * * * *' => 'Every 15 minutes',
            '*/30 * * * *' => 'Every 30 minutes',
            '0 * * * *' => 'Every hour',
            '0 */2 * * *' => 'Every 2 hours',
            '0 */6 * * *' => 'Every 6 hours',
            '0 0 * * *' => 'Daily at midnight',
            '0 12 * * *' => 'Daily at noon',
            '0 0 * * 0' => 'Weekly on Sunday',
            '0 0 1 * *' => 'Monthly on the 1st',
            '0 0 1 1 *' => 'Yearly on January 1st',
        ];
    }
}
