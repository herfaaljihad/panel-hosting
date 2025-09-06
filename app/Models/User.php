<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * App\Models\User
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string $role
 * @property bool $is_admin
 * @property string|null $google2fa_secret
 * @property int|null $package_id
 * @property float $disk_used_mb
 * @property float $bandwidth_used_mb
 * @property \Illuminate\Support\Carbon|null $last_login_at
 * @property string|null $last_login_ip
 * @property string $status
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Domain> $domains
 * @property-read int|null $domains_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Database> $databases
 * @property-read int|null $databases_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EmailAccount> $emailAccounts
 * @property-read int|null $email_accounts_count
 * @property-read \App\Models\Package|null $package
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'package_id',
        'disk_used_mb',
        'bandwidth_used_mb',
        'last_login_at',
        'last_login_ip',
        'status',
        'is_admin',
        'google2fa_enabled',
        'failed_login_attempts',
        'user_type',
        'reseller_id',
        'reseller_quota_disk_mb',
        'reseller_quota_bandwidth_mb',
        'reseller_max_users',
        'reseller_permissions',
        'can_create_resellers',
        'reseller_package_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google2fa_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'google2fa_enabled' => 'boolean',
            'failed_login_attempts' => 'integer',
            'can_create_resellers' => 'boolean',
            'reseller_permissions' => 'array',
        ];
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin' || $this->user_type === 'admin';
    }

    /**
     * Check if user is reseller
     */
    public function isReseller(): bool
    {
        return $this->user_type === 'reseller';
    }

    /**
     * Check if user is regular user
     */
    public function isUser(): bool
    {
        return $this->user_type === 'user';
    }

    /**
     * Relationships
     */
    public function domains()
    {
        return $this->hasMany(Domain::class);
    }

    public function databases()
    {
        return $this->hasMany(Database::class);
    }

    public function emailAccounts()
    {
        return $this->hasMany(EmailAccount::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function dnsRecords()
    {
        return $this->hasMany(DnsRecord::class);
    }

    public function sslCertificates()
    {
        return $this->hasMany(SslCertificate::class);
    }

    public function ftpAccounts()
    {
        return $this->hasMany(FtpAccount::class);
    }

    public function cronJobs()
    {
        return $this->hasMany(CronJob::class);
    }

    public function backups()
    {
        return $this->hasMany(Backup::class);
    }

    /**
     * Reseller relationships
     */
    public function reseller()
    {
        return $this->belongsTo(User::class, 'reseller_id');
    }

    public function resellerUsers()
    {
        return $this->hasMany(User::class, 'reseller_id');
    }

    public function resellerPackage()
    {
        return $this->belongsTo(ResellerPackage::class);
    }

    public function assignedIpAddresses()
    {
        return $this->hasMany(IpAddress::class, 'assigned_user_id');
    }

    /**
     * Check if user has exceeded package limits
     */
    public function hasExceededDomainLimit()
    {
        if (!$this->package) return false;
        return $this->domains()->count() >= $this->package->max_domains;
    }

    public function hasExceededDatabaseLimit()
    {
        if (!$this->package) return false;
        return $this->databases()->count() >= $this->package->max_databases;
    }

    public function hasExceededEmailLimit()
    {
        if (!$this->package) return false;
        return $this->emailAccounts()->count() >= $this->package->max_email_accounts;
    }

    public function hasExceededDiskQuota()
    {
        if (!$this->package) return false;
        return $this->disk_used_mb >= $this->package->disk_quota_mb;
    }

    /**
     * Reseller quota checks
     */
    public function hasExceededResellerUserLimit()
    {
        if (!$this->isReseller()) return false;
        return $this->resellerUsers()->count() >= $this->reseller_max_users;
    }

    public function hasExceededResellerDiskQuota()
    {
        if (!$this->isReseller()) return false;
        return $this->disk_used_mb >= $this->reseller_quota_disk_mb;
    }

    public function hasExceededResellerBandwidthQuota()
    {
        if (!$this->isReseller()) return false;
        return $this->bandwidth_used_mb >= $this->reseller_quota_bandwidth_mb;
    }

    /**
     * Check if reseller has permission
     */
    public function hasResellerPermission(string $permission): bool
    {
        if (!$this->isReseller()) return false;
        return in_array($permission, $this->reseller_permissions ?? []);
    }
}
