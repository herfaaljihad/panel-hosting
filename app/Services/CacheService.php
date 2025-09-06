<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Domain;

class CacheService
{
    const CACHE_TTL = 3600; // 1 hour
    const STATS_CACHE_TTL = 300; // 5 minutes

    /**
     * Cache data with key and TTL
     */
    public function remember(string $key, int $ttl, callable $callback)
    {
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Put data in cache
     */
    public function put(string $key, $value, int $ttl = self::CACHE_TTL): void
    {
        Cache::put($key, $value, $ttl);
    }

    /**
     * Get data from cache
     */
    public function get(string $key, $default = null)
    {
        return Cache::get($key, $default);
    }

    /**
     * Forget cache key
     */
    public function forget(string $key): void
    {
        Cache::forget($key);
    }

    /**
     * Clear cache with pattern
     */
    public function forgetPattern(string $pattern): void
    {
        $keys = Cache::getRedis()->keys($pattern);
        if (!empty($keys)) {
            Cache::getRedis()->del($keys);
        }
    }

    /**
     * Get user statistics with caching
     */
    public function getUserStats(User $user): array
    {
        $cacheKey = "user_stats_{$user->id}";
        
        return Cache::remember($cacheKey, self::STATS_CACHE_TTL, function () use ($user) {
            return [
                'domains_count' => $user->domains()->count(),
                'databases_count' => $user->databases()->count(),
                'email_accounts_count' => $user->emailAccounts()->count(),
                'ftp_accounts_count' => $user->ftpAccounts()->count(),
                'ssl_certificates_count' => $user->sslCertificates()->count(),
                'cron_jobs_count' => $user->cronJobs()->count(),
                'backups_count' => $user->backups()->count(),
                'disk_usage' => $this->calculateDiskUsage($user),
                'bandwidth_usage' => $this->calculateBandwidthUsage($user),
            ];
        });
    }

    /**
     * Get domain statistics with caching
     */
    public function getDomainStats(Domain $domain): array
    {
        $cacheKey = "domain_stats_{$domain->id}";
        
        return Cache::remember($cacheKey, self::STATS_CACHE_TTL, function () use ($domain) {
            return [
                'databases_count' => $domain->databases()->count(),
                'email_accounts_count' => $domain->emailAccounts()->count(),
                'ftp_accounts_count' => $domain->ftpAccounts()->count(),
                'ssl_certificate' => $domain->sslCertificates()->active()->first(),
                'dns_records_count' => $domain->dnsRecords()->count(),
                'cron_jobs_count' => $domain->cronJobs()->active()->count(),
                'last_backup' => $domain->backups()->latest()->first(),
                'traffic_today' => $this->getDomainTraffic($domain, 'today'),
                'traffic_month' => $this->getDomainTraffic($domain, 'month'),
            ];
        });
    }

    /**
     * Get system-wide statistics (for admin)
     */
    public function getSystemStats(): array
    {
        $cacheKey = 'system_stats';
        
        return Cache::remember($cacheKey, self::STATS_CACHE_TTL, function () {
            return [
                'total_users' => User::count(),
                'active_users' => User::where('email_verified_at', '!=', null)->count(),
                'total_domains' => Domain::count(),
                'active_domains' => Domain::where('status', 'active')->count(),
                'total_databases' => DB::table('databases')->count(),
                'total_email_accounts' => DB::table('email_accounts')->count(),
                'ssl_certificates' => DB::table('ssl_certificates')->where('status', 'active')->count(),
                'pending_backups' => DB::table('backups')->where('status', 'pending')->count(),
                'failed_cron_jobs' => DB::table('cron_jobs')->where('status', 'failed')->count(),
                'disk_usage_total' => $this->getTotalDiskUsage(),
                'bandwidth_usage_total' => $this->getTotalBandwidthUsage(),
            ];
        });
    }

    /**
     * Cache frequently accessed data
     */
    public function cacheUserPackage(User $user)
    {
        $cacheKey = "user_package_{$user->id}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            return $user->package()->with(['users'])->first();
        });
    }

    /**
     * Cache DNS records for a domain
     */
    public function cacheDomainDnsRecords(Domain $domain)
    {
        $cacheKey = "domain_dns_{$domain->id}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($domain) {
            return $domain->dnsRecords()->orderBy('type')->orderBy('name')->get();
        });
    }

    /**
     * Invalidate user-related caches
     */
    public function invalidateUserCache(User $user): void
    {
        $keys = [
            "user_stats_{$user->id}",
            "user_package_{$user->id}",
        ];
        
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        
        // Invalidate domain caches for user's domains
        foreach ($user->domains as $domain) {
            $this->invalidateDomainCache($domain);
        }
    }

    /**
     * Invalidate domain-related caches
     */
    public function invalidateDomainCache(Domain $domain): void
    {
        $keys = [
            "domain_stats_{$domain->id}",
            "domain_dns_{$domain->id}",
        ];
        
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Invalidate system-wide caches
     */
    public function invalidateSystemCache(): void
    {
        Cache::forget('system_stats');
    }

    /**
     * Calculate disk usage for a user
     */
    private function calculateDiskUsage(User $user): int
    {
        // Simulate disk usage calculation
        $baseUsage = 100; // MB
        $domainsUsage = $user->domains()->count() * 50; // 50MB per domain
        $emailUsage = $user->emailAccounts()->count() * 25; // 25MB per email account
        $backupUsage = $user->backups()->sum('file_size') / (1024 * 1024); // Convert to MB
        
        return $baseUsage + $domainsUsage + $emailUsage + $backupUsage;
    }

    /**
     * Calculate bandwidth usage for a user
     */
    private function calculateBandwidthUsage(User $user): int
    {
        // Simulate bandwidth usage calculation
        return $user->domains()->count() * 1024; // 1GB per domain
    }

    /**
     * Get domain traffic for a specific period
     */
    private function getDomainTraffic(Domain $domain, string $period): int
    {
        // Simulate traffic calculation
        $multiplier = $period === 'today' ? 1 : 30;
        return rand(100, 1000) * $multiplier; // MB
    }

    /**
     * Get total disk usage across all users
     */
    private function getTotalDiskUsage(): int
    {
        return Cache::remember('total_disk_usage', self::STATS_CACHE_TTL, function () {
            return User::all()->sum(function ($user) {
                return $this->calculateDiskUsage($user);
            });
        });
    }

    /**
     * Get total bandwidth usage across all users
     */
    private function getTotalBandwidthUsage(): int
    {
        return Cache::remember('total_bandwidth_usage', self::STATS_CACHE_TTL, function () {
            return User::all()->sum(function ($user) {
                return $this->calculateBandwidthUsage($user);
            });
        });
    }
}
