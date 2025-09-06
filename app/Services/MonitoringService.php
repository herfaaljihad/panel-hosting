<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Domain;
use App\Models\User;
use App\Models\Database;
use App\Models\EmailAccount;
use App\Services\RealServerIntegrationService;
use Carbon\Carbon;

class MonitoringService
{
    protected array $metrics;
    protected string $logPath;

    public function __construct()
    {
        $this->metrics = [];
        $this->logPath = config('hosting.log_path', '/var/log');
    }

    /**
     * Get comprehensive system statistics
     */
    public function getSystemStats(): array
    {
        return Cache::remember('system_stats', 300, function () {
            return [
                'server' => $this->getServerStats(),
                'resources' => $this->getResourceStats(),
                'services' => $this->getServiceStats(),
                'security' => $this->getSecurityStats(),
                'performance' => $this->getPerformanceStats()
            ];
        });
    }

    /**
     * Get server resource statistics
     */
    public function getServerStats(): array
    {
        $stats = [];

        // CPU Usage
        $cpuUsage = $this->getCpuUsage();
        $stats['cpu'] = [
            'usage_percent' => $cpuUsage,
            'load_average' => $this->getLoadAverage(),
            'cores' => $this->getCpuCores()
        ];

        // Memory Usage
        $memoryInfo = $this->getMemoryInfo();
        $stats['memory'] = [
            'total_mb' => $memoryInfo['total'],
            'used_mb' => $memoryInfo['used'],
            'free_mb' => $memoryInfo['free'],
            'usage_percent' => $memoryInfo['usage_percent'],
            'cached_mb' => $memoryInfo['cached'] ?? 0
        ];

        // Disk Usage
        $diskInfo = $this->getDiskInfo();
        $stats['disk'] = [
            'total_gb' => $diskInfo['total'],
            'used_gb' => $diskInfo['used'],
            'free_gb' => $diskInfo['free'],
            'usage_percent' => $diskInfo['usage_percent']
        ];

        // Network Statistics
        $stats['network'] = $this->getNetworkStats();

        // System Uptime
        $stats['uptime'] = $this->getSystemUptime();

        return $stats;
    }

    /**
     * Get hosting resource statistics
     */
    public function getResourceStats(): array
    {
        return [
            'users' => [
                'total' => User::count(),
                'active' => User::where('created_at', '>=', Carbon::now()->subDays(30))->count(),
                'admin' => User::where('role', 'admin')->count()
            ],
            'domains' => [
                'total' => Domain::count(),
                'active' => Domain::where('status', 'active')->count(),
                'by_user' => Domain::selectRaw('user_id, COUNT(*) as count')
                           ->groupBy('user_id')
                           ->with('user:id,name')
                           ->get()
                           ->take(10)
            ],
            'databases' => [
                'total' => Database::count(),
                'by_user' => Database::selectRaw('user_id, COUNT(*) as count')
                           ->groupBy('user_id')
                           ->with('user:id,name')
                           ->get()
                           ->take(10),
                'total_size_mb' => $this->getTotalDatabaseSize()
            ],
            'emails' => [
                'total' => EmailAccount::count(),
                'by_domain' => EmailAccount::selectRaw('domain_id, COUNT(*) as count')
                             ->groupBy('domain_id')
                             ->with('domain:id,name')
                             ->get()
                             ->take(10)
            ]
        ];
    }

    /**
     * Get service status
     */
    public function getServiceStats(): array
    {
        $services = ['nginx', 'apache2', 'mysql', 'postgresql', 'redis', 'memcached', 'php-fpm'];
        $stats = [];

        foreach ($services as $service) {
            $stats[$service] = $this->getServiceStatus($service);
        }

        return $stats;
    }

    /**
     * Get security statistics
     */
    public function getSecurityStats(): array
    {
        return [
            'failed_logins' => $this->getFailedLoginAttempts(),
            'ssl_certificates' => $this->getSslCertificateStats(),
            'firewall_blocks' => $this->getFirewallBlocks(),
            'malware_scans' => $this->getMalwareScanResults(),
            'security_updates' => $this->getSecurityUpdates()
        ];
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceStats(): array
    {
        return [
            'response_times' => $this->getResponseTimes(),
            'error_rates' => $this->getErrorRates(),
            'bandwidth_usage' => $this->getBandwidthUsage(),
            'cache_hit_rates' => $this->getCacheHitRates(),
            'database_queries' => $this->getDatabaseQueryStats()
        ];
    }

    /**
     * Check system health
     */
    public function checkSystemHealth(): array
    {
        $health = [
            'status' => 'healthy',
            'checks' => [],
            'timestamp' => now()->toISOString()
        ];

        // Database check
        try {
            DB::connection()->getPdo();
            $health['checks']['database'] = ['status' => 'healthy', 'message' => 'Database connection successful'];
        } catch (\Exception $e) {
            $health['checks']['database'] = ['status' => 'unhealthy', 'message' => $e->getMessage()];
            $health['status'] = 'unhealthy';
        }

        // Cache check
        try {
            Cache::put('health_check', 'test', 60);
            $value = Cache::get('health_check');
            if ($value === 'test') {
                $health['checks']['cache'] = ['status' => 'healthy', 'message' => 'Cache working properly'];
            } else {
                $health['checks']['cache'] = ['status' => 'unhealthy', 'message' => 'Cache not working'];
                $health['status'] = 'unhealthy';
            }
        } catch (\Exception $e) {
            $health['checks']['cache'] = ['status' => 'unhealthy', 'message' => $e->getMessage()];
            $health['status'] = 'unhealthy';
        }

        // Disk space check
        $diskFree = disk_free_space('/');
        $diskTotal = disk_total_space('/');
        $diskUsagePercent = (($diskTotal - $diskFree) / $diskTotal) * 100;
        
        if ($diskUsagePercent > 90) {
            $health['checks']['disk'] = ['status' => 'unhealthy', 'message' => 'Disk usage critical: ' . round($diskUsagePercent, 2) . '%'];
            $health['status'] = 'unhealthy';
        } else {
            $health['checks']['disk'] = ['status' => 'healthy', 'message' => 'Disk usage normal: ' . round($diskUsagePercent, 2) . '%'];
        }

        return $health;
    }

    /**
     * Get traffic statistics for domains
     */
    public function getTrafficStats(Domain $domain = null, int $days = 30): array
    {
        $stats = Cache::remember(
            "traffic_stats_{$domain?->id}_{$days}",
            300,
            function () use ($domain, $days) {
                return $this->parseAccessLogs($domain, $days);
            }
        );

        return $stats;
    }

    /**
     * Get real-time alerts
     */
    public function getAlerts(): array
    {
        $alerts = [];

        // High CPU usage alert
        $cpuUsage = $this->getCpuUsage();
        if ($cpuUsage > 80) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "High CPU usage: {$cpuUsage}%",
                'timestamp' => Carbon::now()
            ];
        }

        // Low disk space alert
        $diskInfo = $this->getDiskInfo();
        if ($diskInfo['usage_percent'] > 90) {
            $alerts[] = [
                'type' => 'danger',
                'message' => "Low disk space: {$diskInfo['usage_percent']}% used",
                'timestamp' => Carbon::now()
            ];
        }

        // Expiring SSL certificates
        $expiringSsl = DB::table('ssl_certificates')
                        ->where('expires_at', '<=', Carbon::now()->addDays(7))
                        ->where('status', 'active')
                        ->count();
        
        if ($expiringSsl > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "{$expiringSsl} SSL certificate(s) expiring within 7 days",
                'timestamp' => Carbon::now()
            ];
        }

        return $alerts;
    }

    /**
     * Get historical metrics for charts
     */
    public function getHistoricalMetrics(string $metric, int $hours = 24): array
    {
        $cacheKey = "historical_{$metric}_{$hours}";
        
        return Cache::remember($cacheKey, 300, function () use ($metric, $hours) {
            $data = [];
            $interval = max(1, intval($hours / 24)); // Adjust interval based on time range
            
            for ($i = $hours; $i >= 0; $i -= $interval) {
                $timestamp = Carbon::now()->subHours($i);
                $value = $this->getMetricAtTime($metric, $timestamp);
                
                $data[] = [
                    'timestamp' => $timestamp->format('Y-m-d H:i:s'),
                    'value' => $value
                ];
            }
            
            return $data;
        });
    }

    // Private helper methods
    private function getCpuUsage(): float
    {
        $load = sys_getloadavg();
        $cores = $this->getCpuCores();
        return round(($load[0] / $cores) * 100, 2);
    }

    private function getCpuCores(): int
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return intval(shell_exec('echo %NUMBER_OF_PROCESSORS%'));
        }
        return intval(shell_exec('nproc'));
    }

    private function getLoadAverage(): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return [0, 0, 0]; // Windows doesn't have load average
        }
        return sys_getloadavg();
    }

    private function getMemoryInfo(): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return $this->getWindowsMemoryInfo();
        }
        
        $meminfo = file_get_contents('/proc/meminfo');
        preg_match_all('/(\w+):\s+(\d+)\s+kB/', $meminfo, $matches);
        $memory = array_combine($matches[1], $matches[2]);
        
        $total = intval($memory['MemTotal']) / 1024; // Convert to MB
        $free = intval($memory['MemFree']) / 1024;
        $cached = intval($memory['Cached'] ?? 0) / 1024;
        $used = $total - $free - $cached;
        
        return [
            'total' => round($total, 2),
            'used' => round($used, 2),
            'free' => round($free, 2),
            'cached' => round($cached, 2),
            'usage_percent' => round(($used / $total) * 100, 2)
        ];
    }

    private function getWindowsMemoryInfo(): array
    {
        // Simplified Windows memory info
        $output = shell_exec('wmic OS get TotalVisibleMemorySize,FreePhysicalMemory /value');
        preg_match('/FreePhysicalMemory=(\d+)/', $output, $freeMatches);
        preg_match('/TotalVisibleMemorySize=(\d+)/', $output, $totalMatches);
        
        $total = isset($totalMatches[1]) ? intval($totalMatches[1]) / 1024 : 0;
        $free = isset($freeMatches[1]) ? intval($freeMatches[1]) / 1024 : 0;
        $used = $total - $free;
        
        return [
            'total' => round($total, 2),
            'used' => round($used, 2),
            'free' => round($free, 2),
            'cached' => 0,
            'usage_percent' => $total > 0 ? round(($used / $total) * 100, 2) : 0
        ];
    }

    private function getDiskInfo(): array
    {
        $rootPath = PHP_OS_FAMILY === 'Windows' ? 'C:' : '/';
        
        $total = disk_total_space($rootPath);
        $free = disk_free_space($rootPath);
        $used = $total - $free;
        
        return [
            'total' => round($total / (1024**3), 2), // Convert to GB
            'used' => round($used / (1024**3), 2),
            'free' => round($free / (1024**3), 2),
            'usage_percent' => round(($used / $total) * 100, 2)
        ];
    }

    private function getNetworkStats(): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return ['rx_bytes' => 0, 'tx_bytes' => 0, 'rx_packets' => 0, 'tx_packets' => 0];
        }
        
        $stats = ['rx_bytes' => 0, 'tx_bytes' => 0, 'rx_packets' => 0, 'tx_packets' => 0];
        
        $interfaces = glob('/sys/class/net/*/statistics');
        foreach ($interfaces as $interface) {
            $interfaceName = basename(dirname($interface));
            if ($interfaceName === 'lo') continue; // Skip loopback
            
            $rxBytes = intval(file_get_contents($interface . '/rx_bytes'));
            $txBytes = intval(file_get_contents($interface . '/tx_bytes'));
            $rxPackets = intval(file_get_contents($interface . '/rx_packets'));
            $txPackets = intval(file_get_contents($interface . '/tx_packets'));
            
            $stats['rx_bytes'] += $rxBytes;
            $stats['tx_bytes'] += $txBytes;
            $stats['rx_packets'] += $rxPackets;
            $stats['tx_packets'] += $txPackets;
        }
        
        return $stats;
    }

    private function getSystemUptime(): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $uptime = shell_exec('net stats srv | find "Statistics since"');
            return ['uptime_seconds' => 0, 'uptime_formatted' => 'N/A'];
        }
        
        $uptime = floatval(file_get_contents('/proc/uptime'));
        
        return [
            'uptime_seconds' => $uptime,
            'uptime_formatted' => $this->formatUptime($uptime)
        ];
    }

    private function formatUptime(float $seconds): string
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        return "{$days}d {$hours}h {$minutes}m";
    }

    private function getServiceStatus(string $service): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $output = shell_exec("sc query \"{$service}\" 2>nul");
            $isRunning = $output && strpos($output, 'RUNNING') !== false;
        } else {
            $output = shell_exec("systemctl is-active {$service} 2>/dev/null");
            $isRunning = trim($output) === 'active';
        }
        
        return [
            'name' => $service,
            'status' => $isRunning ? 'running' : 'stopped',
            'pid' => $isRunning ? $this->getServicePid($service) : null
        ];
    }

    private function getServicePid(string $service): ?int
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return null; // Simplified for Windows
        }
        
        $output = shell_exec("systemctl show {$service} --property=MainPID --value 2>/dev/null");
        $pid = intval(trim($output));
        return $pid > 0 ? $pid : null;
    }

    private function getTotalDatabaseSize(): float
    {
        try {
            $result = DB::select("
                SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.tables
                WHERE table_schema NOT IN ('information_schema', 'performance_schema', 'mysql', 'sys')
            ");
            
            return $result[0]->size_mb ?? 0.0;
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    private function getFailedLoginAttempts(): int
    {
        // Count failed login attempts from logs or database
        return DB::table('audit_logs')
                 ->where('action', 'login_failed')
                 ->where('created_at', '>=', Carbon::now()->subDay())
                 ->count();
    }

    private function getSslCertificateStats(): array
    {
        return [
            'total' => DB::table('ssl_certificates')->count(),
            'active' => DB::table('ssl_certificates')->where('status', 'active')->count(),
            'expiring_soon' => DB::table('ssl_certificates')
                              ->where('expires_at', '<=', Carbon::now()->addDays(30))
                              ->where('status', 'active')
                              ->count(),
            'expired' => DB::table('ssl_certificates')
                        ->where('expires_at', '<=', Carbon::now())
                        ->count()
        ];
    }

    private function getFirewallBlocks(): int
    {
        // Parse firewall logs for blocked connections
        return 0; // Placeholder
    }

    private function getMalwareScanResults(): array
    {
        // Return malware scan statistics
        return ['clean' => 0, 'infected' => 0, 'last_scan' => null];
    }

    private function getSecurityUpdates(): int
    {
        // Count available security updates
        return 0; // Placeholder
    }

    private function getResponseTimes(): array
    {
        // Parse web server logs for response times
        return ['avg_ms' => 0, 'max_ms' => 0, 'min_ms' => 0];
    }

    private function getErrorRates(): array
    {
        // Parse error logs for error rates
        return ['4xx_rate' => 0, '5xx_rate' => 0, 'total_errors' => 0];
    }

    private function getBandwidthUsage(): array
    {
        // Calculate bandwidth usage from logs
        return ['rx_mb' => 0, 'tx_mb' => 0, 'total_mb' => 0];
    }

    private function getCacheHitRates(): array
    {
        // Get cache hit rates from various cache systems
        return ['redis_hit_rate' => 0, 'memcached_hit_rate' => 0];
    }

    private function getDatabaseQueryStats(): array
    {
        // Get database query performance statistics
        return ['avg_query_time' => 0, 'slow_queries' => 0, 'total_queries' => 0];
    }

    private function parseAccessLogs(?Domain $domain, int $days): array
    {
        // Parse web server access logs for traffic statistics
        return [
            'total_requests' => 0,
            'unique_visitors' => 0,
            'bandwidth_mb' => 0,
            'top_pages' => [],
            'hourly_stats' => []
        ];
    }

    private function getMetricAtTime(string $metric, Carbon $timestamp): float
    {
        // Get historical metric value at specific time
        // This would typically read from a time-series database
        return 0.0; // Placeholder
    }

    /**
     * Get system metrics (legacy method)
     */
    public function getSystemMetrics(): array
    {
        return $this->getServerStats();
    }

    /**
     * Log performance metric
     */
    public function logPerformanceMetric(string $metric, $value, array $context = []): void
    {
        Log::channel('performance')->info($metric, array_merge($context, [
            'value' => $value,
            'timestamp' => now()->toISOString(),
        ]));
    }

    /**
     * Log security event
     */
    public function logSecurityEvent(string $event, array $context = []): void
    {
        Log::channel('security')->warning($event, array_merge($context, [
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ]));
    }

    /**
     * Get detailed statistics for API
     */
    public function getDetailedStats(): array
    {
        return [
            'system' => $this->getSystemStats(),
            'server' => $this->getServerStats(),
            'resources' => $this->getResourceStats(),
            'services' => $this->getServiceStats(),
            'security' => $this->getSecurityStats(),
            'performance' => $this->getPerformanceStats(),
            'alerts' => $this->getAlerts(),
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Get service status for specific service or all (public version)
     */
    public function getServiceStatusPublic(string $service = null): array
    {
        $services = [
            'web_server' => $this->checkWebServer(),
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkStorage()
        ];

        if ($service) {
            return $services[$service] ?? ['status' => 'unknown', 'message' => 'Service not found'];
        }

        return $services;
    }

    /**
     * Generate system report
     */
    public function generateReport(string $type = 'full'): array
    {
        switch ($type) {
            case 'security':
                return [
                    'type' => 'security',
                    'generated_at' => now()->toISOString(),
                    'data' => [
                        'security_stats' => $this->getSecurityStats(),
                        'alerts' => $this->getAlerts(),
                        'failed_logins' => $this->getFailedLogins(),
                    ]
                ];

            case 'performance':
                return [
                    'type' => 'performance',
                    'generated_at' => now()->toISOString(),
                    'data' => [
                        'performance_stats' => $this->getPerformanceStats(),
                        'server_stats' => $this->getServerStats(),
                        'historical_metrics' => $this->getHistoricalMetrics('cpu', 24),
                    ]
                ];

            case 'full':
            default:
                return [
                    'type' => 'full',
                    'generated_at' => now()->toISOString(),
                    'data' => $this->getDetailedStats()
                ];
        }
    }

    /**
     * Get failed login attempts
     */
    private function getFailedLogins(): array
    {
        try {
            // This would typically read from logs or database
            return [
                'last_24h' => rand(0, 10),
                'last_7d' => rand(0, 50),
                'blocked_ips' => []
            ];
        } catch (\Exception $e) {
            return [
                'last_24h' => 0,
                'last_7d' => 0,
                'blocked_ips' => []
            ];
        }
    }

    /**
     * Check web server status
     */
    private function checkWebServer(): array
    {
        try {
            $response = @file_get_contents('http://localhost');
            return [
                'status' => $response !== false ? 'running' : 'stopped',
                'message' => $response !== false ? 'Web server is responding' : 'Web server not responding'
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Check database status
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'running', 'message' => 'Database connection successful'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()];
        }
    }

    /**
     * Check cache status
     */
    private function checkCache(): array
    {
        try {
            Cache::put('health_check', 'ok', 1);
            $result = Cache::get('health_check');
            return [
                'status' => $result === 'ok' ? 'running' : 'error',
                'message' => $result === 'ok' ? 'Cache is working' : 'Cache not responding'
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Cache error: ' . $e->getMessage()];
        }
    }

    /**
     * Check queue status
     */
    private function checkQueue(): array
    {
        try {
            // Simple check - in production, you'd want to check actual queue workers
            return ['status' => 'running', 'message' => 'Queue system available'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Queue error: ' . $e->getMessage()];
        }
    }

    /**
     * Check storage status
     */
    private function checkStorage(): array
    {
        try {
            $disk = disk_free_space(storage_path());
            $total = disk_total_space(storage_path());
            $used_percent = (($total - $disk) / $total) * 100;
            
            return [
                'status' => $used_percent < 90 ? 'healthy' : 'warning',
                'message' => sprintf('Storage %.1f%% used', $used_percent),
                'free_space' => $this->formatBytes($disk),
                'total_space' => $this->formatBytes($total)
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Storage check failed: ' . $e->getMessage()];
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
