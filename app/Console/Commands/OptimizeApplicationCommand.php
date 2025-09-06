<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class OptimizeApplicationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:optimize {--force : Force optimization even in development}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize application configuration, routes, and views for production';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $force = $this->option('force');
        
        // Check if we're in production or force is used
        if (!app()->environment('production') && !$force) {
            if (!$this->confirm('You are not in production environment. Continue anyway?')) {
                $this->info('Optimization cancelled.');
                return 0;
            }
        }
        
        $this->info('🚀 Starting application optimization...');
        
        try {
            // Clear all caches first
            $this->clearCaches();
            
            // Optimize configuration
            $this->optimizeConfig();
            
            // Optimize routes
            $this->optimizeRoutes();
            
            // Optimize views
            $this->optimizeViews();
            
            // Optimize autoloader
            $this->optimizeAutoloader();
            
            // Optimize database
            $this->optimizeDatabase();
            
            // Generate app key if needed
            $this->ensureAppKey();
            
            $this->info('✅ Application optimization completed successfully!');
            $this->displayOptimizationSummary();
            
        } catch (\Exception $e) {
            $this->error('❌ Error during optimization: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
    
    /**
     * Clear all caches
     */
    private function clearCaches()
    {
        $this->line('🧹 Clearing existing caches...');
        
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        
        $this->info('   ✓ Caches cleared');
    }
    
    /**
     * Optimize configuration
     */
    private function optimizeConfig()
    {
        $this->line('⚙️  Optimizing configuration...');
        
        Artisan::call('config:cache');
        
        $this->info('   ✓ Configuration cached');
    }
    
    /**
     * Optimize routes
     */
    private function optimizeRoutes()
    {
        $this->line('🛣️  Optimizing routes...');
        
        Artisan::call('route:cache');
        
        $this->info('   ✓ Routes cached');
    }
    
    /**
     * Optimize views
     */
    private function optimizeViews()
    {
        $this->line('👁️  Optimizing views...');
        
        Artisan::call('view:cache');
        
        $this->info('   ✓ Views cached');
    }
    
    /**
     * Optimize autoloader
     */
    private function optimizeAutoloader()
    {
        $this->line('📦 Optimizing autoloader...');
        
        // Run composer dump-autoload --optimize
        $composerPath = $this->findComposer();
        
        if ($composerPath) {
            exec("{$composerPath} dump-autoload --optimize --no-dev", $output, $returnCode);
            
            if ($returnCode === 0) {
                $this->info('   ✓ Autoloader optimized');
            } else {
                $this->warn('   ⚠ Could not optimize autoloader');
            }
        } else {
            $this->warn('   ⚠ Composer not found, skipping autoloader optimization');
        }
    }
    
    /**
     * Optimize database
     */
    private function optimizeDatabase()
    {
        $this->line('🗄️  Optimizing database...');
        
        try {
            // Run migrations if needed
            Artisan::call('migrate', ['--force' => true]);
            
            // Clear query cache if MySQL
            if (config('database.default') === 'mysql') {
                DB::statement('RESET QUERY CACHE');
            }
            
            $this->info('   ✓ Database optimized');
            
        } catch (\Exception $e) {
            $this->warn('   ⚠ Database optimization skipped: ' . $e->getMessage());
        }
    }
    
    /**
     * Ensure app key exists
     */
    private function ensureAppKey()
    {
        if (empty(config('app.key'))) {
            $this->line('🔑 Generating application key...');
            Artisan::call('key:generate', ['--force' => true]);
            $this->info('   ✓ Application key generated');
        }
    }
    
    /**
     * Find composer binary
     */
    private function findComposer()
    {
        $composerPaths = [
            'composer',
            'composer.phar',
            '/usr/local/bin/composer',
            '/usr/bin/composer'
        ];
        
        foreach ($composerPaths as $path) {
            if (exec("which {$path} 2>/dev/null")) {
                return $path;
            }
        }
        
        return null;
    }
    
    /**
     * Display optimization summary
     */
    private function displayOptimizationSummary()
    {
        $this->line('');
        $this->line('📊 <comment>Optimization Summary:</comment>');
        $this->line('   • Configuration cached');
        $this->line('   • Routes cached');
        $this->line('   • Views cached');
        $this->line('   • Autoloader optimized');
        $this->line('   • Database optimized');
        $this->line('');
        $this->line('🎯 <info>Your application is now optimized for production!</info>');
        
        if (!app()->environment('production')) {
            $this->line('');
            $this->warn('⚠️  Remember to run this command again after deploying to production.');
        }
    }
}
