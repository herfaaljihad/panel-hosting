<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Plugin Model
 * 
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $version
 * @property string|null $description
 * @property string|null $author
 * @property string $status
 * @property string $type
 * @property string|null $file_path
 * @property string|null $config_file
 * @property array|null $dependencies
 * @property array|null $requirements
 * @property \Carbon\Carbon|null $install_date
 * @property \Carbon\Carbon|null $last_update_check
 * @property string|null $available_version
 * @property string|null $download_url
 * @property string|null $changelog
 * @property bool $auto_update
 * @property bool $is_core
 * @property int $priority
 * @property string|null $current_version
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class Plugin extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'version',
        'description',
        'author',
        'status',
        'type',
        'file_path',
        'config_file',
        'dependencies',
        'requirements',
        'install_date',
        'last_update_check',
        'available_version',
        'download_url',
        'changelog',
        'auto_update',
        'is_core',
        'priority'
    ];

    protected $casts = [
        'dependencies' => 'array',
        'requirements' => 'array',
        'install_date' => 'datetime',
        'last_update_check' => 'datetime',
        'auto_update' => 'boolean',
        'is_core' => 'boolean',
        'priority' => 'integer'
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_ERROR = 'error';
    const STATUS_UPDATING = 'updating';

    const TYPE_CORE = 'core';
    const TYPE_EXTENSION = 'extension';
    const TYPE_THEME = 'theme';
    const TYPE_MODULE = 'module';

    /**
     * Check if plugin has updates available
     */
    public function hasUpdatesAvailable(): bool
    {
        return !empty($this->available_version) && 
               version_compare($this->available_version, $this->version, '>');
    }

    /**
     * Get update status
     */
    public function getUpdateStatus(): string
    {
        if ($this->hasUpdatesAvailable()) {
            return 'update_available';
        }

        if ($this->last_update_check && $this->last_update_check->diffInHours(now()) > 24) {
            return 'check_pending';
        }

        return 'up_to_date';
    }

    /**
     * Get status color for UI
     */
    public function getStatusColor(): string
    {
        switch ($this->status) {
            case self::STATUS_ACTIVE:
                return 'success';
            case self::STATUS_INACTIVE:
                return 'secondary';
            case self::STATUS_ERROR:
                return 'danger';
            case self::STATUS_UPDATING:
                return 'warning';
            default:
                return 'secondary';
        }
    }

    /**
     * Get update status color
     */
    public function getUpdateStatusColor(): string
    {
        switch ($this->getUpdateStatus()) {
            case 'update_available':
                return 'warning';
            case 'check_pending':
                return 'info';
            case 'up_to_date':
                return 'success';
            default:
                return 'secondary';
        }
    }

    /**
     * Check if plugin is compatible with current system
     */
    public function isCompatible(): bool
    {
        if (empty($this->requirements)) {
            return true;
        }

        // Check PHP version
        if (isset($this->requirements['php_version'])) {
            if (!version_compare(PHP_VERSION, $this->requirements['php_version'], '>=')) {
                return false;
            }
        }

        // Check Laravel version
        if (isset($this->requirements['laravel_version'])) {
            $laravelVersion = app()->version();
            if (!version_compare($laravelVersion, $this->requirements['laravel_version'], '>=')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get all active plugins
     */
    public static function getActivePlugins()
    {
        return self::where('status', self::STATUS_ACTIVE)->get();
    }

    /**
     * Get plugins that need updates
     */
    public static function getPluginsNeedingUpdates()
    {
        return self::whereRaw("available_version IS NOT NULL AND available_version != '' AND available_version != version")->get();
    }

    /**
     * Update comments relationship
     */
    public function updateComments()
    {
        return $this->hasMany(UpdateComment::class);
    }

    /**
     * Get plugins that need update check
     */
    public static function getPluginsNeedingCheck()
    {
        return self::where(function($query) {
            $query->whereNull('last_update_check')
                  ->orWhere('last_update_check', '<', Carbon::now()->subHours(24));
        })->get();
    }

    /**
     * Check for updates and create comments if needed
     */
    public function checkForUpdates(): bool
    {
        // Simulate update check
        $hasUpdates = rand(0, 3) === 0; // 25% chance of having updates
        
        if ($hasUpdates) {
            $this->update([
                'available_version' => $this->incrementVersion($this->version),
                'update_available' => true,
                'last_update_check' => now()
            ]);

            // Create update comment
            $this->updateComments()->create([
                'user_id' => null, // System generated
                'comment_type' => UpdateComment::TYPE_UPDATE_AVAILABLE,
                'title' => 'Update Available',
                'message' => "Plugin {$this->name} has an update available (v{$this->available_version})",
                'priority' => $this->is_core ? UpdateComment::PRIORITY_HIGH : UpdateComment::PRIORITY_MEDIUM,
                'status' => UpdateComment::STATUS_PENDING,
                'action_required' => true,
                'metadata' => [
                    'current_version' => $this->version,
                    'available_version' => $this->available_version,
                    'plugin_type' => $this->type
                ]
            ]);

            return true;
        } else {
            $this->update(['last_update_check' => now()]);
            return false;
        }
    }

    /**
     * Increment version for simulation
     */
    private function incrementVersion($version)
    {
        $parts = explode('.', $version);
        if (count($parts) >= 3) {
            $parts[2] = (int)$parts[2] + 1;
        } else {
            $parts[] = '1';
        }
        return implode('.', $parts);
    }
}
