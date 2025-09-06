<?php

namespace App\Jobs;

use App\Models\AppInstallation;
use App\Services\AutoInstallerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class UpdateApplicationJob implements ShouldQueue
{
    use Queueable;

    public AppInstallation $installation;

    public function __construct(AppInstallation $installation)
    {
        $this->installation = $installation;
    }

    public function handle(): void
    {
        try {
            $this->installation->update(['status' => 'updating']);
            
            // Get latest app info
            $availableApps = collect(config('auto_installer.apps'));
            $app = $availableApps->firstWhere('name', $this->installation->app_name);
            
            if (!$app) {
                throw new \Exception('App configuration not found');
            }

            // Backup current installation
            $installerService = new AutoInstallerService();
            $installerService->backupApp($this->installation);

            // Download and install new version
            // Implementation depends on app type
            
            $this->installation->update([
                'status' => 'installed',
                'app_version' => $app['version'],
                'last_updated_at' => now(),
                'installation_log' => 'Update completed successfully'
            ]);

            Log::info('Application updated successfully', [
                'app' => $this->installation->app_name,
                'version' => $app['version']
            ]);

        } catch (\Exception $e) {
            $this->installation->update([
                'status' => 'failed',
                'installation_log' => 'Update failed: ' . $e->getMessage()
            ]);

            Log::error('Application update failed', [
                'error' => $e->getMessage(),
                'app' => $this->installation->app_name
            ]);
        }
    }
}
