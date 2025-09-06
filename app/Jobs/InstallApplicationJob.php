<?php

namespace App\Jobs;

use App\Services\AutoInstallerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class InstallApplicationJob implements ShouldQueue
{
    use Queueable;

    public array $installationData;

    public function __construct(array $installationData)
    {
        $this->installationData = $installationData;
    }

    public function handle(): void
    {
        $installerService = new AutoInstallerService();
        
        try {
            $result = $installerService->installApp($this->installationData);
            
            if ($result) {
                Log::info('Application installation completed', [
                    'app' => $this->installationData['app']['name'],
                    'domain' => $this->installationData['domain']['name']
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Application installation failed', [
                'error' => $e->getMessage(),
                'app' => $this->installationData['app']['name']
            ]);
        }
    }
}
