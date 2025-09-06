<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * SslCertificate Model
 * 
 * @property int $id
 * @property int $domain_id
 * @property string $provider
 * @property string $status
 * @property string|null $certificate_path
 * @property string|null $private_key_path
 * @property \Carbon\Carbon|null $expires_at
 * @property \Carbon\Carbon|null $issued_at
 * @property \Carbon\Carbon|null $renewed_at
 * @property bool $auto_renew
 * @property string|null $challenge_type
 * @property array|null $certificate_data
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read \App\Models\Domain $domain
 */
class SslCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain_id',
        'provider',
        'status',
        'certificate_path',
        'private_key_path',
        'expires_at',
        'issued_at',
        'renewed_at',
        'auto_renew',
        'challenge_type',
        'certificate_data'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'issued_at' => 'datetime',
        'renewed_at' => 'datetime',
        'auto_renew' => 'boolean',
        'certificate_data' => 'array'
    ];

    /**
     * Get the domain that owns the SSL certificate
     */
    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * Check if certificate is expiring soon (within 30 days)
     */
    public function isExpiringSoon(): bool
    {
        return $this->expires_at && $this->expires_at->diffInDays(Carbon::now()) <= 30;
    }

    /**
     * Check if certificate is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if certificate is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && !$this->isExpired();
    }

    /**
     * Get days until expiration
     */
    public function getDaysUntilExpiration(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        $diff = $this->expires_at->diffInDays(Carbon::now(), false);
        return $diff < 0 ? null : $diff;
    }

    /**
     * Get certificate validity period in days
     */
    public function getValidityPeriod(): ?int
    {
        if (!$this->issued_at || !$this->expires_at) {
            return null;
        }

        return $this->issued_at->diffInDays($this->expires_at);
    }

    /**
     * Scope for active certificates
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                     ->where('expires_at', '>', Carbon::now());
    }

    /**
     * Scope for expiring certificates
     */
    public function scopeExpiring($query, int $days = 30)
    {
        return $query->where('status', 'active')
                     ->where('expires_at', '<=', Carbon::now()->addDays($days))
                     ->where('expires_at', '>', Carbon::now());
    }

    /**
     * Scope for expired certificates
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', Carbon::now());
    }

    /**
     * Scope for auto-renewable certificates
     */
    public function scopeAutoRenewable($query)
    {
        return $query->where('auto_renew', true);
    }

    /**
     * Get status badge class for UI
     */
    public function getStatusBadgeClass(): string
    {
        if ($this->isExpired()) {
            return 'badge-danger';
        }
        
        if ($this->isExpiringSoon()) {
            return 'badge-warning';
        }
        
        if ($this->isActive()) {
            return 'badge-success';
        }
        
        return 'badge-secondary';
    }

    /**
     * Get status text for UI
     */
    public function getStatusText(): string
    {
        if ($this->isExpired()) {
            return 'Expired';
        }
        
        if ($this->isExpiringSoon()) {
            return 'Expiring Soon';
        }
        
        if ($this->isActive()) {
            return 'Active';
        }
        
        return ucfirst($this->status);
    }

    /**
     * Get provider display name
     */
    public function getProviderDisplayName(): string
    {
        $providers = [
            'letsencrypt' => "Let's Encrypt",
            'letsencrypt_test' => "Let's Encrypt (Test)",
            'zerossl' => 'ZeroSSL',
            'buypass' => 'Buypass',
            'self_signed' => 'Self-Signed'
        ];

        return $providers[$this->provider] ?? ucfirst($this->provider);
    }

    /**
     * Get challenge type display name
     */
    public function getChallengeTypeDisplayName(): string
    {
        $types = [
            'http-01' => 'HTTP Challenge',
            'dns-01' => 'DNS Challenge',
            'tls-alpn-01' => 'TLS-ALPN Challenge'
        ];

        return $types[$this->challenge_type] ?? ucfirst($this->challenge_type);
    }
}
