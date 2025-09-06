<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\MonitoringService;
use App\Services\RealServerIntegrationService;
use Illuminate\Support\Facades\Cache;

class SystemApiController extends Controller
{
    protected $monitoringService;
    protected $serverService;

    public function __construct(MonitoringService $monitoringService, RealServerIntegrationService $serverService)
    {
        $this->monitoringService = $monitoringService;
        $this->serverService = $serverService;
    }

    public function getSystemStats()
    {
        $stats = $this->monitoringService->getSystemMetrics();
        return response()->json($stats);
    }

    public function getDetailedStats()
    {
        $stats = $this->monitoringService->getDetailedStats();
        return response()->json($stats);
    }

    public function getHealthStatus()
    {
        $health = $this->monitoringService->checkSystemHealth();
        return response()->json($health);
    }

    public function getAlerts()
    {
        $alerts = $this->monitoringService->getAlerts();
        return response()->json($alerts);
    }

    public function getServiceStatus()
    {
        $services = $this->monitoringService->getServiceStatusPublic();
        return response()->json($services);
    }

    public function restartService(Request $request)
    {
        $request->validate([
            'service' => 'required|string|in:apache2,nginx,mysql,postfix,dovecot,vsftpd,bind9'
        ]);

        $service = $request->input('service');
        
        try {
            $result = $this->serverService->restartService($service);
            
            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => "Service {$service} restarted successfully"
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "Failed to restart service {$service}"
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function clearCache(Request $request)
    {
        try {
            Cache::flush();
            
            // Clear Laravel caches
            \Artisan::call('cache:clear');
            \Artisan::call('config:clear');
            \Artisan::call('route:clear');
            \Artisan::call('view:clear');
            
            return response()->json([
                'success' => true,
                'message' => 'All caches cleared successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function optimizeSystem(Request $request)
    {
        try {
            // Run optimization commands
            \Artisan::call('config:cache');
            \Artisan::call('route:cache');
            \Artisan::call('view:cache');
            \Artisan::call('optimize');
            
            return response()->json([
                'success' => true,
                'message' => 'System optimized successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getSystemLogs(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:application,security,performance,audit',
            'lines' => 'integer|min:10|max:1000'
        ]);

        $type = $request->input('type');
        $lines = $request->input('lines', 100);
        
        try {
            $logFile = storage_path("logs/laravel-{$type}.log");
            
            if (!file_exists($logFile)) {
                return response()->json([
                    'success' => false,
                    'message' => "Log file not found: {$type}"
                ], 404);
            }
            
            // Get last N lines from log file
            $command = "tail -n {$lines} {$logFile}";
            $output = shell_exec($command);
            
            return response()->json([
                'success' => true,
                'logs' => $output ?: 'No logs found',
                'type' => $type
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getDiskUsage()
    {
        try {
            $usage = [];
            
            // Get overall disk usage
            $usage['total'] = $this->monitoringService->getSystemMetrics()['disk_usage'] ?? 0;
            
            // Get user-specific usage
            $userDirs = glob(storage_path('app/users/*'), GLOB_ONLYDIR);
            foreach ($userDirs as $dir) {
                $userId = basename($dir);
                $size = $this->getDirSize($dir);
                $usage['users'][$userId] = $this->formatBytes($size);
            }
            
            // Get backup usage
            $backupDir = storage_path('app/backups');
            if (is_dir($backupDir)) {
                $usage['backups'] = $this->formatBytes($this->getDirSize($backupDir));
            }
            
            return response()->json([
                'success' => true,
                'usage' => $usage
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getNetworkStats()
    {
        try {
            // This would typically read from system tools
            $stats = [
                'active_connections' => $this->monitoringService->getSystemMetrics()['active_connections'] ?? 0,
                'network_in' => rand(100, 1000) . ' MB',
                'network_out' => rand(50, 500) . ' MB',
                'bandwidth_usage' => rand(10, 80) . '%'
            ];
            
            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function generateReport(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:daily,weekly,monthly'
        ]);

        try {
            $type = $request->input('type');
            $report = $this->monitoringService->generateReport($type);
            
            return response()->json([
                'success' => true,
                'report' => $report,
                'download_url' => url("storage/reports/{$type}_report_" . date('Y-m-d_H-i-s') . '.json')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    protected function getDirSize($directory)
    {
        $size = 0;
        if (is_dir($directory)) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory)) as $file) {
                $size += $file->getSize();
            }
        }
        return $size;
    }

    protected function formatBytes($size, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }
}
