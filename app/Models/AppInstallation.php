<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\AppInstallation
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $domain_id
 * @property string $app_name
 * @property string $app_version
 * @property string $installation_path
 * @property string|null $database_name
 * @property string|null $database_user
 * @property string|null $database_password
 * @property string $app_url
 * @property string|null $admin_username
 * @property string|null $admin_email
 * @property string|null $admin_password
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Domain|null $domain
 */
class AppInstallation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'domain_id',
        'app_name',
        'app_version',
        'installation_path',
        'database_name',
        'database_user',
        'admin_username',
        'admin_email',
        'app_url',
        'status',
        'installation_log',
        'auto_update',
        'backup_enabled',
        'ssl_enabled',
        'installed_at',
        'last_updated_at'
    ];

    protected $casts = [
        'auto_update' => 'boolean',
        'backup_enabled' => 'boolean',
        'ssl_enabled' => 'boolean',
        'installed_at' => 'datetime',
        'last_updated_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'installing' => '<span class="badge bg-warning">Installing</span>',
            'installed' => '<span class="badge bg-success">Installed</span>',
            'failed' => '<span class="badge bg-danger">Failed</span>',
            'updating' => '<span class="badge bg-info">Updating</span>',
            default => '<span class="badge bg-secondary">Unknown</span>'
        };
    }

    public function getAppIconAttribute(): string
    {
        return match($this->app_name) {
            'WordPress' => 'fab fa-wordpress',
            'Joomla' => 'fab fa-joomla',
            'Drupal' => 'fab fa-drupal',
            'Magento' => 'fab fa-magento',
            'PrestaShop' => 'fas fa-shopping-cart',
            'OpenCart' => 'fas fa-store',
            'Laravel' => 'fab fa-laravel',
            'CodeIgniter' => 'fas fa-code',
            'phpMyAdmin' => 'fas fa-database',
            default => 'fas fa-cube'
        };
    }

    public function isUpdateAvailable(): bool
    {
        // Logic to check if update is available
        $availableApps = config('auto_installer.apps');
        $currentApp = collect($availableApps)->firstWhere('name', $this->app_name);
        
        if (!$currentApp) {
            return false;
        }

        return version_compare($this->app_version, $currentApp['version'], '<');
    }

    public function getInstallationSize(): string
    {
        $path = $this->getInstallationPath();
        if (!is_dir($path)) {
            return '0 MB';
        }

        $size = $this->getFolderSize($path);
        return $this->formatBytes($size);
    }

    private function getInstallationPath(): string
    {
        return storage_path('app/domains/' . $this->domain->name . '/' . $this->installation_path);
    }

    private function getFolderSize($dir): int
    {
        $size = 0;
        if (is_dir($dir)) {
            foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $each) {
                $size += is_file($each) ? filesize($each) : $this->getFolderSize($each);
            }
        }
        return $size;
    }

    private function formatBytes($bytes, $precision = 2): string
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
