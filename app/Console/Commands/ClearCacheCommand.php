<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class ClearCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-all {--type=all : Type of cache to clear (all|config|route|view|application)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all application caches or specific cache types';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        
        $this->info('🧹 Starting cache clearing process...');
        
        try {
            switch ($type) {
                case 'config':
                    $this->clearConfigCache();
                    break;
                case 'route':
                    $this->clearRouteCache();
                    break;
                case 'view':
                    $this->clearViewCache();
                    break;
                case 'application':
                    $this->clearApplicationCache();
                    break;
                case 'all':
                default:
                    $this->clearAllCaches();
                    break;
            }
            
            $this->info('✅ Cache clearing completed successfully!');
            
        } catch (\Exception $e) {
            $this->error('❌ Error clearing cache: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
    
    /**
     * Clear all caches
     */
    private function clearAllCaches()
    {
        $this->clearApplicationCache();
        $this->clearConfigCache();
        $this->clearRouteCache();
        $this->clearViewCache();
        $this->clearCompiledCache();
        $this->clearSessionCache();
    }
    
    /**
     * Clear application cache
     */
    private function clearApplicationCache()
    {
        $this->line('🗑️  Clearing application cache...');
        Cache::flush();
        Artisan::call('cache:clear');
        $this->info('   ✓ Application cache cleared');
    }
    
    /**
     * Clear configuration cache
     */
    private function clearConfigCache()
    {
        $this->line('⚙️  Clearing configuration cache...');
        Artisan::call('config:clear');
        $this->info('   ✓ Configuration cache cleared');
    }
    
    /**
     * Clear route cache
     */
    private function clearRouteCache()
    {
        $this->line('🛣️  Clearing route cache...');
        Artisan::call('route:clear');
        $this->info('   ✓ Route cache cleared');
    }
    
    /**
     * Clear view cache
     */
    private function clearViewCache()
    {
        $this->line('👁️  Clearing view cache...');
        Artisan::call('view:clear');
        $this->info('   ✓ View cache cleared');
    }
    
    /**
     * Clear compiled files
     */
    private function clearCompiledCache()
    {
        $this->line('📦 Clearing compiled files...');
        
        $bootstrapCache = base_path('bootstrap/cache');
        
        if (File::exists($bootstrapCache . '/packages.php')) {
            File::delete($bootstrapCache . '/packages.php');
        }
        
        if (File::exists($bootstrapCache . '/services.php')) {
            File::delete($bootstrapCache . '/services.php');
        }
        
        $this->info('   ✓ Compiled files cleared');
    }
    
    /**
     * Clear session files
     */
    private function clearSessionCache()
    {
        $this->line('🔐 Clearing session files...');
        
        $sessionPath = storage_path('framework/sessions');
        if (File::exists($sessionPath)) {
            $files = File::files($sessionPath);
            foreach ($files as $file) {
                if ($file->getFilename() !== '.gitignore') {
                    File::delete($file->getPathname());
                }
            }
        }
        
        $this->info('   ✓ Session files cleared');
    }
}
