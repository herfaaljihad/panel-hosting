<?php

namespace App\Jobs;

use App\Models\CronJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class ExecuteCronJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour timeout
    public $tries = 1; // Don't retry cron jobs

    public function __construct(
        public CronJob $cronJob
    ) {}

    public function handle(): void
    {
        if (!$this->cronJob->is_active) {
            Log::info('Skipping inactive cron job', ['cron_job_id' => $this->cronJob->id]);
            return;
        }

        $startTime = microtime(true);
        
        try {
            Log::info('Executing cron job', [
                'cron_job_id' => $this->cronJob->id,
                'command' => $this->cronJob->command,
                'domain' => $this->cronJob->domain->name
            ]);

            $this->cronJob->update([
                'status' => 'running',
                'last_run_at' => now(),
            ]);

            $output = $this->executeCommand($this->cronJob->command);
            $duration = round(microtime(true) - $startTime, 2);

            $this->cronJob->update([
                'status' => 'completed',
                'last_output' => $output,
                'last_duration' => $duration,
                'success_count' => $this->cronJob->success_count + 1,
                'next_run_at' => $this->calculateNextRun(),
            ]);

            // Log execution
            $this->logExecution('success', $output, $duration);

            Log::info('Cron job executed successfully', [
                'cron_job_id' => $this->cronJob->id,
                'duration' => $duration
            ]);

        } catch (\Exception $e) {
            $duration = round(microtime(true) - $startTime, 2);
            
            $this->cronJob->update([
                'status' => 'failed',
                'last_output' => $e->getMessage(),
                'last_duration' => $duration,
                'failure_count' => $this->cronJob->failure_count + 1,
                'next_run_at' => $this->calculateNextRun(),
            ]);

            $this->logExecution('failed', $e->getMessage(), $duration);

            Log::error('Cron job execution failed', [
                'cron_job_id' => $this->cronJob->id,
                'error' => $e->getMessage(),
                'duration' => $duration
            ]);

            // Send notification if email is set
            if ($this->cronJob->email_output) {
                $this->sendFailureNotification($e->getMessage());
            }
        }
    }

    private function executeCommand(string $command): string
    {
        // Sanitize command to prevent shell injection
        $allowedCommands = [
            'php', 'curl', 'wget', 'mysql', 'mysqldump', 'rsync', 'tar', 'gzip',
            'find', 'ls', 'cat', 'grep', 'awk', 'sed', 'sort', 'uniq'
        ];

        $commandParts = explode(' ', $command);
        $baseCommand = $commandParts[0];

        if (!in_array($baseCommand, $allowedCommands)) {
            throw new \Exception("Command '{$baseCommand}' is not allowed for security reasons.");
        }

        // Execute command in restricted environment
        $process = new Process(explode(' ', $command));
        $process->setTimeout($this->cronJob->timeout ?? 300);
        $process->setWorkingDirectory(storage_path('cron_workspace'));

        // Create workspace directory if it doesn't exist
        if (!is_dir(storage_path('cron_workspace'))) {
            mkdir(storage_path('cron_workspace'), 0755, true);
        }

        $process->run();

        if (!$process->isSuccessful()) {
            throw new \Exception($process->getErrorOutput() ?: 'Command execution failed');
        }

        return $process->getOutput();
    }

    private function calculateNextRun(): ?\Carbon\Carbon
    {
        // Parse cron expression and calculate next run time
        $expression = $this->cronJob->schedule;
        
        try {
            $cron = new \Cron\CronExpression($expression);
            return \Carbon\Carbon::instance($cron->getNextRunDate());
        } catch (\Exception $e) {
            Log::warning('Invalid cron expression', [
                'cron_job_id' => $this->cronJob->id,
                'expression' => $expression,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function logExecution(string $status, string $output, float $duration): void
    {
        // Store execution log in database or file
        Log::channel('performance')->info('Cron job execution', [
            'cron_job_id' => $this->cronJob->id,
            'domain_id' => $this->cronJob->domain_id,
            'status' => $status,
            'output' => substr($output, 0, 1000), // Limit output length
            'duration' => $duration,
            'executed_at' => now()->toISOString(),
        ]);
    }

    private function sendFailureNotification(string $error): void
    {
        // In a real implementation, this would send an email notification
        Log::info('Cron job failure notification sent', [
            'cron_job_id' => $this->cronJob->id,
            'email' => $this->cronJob->email_output,
            'error' => $error
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Cron job execution failed critically', [
            'cron_job_id' => $this->cronJob->id,
            'exception' => $exception->getMessage()
        ]);

        $this->cronJob->update([
            'status' => 'failed',
            'failure_count' => $this->cronJob->failure_count + 1,
        ]);
    }
}
