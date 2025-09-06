<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Advanced System Statistics Service
 * Provides DirectAdmin-level system monitoring
 */
class SystemStatsService
{
    /**
     * Get comprehensive system statistics
     */
    public function getSystemStats(): array
    {
        return Cache::remember('system_stats', 60, function () {
            return [
                'server' => $this->getServerStats(),
                'services' => $this->getServiceStats(),
                'network' => $this->getNetworkStats(),
                'disk' => $this->getDiskStats(),
                'database' => $this->getDatabaseStats(),
                'email' => $this->getEmailStats(),
                'apache' => $this->getApacheStats(),
                'php' => $this->getPhpStats(),
            ];
        });
    }

    /**
     * Get server hardware statistics
     */
    private function getServerStats(): array
    {
        return [
            'hostname' => gethostname(),
            'uptime' => $this->getUptime(),
            'load_average' => $this->getLoadAverage(),
            'cpu_usage' => $this->getCpuUsage(),
            'memory_usage' => $this->getMemoryUsage(),
            'swap_usage' => $this->getSwapUsage(),
            'processes' => $this->getProcessCount(),
            'kernel' => $this->getKernelVersion(),
        ];
    }

    /**
     * Get service status statistics
     */
    private function getServiceStats(): array
    {
        $services = ['apache2', 'nginx', 'mysql', 'mariadb', 'postfix', 'dovecot', 'named', 'bind9', 'ssh', 'ftp'];
        $stats = [];
        
        foreach ($services as $service) {
            $stats[$service] = $this->getServiceStatus($service);
        }
        
        return $stats;
    }

    /**
     * Get network statistics
     */
    private function getNetworkStats(): array
    {
        return [
            'connections' => $this->getNetworkConnections(),
            'bandwidth' => $this->getBandwidthUsage(),
            'ports' => $this->getListeningPorts(),
            'interfaces' => $this->getNetworkInterfaces(),
        ];
    }

    /**
     * Get disk usage statistics
     */
    private function getDiskStats(): array
    {
        return [
            'partitions' => $this->getDiskPartitions(),
            'inodes' => $this->getInodeUsage(),
            'io_stats' => $this->getDiskIOStats(),
        ];
    }

    /**
     * Get database statistics
     */
    private function getDatabaseStats(): array
    {
        return [
            'mysql_status' => $this->getMysqlStatus(),
            'database_sizes' => $this->getDatabaseSizes(),
            'connections' => $this->getMysqlConnections(),
            'slow_queries' => $this->getSlowQueries(),
        ];
    }

    /**
     * Get email server statistics
     */
    private function getEmailStats(): array
    {
        return [
            'postfix_queue' => $this->getPostfixQueue(),
            'dovecot_connections' => $this->getDovecotConnections(),
            'email_accounts' => $this->getEmailAccountStats(),
            'mail_logs' => $this->getRecentMailLogs(),
        ];
    }

    /**
     * Get Apache/Nginx statistics
     */
    private function getApacheStats(): array
    {
        return [
            'virtual_hosts' => $this->getVirtualHostCount(),
            'requests_per_minute' => $this->getRequestsPerMinute(),
            'error_log_entries' => $this->getRecentErrorLogs(),
            'modules' => $this->getLoadedModules(),
        ];
    }

    /**
     * Get PHP statistics
     */
    private function getPhpStats(): array
    {
        return [
            'version' => PHP_VERSION,
            'extensions' => $this->getPhpExtensions(),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'opcache_status' => $this->getOpcacheStatus(),
        ];
    }

    // Implementation methods for each stat...
    private function getUptime(): string
    {
        try {
            $process = new Process(['uptime', '-p']);
            $process->run();
            return trim($process->getOutput());
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    private function getLoadAverage(): array
    {
        $load = sys_getloadavg();
        return [
            '1min' => $load[0] ?? 0,
            '5min' => $load[1] ?? 0,
            '15min' => $load[2] ?? 0,
        ];
    }

    private function getCpuUsage(): float
    {
        try {
            $process = new Process(['top', '-bn1']);
            $process->run();
            $output = $process->getOutput();
            
            if (preg_match('/Cpu\(s\):\s+([0-9.]+)%us/', $output, $matches)) {
                return (float) $matches[1];
            }
        } catch (\Exception $e) {
            Log::warning('Could not get CPU usage: ' . $e->getMessage());
        }
        
        return 0.0;
    }

    private function getMemoryUsage(): array
    {
        try {
            $process = new Process(['free', '-m']);
            $process->run();
            $output = $process->getOutput();
            
            $lines = explode("\n", $output);
            $memLine = $lines[1];
            $parts = preg_split('/\s+/', $memLine);
            
            return [
                'total' => (int) $parts[1],
                'used' => (int) $parts[2],
                'free' => (int) $parts[3],
                'percentage' => round(($parts[2] / $parts[1]) * 100, 2),
            ];
        } catch (\Exception $e) {
            return ['total' => 0, 'used' => 0, 'free' => 0, 'percentage' => 0];
        }
    }

    private function getServiceStatus(string $service): array
    {
        try {
            $process = new Process(['systemctl', 'is-active', $service]);
            $process->run();
            $isActive = trim($process->getOutput()) === 'active';
            
            $process = new Process(['systemctl', 'is-enabled', $service]);
            $process->run();
            $isEnabled = trim($process->getOutput()) === 'enabled';
            
            return [
                'active' => $isActive,
                'enabled' => $isEnabled,
                'status' => $isActive ? 'running' : 'stopped',
            ];
        } catch (\Exception $e) {
            return ['active' => false, 'enabled' => false, 'status' => 'unknown'];
        }
    }

    // Add more implementation methods as needed...
    private function getOpcacheStatus(): array
    {
        if (function_exists('opcache_get_status')) {
            $status = opcache_get_status();
            return [
                'enabled' => $status !== false,
                'hit_rate' => $status ? round($status['opcache_statistics']['opcache_hit_rate'], 2) : 0,
                'memory_usage' => $status ? $status['memory_usage'] : [],
            ];
        }
        
        return ['enabled' => false, 'hit_rate' => 0, 'memory_usage' => []];
    }

    private function getPhpExtensions(): array
    {
        return get_loaded_extensions();
    }

    private function getDiskPartitions(): array
    {
        try {
            $process = new Process(['df', '-h']);
            $process->run();
            $output = $process->getOutput();
            
            $lines = explode("\n", trim($output));
            array_shift($lines); // Remove header
            
            $partitions = [];
            foreach ($lines as $line) {
                if (empty(trim($line))) continue;
                
                $parts = preg_split('/\s+/', $line);
                if (count($parts) >= 6) {
                    $partitions[] = [
                        'filesystem' => $parts[0],
                        'size' => $parts[1],
                        'used' => $parts[2],
                        'available' => $parts[3],
                        'percentage' => $parts[4],
                        'mount' => $parts[5],
                    ];
                }
            }
            
            return $partitions;
        } catch (\Exception $e) {
            return [];
        }
    }
}
