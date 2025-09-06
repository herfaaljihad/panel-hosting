<?php

namespace App\Services;

use App\Models\AppInstallation;
use App\Models\Domain;
use App\Jobs\InstallApplicationJob;
use App\Jobs\UninstallApplicationJob;
use App\Jobs\UpdateApplicationJob;
use App\Jobs\BackupApplicationJob;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ZipArchive;

class AutoInstallerService
{
    public function queueInstallation(array $installationData): void
    {
        InstallApplicationJob::dispatch($installationData);
    }

    public function installApp(array $data): bool
    {
        $app = $data['app'];
        $domain = $data['domain'];
        $installation = $data['installation'];
        $adminPassword = $data['admin_password'];
        $databasePassword = $data['database_password'] ?? null;

        try {
            // Update status to installing
            $installation->update(['status' => 'installing']);

            // Download application
            $downloadPath = $this->downloadApp($app);
            
            if (!$downloadPath) {
                throw new \Exception('Failed to download application');
            }

            // Extract application
            $extractPath = $this->extractApp($downloadPath, $domain, $installation->installation_path);
            
            if (!$extractPath) {
                throw new \Exception('Failed to extract application');
            }

            // Configure application
            $this->configureApp($app, $installation, $adminPassword, $databasePassword);

            // Set permissions
            $this->setPermissions($extractPath);

            // Create virtual host if needed
            if ($installation->installation_path === '/') {
                $this->createVirtualHost($domain, $installation);
            }

            // Enable SSL if requested
            if ($installation->ssl_enabled) {
                $this->enableSSL($domain);
            }

            // Update installation status
            $installation->update([
                'status' => 'installed',
                'installation_log' => 'Installation completed successfully'
            ]);

            // Clean up download file
            File::delete($downloadPath);

            return true;

        } catch (\Exception $e) {
            $installation->update([
                'status' => 'failed',
                'installation_log' => 'Installation failed: ' . $e->getMessage()
            ]);

            return false;
        }
    }

    public function uninstallApp(AppInstallation $installation): bool
    {
        try {
            $installationPath = $this->getInstallationPath($installation->domain, $installation->installation_path);
            
            // Remove files
            if (File::exists($installationPath)) {
                File::deleteDirectory($installationPath);
            }

            // Drop database if exists
            if ($installation->database_name) {
                DB::statement("DROP DATABASE IF EXISTS `{$installation->database_name}`");
                DB::statement("DROP USER IF EXISTS `{$installation->database_user}`@'localhost'");
            }

            // Remove installation record
            $installation->delete();

            return true;

        } catch (\Exception $e) {
            throw new \Exception('Uninstallation failed: ' . $e->getMessage());
        }
    }

    public function updateApp(AppInstallation $installation): bool
    {
        UpdateApplicationJob::dispatch($installation);
        return true;
    }

    public function backupApp(AppInstallation $installation): bool
    {
        BackupApplicationJob::dispatch($installation);
        return true;
    }

    private function downloadApp(array $app): ?string
    {
        $downloadUrl = $app['download_url'];
        $filename = $app['slug'] . '_' . $app['version'] . '.zip';
        $downloadPath = storage_path('app/downloads/' . $filename);

        // Create downloads directory if not exists
        File::ensureDirectoryExists(dirname($downloadPath));

        // Download file
        if (filter_var($downloadUrl, FILTER_VALIDATE_URL)) {
            $response = Http::timeout(300)->get($downloadUrl);
            
            if ($response->successful()) {
                File::put($downloadPath, $response->body());
                return $downloadPath;
            }
        }

        // If download fails, check for local copy
        if (File::exists(storage_path('app/installers/' . $filename))) {
            return storage_path('app/installers/' . $filename);
        }

        return null;
    }

    private function extractApp(string $downloadPath, Domain $domain, string $installationPath): ?string
    {
        $extractPath = $this->getInstallationPath($domain, $installationPath);
        
        // Create directory
        File::ensureDirectoryExists($extractPath);

        // Extract ZIP file
        $zip = new ZipArchive;
        if ($zip->open($downloadPath) === TRUE) {
            $zip->extractTo($extractPath);
            $zip->close();
            return $extractPath;
        }

        return null;
    }

    private function configureApp(array $app, AppInstallation $installation, string $adminPassword, ?string $databasePassword): void
    {
        $installationPath = $this->getInstallationPath($installation->domain, $installation->installation_path);
        
        switch ($app['name']) {
            case 'WordPress':
                $this->configureWordPress($installationPath, $installation, $adminPassword, $databasePassword);
                break;
            case 'Joomla':
                $this->configureJoomla($installationPath, $installation, $adminPassword, $databasePassword);
                break;
            case 'Drupal':
                $this->configureDrupal($installationPath, $installation, $adminPassword, $databasePassword);
                break;
            default:
                // Generic configuration
                $this->configureGeneric($installationPath, $installation, $adminPassword, $databasePassword);
                break;
        }
    }

    private function configureWordPress(string $path, AppInstallation $installation, string $adminPassword, string $databasePassword): void
    {
        $configPath = $path . '/wp-config.php';
        $sampleConfigPath = $path . '/wp-config-sample.php';

        if (File::exists($sampleConfigPath)) {
            $config = File::get($sampleConfigPath);
            
            // Replace database settings
            $config = str_replace('database_name_here', $installation->database_name, $config);
            $config = str_replace('username_here', $installation->database_user, $config);
            $config = str_replace('password_here', $databasePassword, $config);
            $config = str_replace('localhost', 'localhost', $config);

            // Add security keys
            $config = str_replace('put your unique phrase here', Str::random(64), $config);

            File::put($configPath, $config);
        }

        // Create admin user via WP-CLI if available
        $this->runWordPressInstall($path, $installation, $adminPassword);
    }

    private function configureJoomla(string $path, AppInstallation $installation, string $adminPassword, string $databasePassword): void
    {
        // Joomla configuration logic
        $configPath = $path . '/configuration.php';
        
        $config = "<?php\n";
        $config .= "class JConfig {\n";
        $config .= "    public \$host = 'localhost';\n";
        $config .= "    public \$user = '{$installation->database_user}';\n";
        $config .= "    public \$password = '{$databasePassword}';\n";
        $config .= "    public \$db = '{$installation->database_name}';\n";
        $config .= "    public \$dbtype = 'mysqli';\n";
        $config .= "    public \$secret = '" . Str::random(16) . "';\n";
        $config .= "}\n";

        File::put($configPath, $config);
    }

    private function configureDrupal(string $path, AppInstallation $installation, string $adminPassword, string $databasePassword): void
    {
        // Drupal configuration logic
        $settingsPath = $path . '/sites/default/settings.php';
        $defaultSettingsPath = $path . '/sites/default/default.settings.php';

        if (File::exists($defaultSettingsPath)) {
            File::copy($defaultSettingsPath, $settingsPath);
            
            $dbConfig = "\n\$databases['default']['default'] = array (\n";
            $dbConfig .= "  'database' => '{$installation->database_name}',\n";
            $dbConfig .= "  'username' => '{$installation->database_user}',\n";
            $dbConfig .= "  'password' => '{$databasePassword}',\n";
            $dbConfig .= "  'prefix' => '',\n";
            $dbConfig .= "  'host' => 'localhost',\n";
            $dbConfig .= "  'port' => '3306',\n";
            $dbConfig .= "  'namespace' => 'Drupal\\\\Core\\\\Database\\\\Driver\\\\mysql',\n";
            $dbConfig .= "  'driver' => 'mysql',\n";
            $dbConfig .= ");\n";

            File::append($settingsPath, $dbConfig);
        }
    }

    private function configureGeneric(string $path, AppInstallation $installation, string $adminPassword, ?string $databasePassword): void
    {
        // Generic configuration for other apps
        $configFile = $path . '/.env';
        
        $config = "DB_HOST=localhost\n";
        $config .= "DB_DATABASE={$installation->database_name}\n";
        $config .= "DB_USERNAME={$installation->database_user}\n";
        $config .= "DB_PASSWORD={$databasePassword}\n";
        $config .= "ADMIN_EMAIL={$installation->admin_email}\n";
        $config .= "ADMIN_USERNAME={$installation->admin_username}\n";
        $config .= "APP_URL=https://{$installation->app_url}\n";

        File::put($configFile, $config);
    }

    private function setPermissions(string $path): void
    {
        // Set appropriate file permissions
        $directories = [
            $path . '/wp-content/uploads',
            $path . '/wp-content/themes',
            $path . '/wp-content/plugins',
            $path . '/cache',
            $path . '/tmp',
            $path . '/logs'
        ];

        foreach ($directories as $dir) {
            if (File::exists($dir)) {
                chmod($dir, 0755);
            }
        }
    }

    private function createVirtualHost(Domain $domain, AppInstallation $installation): void
    {
        // Create Apache/Nginx virtual host configuration
        // This would integrate with your server management system
    }

    private function enableSSL(Domain $domain): void
    {
        // Enable SSL certificate for the domain
        // This would integrate with your SSL management system
    }

    private function runWordPressInstall(string $path, AppInstallation $installation, string $adminPassword): void
    {
        // Run WordPress installation via command line if WP-CLI is available
        $command = "cd {$path} && wp core install --url='{$installation->app_url}' --title='My Site' --admin_user='{$installation->admin_username}' --admin_password='{$adminPassword}' --admin_email='{$installation->admin_email}' 2>&1";
        
        // Execute command (you might want to queue this or handle it differently)
        // exec($command, $output, $returnCode);
    }

    private function getInstallationPath(Domain $domain, string $installationPath): string
    {
        $basePath = storage_path('app/domains/' . $domain->name);
        return $basePath . '/' . trim($installationPath, '/');
    }
}
