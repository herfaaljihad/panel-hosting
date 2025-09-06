<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class StatsController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Get detailed statistics
        $stats = [
            'domains' => $user->domains()->count(),
            'databases' => $user->databases()->count(),
            'emails' => $user->emailAccounts()->count(),
            'storage' => $this->getUserStorageUsage($user->id),
            'storage_formatted' => $this->formatBytes($this->getUserStorageUsage($user->id)),
        ];

        // Get monthly data for charts
        $monthlyData = $this->getMonthlyData($user);

        return view('stats.index', compact('stats', 'monthlyData'));
    }

    private function getUserStorageUsage($userId)
    {
        $userPath = "user_files/{$userId}";
        
        if (!Storage::exists($userPath)) {
            return 0;
        }

        $files = Storage::allFiles($userPath);
        $totalSize = 0;

        foreach ($files as $file) {
            $totalSize += Storage::size($file);
        }

        return $totalSize;
    }

    private function formatBytes($size, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $base = log($size, 1024);
        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $units[floor($base)];
    }

    private function getMonthlyData($user)
    {
        $data = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $month = $date->format('M');
            
            $data['labels'][] = $month;
            $data['domains'][] = $user->domains()->whereMonth('created_at', $date->month)
                                               ->whereYear('created_at', $date->year)
                                               ->count();
            $data['databases'][] = $user->databases()->whereMonth('created_at', $date->month)
                                                    ->whereYear('created_at', $date->year)
                                                    ->count();
            $data['emails'][] = $user->emailAccounts()->whereMonth('created_at', $date->month)
                                                      ->whereYear('created_at', $date->year)
                                                      ->count();
        }

        return $data;
    }
}
