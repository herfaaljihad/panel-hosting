<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * App\Models\Package
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property float $price
 * @property int $max_domains
 * @property int $max_databases
 * @property int $max_email_accounts
 * @property float $disk_quota_mb
 * @property float $bandwidth_quota_mb
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 */
class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'billing_cycle',
        'max_domains',
        'max_subdomains',
        'max_databases',
        'max_email_accounts',
        'max_ftp_accounts',
        'disk_quota_mb',
        'bandwidth_quota_mb',
        'max_cron_jobs',
        'ssl_enabled',
        'backup_enabled',
        'dns_management',
        'file_manager',
        'statistics',
        'is_active',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'ssl_enabled' => 'boolean',
        'backup_enabled' => 'boolean',
        'dns_management' => 'boolean',
        'file_manager' => 'boolean',
        'statistics' => 'boolean',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute()
    {
        return '$' . number_format((float)($this->price ?? 0), 2);
    }

    /**
     * Get formatted disk quota
     */
    public function getFormattedDiskQuotaAttribute()
    {
        return $this->formatBytes($this->disk_quota_mb * 1024 * 1024);
    }

    /**
     * Get formatted bandwidth quota
     */
    public function getFormattedBandwidthQuotaAttribute()
    {
        return $this->formatBytes($this->bandwidth_quota_mb * 1024 * 1024);
    }

    private function formatBytes($size, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $base = log($size, 1024);
        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $units[floor($base)];
    }
}
