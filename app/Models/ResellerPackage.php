<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class ResellerPackage
 * 
 * @package App\Models
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int $bandwidth_limit
 * @property int $disk_space_limit
 * @property int $domain_limit
 * @property int $subdomain_limit
 * @property int $email_account_limit
 * @property int $database_limit
 * @property int $ftp_account_limit
 * @property int $reseller_users_limit
 * @property float $monthly_price
 * @property float $yearly_price
 * @property boolean $is_active
 * @property array $features
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $resellers
 * @property-read int|null $resellers_count
 */
class ResellerPackage extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'bandwidth_limit',
        'disk_space_limit',
        'domain_limit',
        'subdomain_limit',
        'email_account_limit',
        'database_limit',
        'ftp_account_limit',
        'reseller_users_limit',
        'monthly_price',
        'yearly_price',
        'is_active',
        'features',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'features' => 'array',
        'monthly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
        'bandwidth_limit' => 'integer',
        'disk_space_limit' => 'integer',
        'domain_limit' => 'integer',
        'subdomain_limit' => 'integer',
        'email_account_limit' => 'integer',
        'database_limit' => 'integer',
        'ftp_account_limit' => 'integer',
        'reseller_users_limit' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the resellers associated with this package.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function resellers(): HasMany
    {
        return $this->hasMany(User::class, 'reseller_package_id');
    }

    /**
     * Check if the package is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Get formatted monthly price
     *
     * @return string
     */
    public function getFormattedMonthlyPriceAttribute(): string
    {
        return '$' . number_format((float)$this->monthly_price, 2);
    }

    /**
     * Get formatted yearly price
     *
     * @return string
     */
    public function getFormattedYearlyPriceAttribute(): string
    {
        return '$' . number_format((float)$this->yearly_price, 2);
    }

    /**
     * Get yearly savings amount
     *
     * @return float
     */
    public function getYearlySavingsAttribute(): float
    {
        return ($this->monthly_price * 12) - $this->yearly_price;
    }

    /**
     * Check if a feature is enabled
     *
     * @param string $feature
     * @return bool
     */
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    /**
     * Scope for active packages
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
