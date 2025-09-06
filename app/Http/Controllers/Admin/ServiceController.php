<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Service Management Controller
 * DirectAdmin-style service control interface
 */
class ServiceController extends Controller
{
    protected array $services = [
        'apache2' => ['name' => 'Apache Web Server', 'type' => 'web'],
        'nginx' => ['name' => 'Nginx Web Server', 'type' => 'web'],
        'mysql' => ['name' => 'MySQL Database', 'type' => 'database'],
        'mariadb' => ['name' => 'MariaDB Database', 'type' => 'database'],
        'postfix' => ['name' => 'Postfix Mail Server', 'type' => 'mail'],
        'dovecot' => ['name' => 'Dovecot IMAP/POP3', 'type' => 'mail'],
        'named' => ['name' => 'BIND DNS Server', 'type' => 'dns'],
        'bind9' => ['name' => 'BIND9 DNS Server', 'type' => 'dns'],
        'ssh' => ['name' => 'SSH Service', 'type' => 'system'],
        'vsftpd' => ['name' => 'FTP Server', 'type' => 'ftp'],
        'proftpd' => ['name' => 'ProFTPD Server', 'type' => 'ftp'],
        'fail2ban' => ['name' => 'Fail2Ban Security', 'type' => 'security'],
        'ufw' => ['name' => 'Uncomplicated Firewall', 'type' => 'security'],
    ];

    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Service management dashboard
     */
    public function index()
    {
        $serviceStatuses = [];
        
        foreach ($this->services as $service => $config) {
            $serviceStatuses[$service] = [
                'name' => $config['name'],
                'type' => $config['type'],
                'status' => $this->getServiceStatus($service),
                'uptime' => $this->getServiceUptime($service),
                'memory' => $this->getServiceMemoryUsage($service),
                'last_restart' => $this->getLastRestart($service),
            ];
        }

        $systemLoad = $this->getSystemLoad();
        $diskUsage = $this->getDiskUsage();
        $memoryUsage = $this->getMemoryUsage();

        return view('admin.services.index', compact(
            'serviceStatuses', 
            'systemLoad', 
            'diskUsage', 
            'memoryUsage'
        ));
    }

    /**
     * Start a service
     */
    public function start(Request $request)
    {
        $service = $request->get('service');
        
        if (!array_key_exists($service, $this->services)) {
            return response()->json(['error' => 'Invalid service'], 400);
        }

        try {
            $result = $this->executeServiceCommand($service, 'start');
            
            Log::info("Service started", [
                'service' => $service,
                'user' => auth()->user()->email,
                'result' => $result
            ]);

            return response()->json([
                'success' => true,
                'message' => "Service {$service} started successfully",
                'status' => $this->getServiceStatus($service)
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to start service", [
                'service' => $service,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => "Failed to start {$service}: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stop a service
     */
    public function stop(Request $request)
    {
        $service = $request->get('service');
        
        if (!array_key_exists($service, $this->services)) {
            return response()->json(['error' => 'Invalid service'], 400);
        }

        try {
            $result = $this->executeServiceCommand($service, 'stop');
            
            Log::info("Service stopped", [
                'service' => $service,
                'user' => auth()->user()->email,
                'result' => $result
            ]);

            return response()->json([
                'success' => true,
                'message' => "Service {$service} stopped successfully",
                'status' => $this->getServiceStatus($service)
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to stop service", [
                'service' => $service,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => "Failed to stop {$service}: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restart a service
     */
    public function restart(Request $request)
    {
        $service = $request->get('service');
        
        if (!array_key_exists($service, $this->services)) {
            return response()->json(['error' => 'Invalid service'], 400);
        }

        try {
            $result = $this->executeServiceCommand($service, 'restart');
            
            Log::info("Service restarted", [
                'service' => $service,
                'user' => auth()->user()->email,
                'result' => $result
            ]);

            return response()->json([
                'success' => true,
                'message' => "Service {$service} restarted successfully",
                'status' => $this->getServiceStatus($service)
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to restart service", [
                'service' => $service,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => "Failed to restart {$service}: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get service status
     */
    public function status(Request $request)
    {
        $service = $request->get('service');
        
        if (!array_key_exists($service, $this->services)) {
            return response()->json(['error' => 'Invalid service'], 400);
        }

        return response()->json([
            'service' => $service,
            'status' => $this->getServiceStatus($service),
            'uptime' => $this->getServiceUptime($service),
            'memory' => $this->getServiceMemoryUsage($service),
        ]);
    }

    /**
     * Execute service command
     */
    private function executeServiceCommand(string $service, string $action): array
    {
        // For Windows development, simulate the commands
        if (PHP_OS_FAMILY === 'Windows') {
            return $this->simulateServiceCommand($service, $action);
        }

        // For Linux production
        $process = new Process(['systemctl', $action, $service]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        return [
            'output' => $process->getOutput(),
            'exit_code' => $process->getExitCode()
        ];
    }

    /**
     * Simulate service commands for development
     */
    private function simulateServiceCommand(string $service, string $action): array
    {
        sleep(1); // Simulate processing time
        
        return [
            'output' => "Simulated: {$action} {$service} completed successfully",
            'exit_code' => 0
        ];
    }

    /**
     * Get service status
     */
    private function getServiceStatus(string $service): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // Simulate status for development
            $statuses = ['active', 'inactive', 'failed'];
            return [
                'active' => $statuses[array_rand($statuses)] === 'active',
                'enabled' => true,
                'status' => $statuses[array_rand($statuses)]
            ];
        }

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
                'status' => $isActive ? 'active' : 'inactive'
            ];
        } catch (\Exception $e) {
            return [
                'active' => false,
                'enabled' => false,
                'status' => 'unknown'
            ];
        }
    }

    /**
     * Get service uptime
     */
    private function getServiceUptime(string $service): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return '2d 5h 30m'; // Simulated
        }

        try {
            $process = new Process(['systemctl', 'show', $service, '--property=ActiveEnterTimestamp']);
            $process->run();
            
            if ($process->isSuccessful()) {
                $output = trim($process->getOutput());
                // Parse and calculate uptime
                return $this->calculateUptime($output);
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return 'Unknown';
    }

    /**
     * Get service memory usage
     */
    private function getServiceMemoryUsage(string $service): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return rand(10, 200) . ' MB'; // Simulated
        }

        try {
            $process = new Process(['systemctl', 'show', $service, '--property=MemoryCurrent']);
            $process->run();
            
            if ($process->isSuccessful()) {
                $output = trim($process->getOutput());
                $memory = str_replace('MemoryCurrent=', '', $output);
                return $this->formatMemory($memory);
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return 'Unknown';
    }

    /**
     * Get last restart time
     */
    private function getLastRestart(string $service): string
    {
        // Simulated for now
        return now()->subHours(rand(1, 48))->diffForHumans();
    }

    /**
     * Helper methods
     */
    private function getSystemLoad(): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return ['1min' => 0.5, '5min' => 0.3, '15min' => 0.2];
        }

        $load = sys_getloadavg();
        return [
            '1min' => $load[0] ?? 0,
            '5min' => $load[1] ?? 0,
            '15min' => $load[2] ?? 0
        ];
    }

    private function getDiskUsage(): array
    {
        return [
            'used' => '45.2 GB',
            'total' => '100 GB',
            'percentage' => 45
        ];
    }

    private function getMemoryUsage(): array
    {
        return [
            'used' => '3.2 GB',
            'total' => '8.0 GB',
            'percentage' => 40
        ];
    }

    private function calculateUptime(string $timestamp): string
    {
        // Simplified uptime calculation
        return '2d 5h 30m';
    }

    private function formatMemory(string $bytes): string
    {
        $bytes = (int) $bytes;
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
        if ($bytes < 1024 * 1024 * 1024) return round($bytes / (1024 * 1024), 1) . ' MB';
        return round($bytes / (1024 * 1024 * 1024), 1) . ' GB';
    }
}
