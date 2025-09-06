<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class CacheController extends Controller
{
    /**
     * Show cache management page
     */
    public function index()
    {
        $cacheStats = $this->getCacheStats();
        
        return view('admin.cache.index', compact('cacheStats'));
    }
    
    /**
     * Clear specific cache type
     */
    public function clearCache(Request $request)
    {
        $type = $request->input('type', 'all');
        
        try {
            switch ($type) {
                case 'application':
                    Cache::flush();
                    Artisan::call('cache:clear');
                    $message = 'Application cache cleared successfully!';
                    break;
                    
                case 'config':
                    Artisan::call('config:clear');
                    $message = 'Configuration cache cleared successfully!';
                    break;
                    
                case 'route':
                    Artisan::call('route:clear');
                    $message = 'Route cache cleared successfully!';
                    break;
                    
                case 'view':
                    Artisan::call('view:clear');
                    $message = 'View cache cleared successfully!';
                    break;
                    
                case 'all':
                default:
                    Artisan::call('cache:clear-all');
                    $message = 'All caches cleared successfully!';
                    break;
            }
            
            return response()->json([
                'success' => true,
                'message' => $message
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error clearing cache: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Optimize application
     */
    public function optimize(Request $request)
    {
        try {
            $force = $request->input('force', false);
            
            // Run optimization command
            Artisan::call('app:optimize', $force ? ['--force' => true] : []);
            
            return response()->json([
                'success' => true,
                'message' => 'Application optimized successfully!'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error optimizing application: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get cache statistics
     */
    public function stats()
    {
        $stats = $this->getCacheStats();
        
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
    
    /**
     * Calculate cache statistics
     */
    private function getCacheStats()
    {
        $stats = [];
        
        // Application cache size
        try {
            $cacheDir = storage_path('framework/cache/data');
            $stats['application_cache'] = [
                'size' => $this->formatBytes($this->getDirectorySize($cacheDir)),
                'files' => $this->countFiles($cacheDir),
                'enabled' => true
            ];
        } catch (\Exception $e) {
            $stats['application_cache'] = [
                'size' => '0 B',
                'files' => 0,
                'enabled' => false
            ];
        }
        
        // Config cache
        $configCachePath = base_path('bootstrap/cache/config.php');
        $stats['config_cache'] = [
            'exists' => File::exists($configCachePath),
            'size' => File::exists($configCachePath) ? $this->formatBytes(File::size($configCachePath)) : '0 B',
            'modified' => File::exists($configCachePath) ? File::lastModified($configCachePath) : null
        ];
        
        // Route cache
        $routeCachePath = base_path('bootstrap/cache/routes-v7.php');
        $stats['route_cache'] = [
            'exists' => File::exists($routeCachePath),
            'size' => File::exists($routeCachePath) ? $this->formatBytes(File::size($routeCachePath)) : '0 B',
            'modified' => File::exists($routeCachePath) ? File::lastModified($routeCachePath) : null
        ];
        
        // View cache
        $viewCacheDir = storage_path('framework/views');
        $stats['view_cache'] = [
            'size' => $this->formatBytes($this->getDirectorySize($viewCacheDir)),
            'files' => $this->countFiles($viewCacheDir),
            'enabled' => true
        ];
        
        // Session storage
        $sessionDir = storage_path('framework/sessions');
        $stats['sessions'] = [
            'size' => $this->formatBytes($this->getDirectorySize($sessionDir)),
            'files' => $this->countFiles($sessionDir),
            'enabled' => true
        ];
        
        // Total cache size
        $totalSize = 0;
        if (isset($stats['application_cache'])) {
            $totalSize += $this->getDirectorySize(storage_path('framework/cache/data'));
        }
        $totalSize += $this->getDirectorySize($viewCacheDir);
        $totalSize += $this->getDirectorySize($sessionDir);
        
        $stats['total_size'] = $this->formatBytes($totalSize);
        
        return $stats;
    }
    
    /**
     * Get directory size
     */
    private function getDirectorySize($directory)
    {
        if (!File::exists($directory)) {
            return 0;
        }
        
        $size = 0;
        $files = File::allFiles($directory);
        
        foreach ($files as $file) {
            $size += $file->getSize();
        }
        
        return $size;
    }
    
    /**
     * Count files in directory
     */
    private function countFiles($directory)
    {
        if (!File::exists($directory)) {
            return 0;
        }
        
        return count(File::allFiles($directory));
    }
    
    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
