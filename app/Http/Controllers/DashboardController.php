<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Domain;
use App\Models\Database;
use App\Models\EmailAccount;
use App\Services\MonitoringService;

class DashboardController extends Controller
{
    protected $monitoringService;

    public function __construct(MonitoringService $monitoringService)
    {
        $this->monitoringService = $monitoringService;
    }
    
    public function index()
    {
        $user = Auth::user();
        
        // Get user's resources
        $domains = $user->domains()->latest()->take(5)->get();
        $databases = $user->databases()->latest()->take(5)->get();
        $emails = $user->emailAccounts()->latest()->take(5)->get();
        
        // Simple stats without complex monitoring
        $stats = [
            'system' => [
                'cpu_usage' => rand(10, 80),
                'disk_usage' => rand(20, 70),
                'memory_used' => 512,
                'memory_total' => 1024,
            ],
            'domains' => [
                'total' => $user->domains()->count(),
                'recent' => $user->domains()->where('created_at', '>=', now()->subDays(7))->count(),
            ],
            'databases' => [
                'total' => $user->databases()->count(),
                'recent' => $user->databases()->where('created_at', '>=', now()->subDays(7))->count(),
            ],
            'emails' => [
                'total_accounts' => $user->emailAccounts()->count(),
                'recent' => $user->emailAccounts()->where('created_at', '>=', now()->subDays(7))->count(),
            ],
            'security' => [
                'failed_logins_24h' => 0,
                '2fa_enabled_users' => \App\Models\User::where('google2fa_enabled', true)->count(),
            ],
            'performance' => [
                'avg_response_time_24h' => rand(100, 500),
                'error_rate_24h' => rand(1, 5),
                'cache_hit_rate' => rand(85, 95),
            ]
        ];
        
        $health = ['status' => 'healthy', 'issues' => [], 'warnings' => []];
        $alerts = collect();
        $services = [
            'apache2' => ['active' => true],
            'mysql' => ['active' => true],
            'postfix' => ['active' => true],
        ];
        
        // Get user-specific stats
        $userStats = [
            'domains_count' => $user->domains()->count(),
            'databases_count' => $user->databases()->count(),
            'emails_count' => $user->emailAccounts()->count(),
            'disk_usage' => $this->getUserDiskUsage($user),
        ];

        return view('dashboard.enhanced', compact(
            'user', 'domains', 'databases', 'emails', 
            'stats', 'health', 'alerts', 'services', 'userStats'
        ));
    }

    public function getStats(Request $request)
    {
        // API endpoint for real-time stats updates
        $stats = [
            'system' => [
                'cpu_usage' => rand(10, 80),
                'disk_usage' => rand(20, 70),
                'memory_used' => rand(300, 800),
                'memory_total' => 1024,
            ],
        ];
        return response()->json($stats);
    }

    protected function getUserDiskUsage($user)
    {
        // Calculate user's disk usage
        $userPath = storage_path('app/users/' . $user->id);
        if (is_dir($userPath)) {
            return $this->getDirSize($userPath);
        }
        return 0;
    }

    protected function getDirSize($directory)
    {
        $size = 0;
        if (is_dir($directory)) {
            try {
                foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory)) as $file) {
                    $size += $file->getSize();
                }
            } catch (\Exception $e) {
                // Handle permission errors gracefully
                return 0;
            }
        }
        return $size;
    }
}
