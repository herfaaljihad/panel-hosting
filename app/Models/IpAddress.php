<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class IpAddress
 * 
 * @package App\Models
 * @property int $id
 * @property string $ip_address
 * @property string $type
 * @property boolean $is_available
 * @property boolean $is_shared
 * @property int|null $assigned_user_id
 * @property string|null $server_name
 * @property string|null $location
 * @property string|null $description
 * @property array|null $dns_records
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User|null $assignedUser
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Domain> $domains
 * @property-read int|null $domains_count
 */
class IpAddress extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ip_address',
        'type',
        'is_available',
        'is_shared',
        'assigned_user_id',
        'server_name',
        'location',
        'description',
        'dns_records',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_available' => 'boolean',
        'is_shared' => 'boolean',
        'dns_records' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * IP address types
     */
    const TYPE_IPV4 = 'ipv4';
    const TYPE_IPV6 = 'ipv6';

    /**
     * Get the user assigned to this IP address.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * Get the domains using this IP address.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class, 'ip_address_id');
    }

    /**
     * Check if the IP address is available
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->is_available;
    }

    /**
     * Check if the IP address is shared
     *
     * @return bool
     */
    public function isShared(): bool
    {
        return $this->is_shared;
    }

    /**
     * Check if the IP address is assigned
     *
     * @return bool
     */
    public function isAssigned(): bool
    {
        return !is_null($this->assigned_user_id);
    }

    /**
     * Check if it's an IPv4 address
     *
     * @return bool
     */
    public function isIPv4(): bool
    {
        return $this->type === self::TYPE_IPV4;
    }

    /**
     * Check if it's an IPv6 address
     *
     * @return bool
     */
    public function isIPv6(): bool
    {
        return $this->type === self::TYPE_IPV6;
    }

    /**
     * Assign IP to a user
     *
     * @param User $user
     * @return bool
     */
    public function assignToUser(User $user): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        $this->assigned_user_id = $user->id;
        $this->is_available = false;
        
        return $this->save();
    }

    /**
     * Unassign IP from user
     *
     * @return bool
     */
    public function unassign(): bool
    {
        $this->assigned_user_id = null;
        $this->is_available = true;
        
        return $this->save();
    }

    /**
     * Get formatted IP type
     *
     * @return string
     */
    public function getFormattedTypeAttribute(): string
    {
        return strtoupper($this->type);
    }

    /**
     * Get status badge color
     *
     * @return string
     */
    public function getStatusBadgeColorAttribute(): string
    {
        if ($this->isAvailable()) {
            return 'success';
        } elseif ($this->isShared()) {
            return 'warning';
        } else {
            return 'primary';
        }
    }

    /**
     * Get status text
     *
     * @return string
     */
    public function getStatusTextAttribute(): string
    {
        if ($this->isAvailable()) {
            return 'Available';
        } elseif ($this->isShared()) {
            return 'Shared';
        } else {
            return 'Assigned';
        }
    }

    /**
     * Scope for available IP addresses
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    /**
     * Scope for assigned IP addresses
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAssigned($query)
    {
        return $query->whereNotNull('assigned_user_id');
    }

    /**
     * Scope for IPv4 addresses
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIpv4($query)
    {
        return $query->where('type', self::TYPE_IPV4);
    }

    /**
     * Scope for IPv6 addresses
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIpv6($query)
    {
        return $query->where('type', self::TYPE_IPV6);
    }
}
