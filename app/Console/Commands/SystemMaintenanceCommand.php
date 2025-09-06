<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MonitoringService;
use App\Services\RealServerIntegrationService;
use App\Models\Domain;
use App\Models\SslCertificate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SystemMaintenanceCommand extends Command
{
    protected $signature = 'system:maintenance 
                           {--check-ssl : Check SSL certificate expiry}
                           {--cleanup-logs : Clean up old log files}
                           {--cleanup-backups : Clean up old backup files}
                           {--update-stats : Update system statistics}
                           {--health-check : Perform system health check}
                           {--all : Run all maintenance tasks}';

    protected $description = 'Perform system maintenance tasks';

    protected $monitoringService;
    protected $serverService;

    public function __construct(MonitoringService $monitoringService, RealServerIntegrationService $serverService)
    {
        parent::__construct();
        $this->monitoringService = $monitoringService;
        $this->serverService = $serverService;
    }

    public function handle()
    {
        $this->info('Starting system maintenance...');

        if ($this->option('all')) {
            $this->checkSslCertificates();
            $this->cleanupLogs();
            $this->cleanupBackups();
            $this->updateSystemStats();
            $this->performHealthCheck();
        } else {
            if ($this->option('check-ssl')) {
                $this->checkSslCertificates();
            }

            if ($this->option('cleanup-logs')) {
                $this->cleanupLogs();
            }

            if ($this->option('cleanup-backups')) {
                $this->cleanupBackups();
            }

            if ($this->option('update-stats')) {
                $this->updateSystemStats();
            }

            if ($this->option('health-check')) {
                $this->performHealthCheck();
            }
        }

        $this->info('System maintenance completed.');
    }

    protected function checkSslCertificates()
    {
        $this->info('Checking SSL certificates...');

        $expiringSoon = Domain::where('ssl_enabled', true)
            ->where('ssl_expiry_date', '<=', now()->addDays(7))
            ->get();

        foreach ($expiringSoon as $domain) {
            $this->warn("SSL certificate for {$domain->name} expires on {$domain->ssl_expiry_date}");
            
            // Try to auto-renew if configured
            if (config('server.ssl.auto_renew')) {
                $this->info("Attempting to renew SSL certificate for {$domain->name}");
                
                if ($this->serverService->generateSslCertificate($domain)) {
                    $this->info("Successfully renewed SSL certificate for {$domain->name}");
                } else {
                    $this->error("Failed to renew SSL certificate for {$domain->name}");
                }
            }
        }

        $expired = Domain::where('ssl_enabled', true)
            ->where('ssl_expiry_date', '<', now())
            ->get();

        foreach ($expired as $domain) {
            $this->error("SSL certificate for {$domain->name} has expired!");
            
            // Send alert
            Log::channel('security')->alert("SSL certificate expired for domain: {$domain->name}");
        }

        $this->info("SSL certificate check completed. Found {$expiringSoon->count()} expiring soon, {$expired->count()} expired.");
    }

    protected function cleanupLogs()
    {
        $this->info('Cleaning up old log files...');

        $logPath = storage_path('logs');
        $cutoffDate = now()->subDays(30);
        $deletedCount = 0;

        if (is_dir($logPath)) {
            $files = glob($logPath . '/*.log');
            
            foreach ($files as $file) {
                if (filemtime($file) < $cutoffDate->timestamp) {
                    if (unlink($file)) {
                        $deletedCount++;
                    }
                }
            }
        }

        // Clean up Apache/Nginx logs if configured
        $webLogPaths = [
            '/var/log/apache2',
            '/var/log/nginx',
        ];

        foreach ($webLogPaths as $path) {
            if (is_dir($path)) {
                $files = glob($path . '/*.log.*');
                foreach ($files as $file) {
                    if (filemtime($file) < $cutoffDate->timestamp) {
                        try {
                            $this->executeServerCommand("sudo rm -f {$file}");
                            $deletedCount++;
                        } catch (\Exception $e) {
                            $this->warn("Failed to delete {$file}: " . $e->getMessage());
                        }
                    }
                }
            }
        }

        $this->info("Log cleanup completed. Deleted {$deletedCount} old log files.");
    }

    protected function cleanupBackups()
    {
        $this->info('Cleaning up old backup files...');

        $maxRetentionDays = config('server.backup.max_retention_days', 30);
        $cutoffDate = now()->subDays($maxRetentionDays);
        $deletedCount = 0;
        $deletedSize = 0;

        $backupPath = 'backups';
        if (Storage::exists($backupPath)) {
            $allFiles = Storage::allFiles($backupPath);
            
            foreach ($allFiles as $file) {
                $lastModified = Storage::lastModified($file);
                
                if ($lastModified < $cutoffDate->timestamp) {
                    $fileSize = Storage::size($file);
                    
                    if (Storage::delete($file)) {
                        $deletedCount++;
                        $deletedSize += $fileSize;
                    }
                }
            }
        }

        $deletedSizeMB = round($deletedSize / 1024 / 1024, 2);
        $this->info("Backup cleanup completed. Deleted {$deletedCount} files ({$deletedSizeMB} MB).");
    }

    protected function updateSystemStats()
    {
        $this->info('Updating system statistics...');

        try {
            $stats = $this->monitoringService->getDetailedStats();
            
            // Store stats for historical analysis
            $statsFile = storage_path('app/stats/system_stats_' . date('Y-m-d_H-i-s') . '.json');
            Storage::put($statsFile, json_encode($stats, JSON_PRETTY_PRINT));
            
            // Generate daily report if it's a new day
            $lastReportDate = Storage::exists('stats/last_report_date.txt') 
                ? Storage::get('stats/last_report_date.txt') 
                : null;
            
            if ($lastReportDate !== date('Y-m-d')) {
                $report = $this->monitoringService->generateReport('daily');
                Storage::put('stats/last_report_date.txt', date('Y-m-d'));
                $this->info('Daily report generated.');
            }
            
            $this->info('System statistics updated.');
            
        } catch (\Exception $e) {
            $this->error('Failed to update system statistics: ' . $e->getMessage());
        }
    }

    protected function performHealthCheck()
    {
        $this->info('Performing system health check...');

        $health = $this->monitoringService->checkSystemHealth();
        
        $this->info("System status: {$health['status']}");
        
        if (!empty($health['issues'])) {
            $this->error('Critical issues found:');
            foreach ($health['issues'] as $issue) {
                $this->error("- {$issue}");
            }
        }
        
        if (!empty($health['warnings'])) {
            $this->warn('Warnings:');
            foreach ($health['warnings'] as $warning) {
                $this->warn("- {$warning}");
            }
        }
        
        if ($health['status'] === 'healthy') {
            $this->info('All systems operational.');
        }
        
        // Log health status
        Log::info('System health check completed', $health);
    }

    /**
     * Execute a server command safely
     */
    protected function executeServerCommand(string $command): string
    {
        // For safety, we'll use exec instead of calling the server service directly
        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \Exception("Command failed with code {$returnCode}: " . implode("\n", $output));
        }
        
        return implode("\n", $output);
    }
}
