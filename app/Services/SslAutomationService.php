<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Domain;
use App\Models\SslCertificate;
use Carbon\Carbon;

class SslAutomationService
{
    protected string $acmePath;
    protected string $certPath;
    protected string $webRoot;
    protected array $acmeServers;

    public function __construct()
    {
        $this->acmePath = config('hosting.ssl.acme_path', '/opt/acme.sh');
        $this->certPath = config('hosting.ssl.cert_path', '/etc/ssl/certs');
        $this->webRoot = config('hosting.web_root', '/var/www');
        $this->acmeServers = [
            'letsencrypt' => 'https://acme-v02.api.letsencrypt.org/directory',
            'letsencrypt_test' => 'https://acme-staging-v02.api.letsencrypt.org/directory',
            'zerossl' => 'https://acme.zerossl.com/v2/DV90',
            'buypass' => 'https://api.buypass.com/acme/directory'
        ];
    }

    /**
     * Generate SSL certificate for domain
     */
    public function generateCertificate(Domain $domain, string $provider = 'letsencrypt'): array
    {
        try {
            // Check if domain is accessible
            if (!$this->isDomainAccessible($domain->name)) {
                throw new \Exception('Domain is not accessible for validation');
            }

            // Generate certificate based on provider
            switch ($provider) {
                case 'letsencrypt':
                case 'letsencrypt_test':
                case 'zerossl':
                case 'buypass':
                    return $this->generateAcmeCertificate($domain, $provider);
                case 'self_signed':
                    return $this->generateSelfSignedCertificate($domain);
                default:
                    throw new \Exception('Unsupported SSL provider');
            }
        } catch (\Exception $e) {
            Log::error('SSL certificate generation failed', [
                'domain' => $domain->name,
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Generate ACME certificate (Let's Encrypt, ZeroSSL, etc.)
     */
    private function generateAcmeCertificate(Domain $domain, string $provider): array
    {
        $domainName = $domain->name;
        $email = $domain->user->email;
        $server = $this->acmeServers[$provider];
        
        // Create domain directory
        $domainPath = $this->certPath . '/' . $domainName;
        if (!file_exists($domainPath)) {
            mkdir($domainPath, 0755, true);
        }

        // Install acme.sh if not exists
        if (!file_exists($this->acmePath . '/acme.sh')) {
            $this->installAcmeScript();
        }

        // Generate certificate
        $command = sprintf(
            '%s/acme.sh --issue -d %s --webroot %s/%s --server %s --email %s --force',
            $this->acmePath,
            $domainName,
            $this->webRoot,
            $domainName,
            $server,
            $email
        );

        $output = shell_exec($command . ' 2>&1');
        
        if ($this->isCertificateGenerated($domainName)) {
            // Install certificate
            $this->installCertificate($domain, $provider);
            
            // Save to database
            $this->saveCertificateToDatabase($domain, $provider);
            
            Log::channel('security')->info('SSL certificate generated', [
                'domain' => $domainName,
                'provider' => $provider
            ]);

            return [
                'success' => true,
                'certificate_path' => $domainPath,
                'provider' => $provider,
                'expires_at' => Carbon::now()->addMonths(3)
            ];
        } else {
            throw new \Exception('Certificate generation failed: ' . $output);
        }
    }

    /**
     * Generate self-signed certificate
     */
    private function generateSelfSignedCertificate(Domain $domain): array
    {
        $domainName = $domain->name;
        $domainPath = $this->certPath . '/' . $domainName;
        
        if (!file_exists($domainPath)) {
            mkdir($domainPath, 0755, true);
        }

        $keyPath = $domainPath . '/private.key';
        $certPath = $domainPath . '/certificate.crt';
        $csrPath = $domainPath . '/certificate.csr';

        // Generate private key
        $keyCommand = "openssl genrsa -out {$keyPath} 2048";
        shell_exec($keyCommand);

        // Generate CSR
        $csrCommand = sprintf(
            'openssl req -new -key %s -out %s -subj "/C=US/ST=State/L=City/O=Organization/CN=%s"',
            $keyPath,
            $csrPath,
            $domainName
        );
        shell_exec($csrCommand);

        // Generate certificate
        $certCommand = "openssl x509 -req -days 365 -in {$csrPath} -signkey {$keyPath} -out {$certPath}";
        shell_exec($certCommand);

        if (file_exists($certPath) && file_exists($keyPath)) {
            $this->saveCertificateToDatabase($domain, 'self_signed');
            
            Log::channel('security')->info('Self-signed SSL certificate generated', [
                'domain' => $domainName
            ]);

            return [
                'success' => true,
                'certificate_path' => $domainPath,
                'provider' => 'self_signed',
                'expires_at' => Carbon::now()->addYear()
            ];
        } else {
            throw new \Exception('Self-signed certificate generation failed');
        }
    }

    /**
     * Renew SSL certificate
     */
    public function renewCertificate(SslCertificate $certificate): bool
    {
        try {
            $domain = $certificate->domain;
            
            if ($certificate->provider === 'self_signed') {
                $result = $this->generateSelfSignedCertificate($domain);
            } else {
                $result = $this->generateAcmeCertificate($domain, $certificate->provider);
            }

            if ($result['success']) {
                $certificate->update([
                    'expires_at' => $result['expires_at'],
                    'renewed_at' => Carbon::now()
                ]);

                Log::channel('security')->info('SSL certificate renewed', [
                    'domain' => $domain->name,
                    'provider' => $certificate->provider
                ]);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('SSL certificate renewal failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Auto-renew expiring certificates
     */
    public function autoRenewCertificates(): array
    {
        $results = [];
        $expiringCertificates = SslCertificate::where('expires_at', '<=', Carbon::now()->addDays(30))
                                              ->where('auto_renew', true)
                                              ->get();

        foreach ($expiringCertificates as $certificate) {
            $results[$certificate->domain->name] = $this->renewCertificate($certificate);
        }

        Log::channel('security')->info('Auto-renewal completed', [
            'total_certificates' => count($expiringCertificates),
            'renewed' => array_sum($results)
        ]);

        return $results;
    }

    /**
     * Validate domain ownership
     */
    public function validateDomainOwnership(string $domainName): bool
    {
        try {
            // Create validation token
            $token = bin2hex(random_bytes(32));
            $validationPath = $this->webRoot . '/' . $domainName . '/.well-known/acme-challenge';
            
            if (!file_exists($validationPath)) {
                mkdir($validationPath, 0755, true);
            }

            // Create validation file
            $validationFile = $validationPath . '/' . $token;
            file_put_contents($validationFile, $token);

            // Verify the file is accessible
            $url = "http://{$domainName}/.well-known/acme-challenge/{$token}";
            $response = Http::timeout(10)->get($url);

            // Clean up
            unlink($validationFile);

            return $response->successful() && $response->body() === $token;
        } catch (\Exception $e) {
            Log::error('Domain ownership validation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check certificate status
     */
    public function checkCertificateStatus(string $domainName): array
    {
        try {
            $certPath = $this->certPath . '/' . $domainName . '/certificate.crt';
            
            if (!file_exists($certPath)) {
                return ['status' => 'not_found'];
            }

            // Get certificate info
            $command = "openssl x509 -in {$certPath} -text -noout";
            $output = shell_exec($command);

            // Parse expiration date
            preg_match('/Not After : (.+)/', $output, $matches);
            $expiresAt = isset($matches[1]) ? Carbon::parse($matches[1]) : null;

            // Parse issuer
            preg_match('/Issuer: (.+)/', $output, $issuerMatches);
            $issuer = isset($issuerMatches[1]) ? trim($issuerMatches[1]) : 'Unknown';

            $daysUntilExpiry = $expiresAt ? $expiresAt->diffInDays(Carbon::now()) : null;
            
            return [
                'status' => 'active',
                'expires_at' => $expiresAt,
                'days_until_expiry' => $daysUntilExpiry,
                'issuer' => $issuer,
                'is_expiring_soon' => $daysUntilExpiry && $daysUntilExpiry <= 30
            ];
        } catch (\Exception $e) {
            Log::error('Certificate status check failed: ' . $e->getMessage());
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    /**
     * Get certificate details
     */
    public function getCertificateDetails(string $domainName): array
    {
        $certPath = $this->certPath . '/' . $domainName . '/certificate.crt';
        
        if (!file_exists($certPath)) {
            return [];
        }

        try {
            $command = "openssl x509 -in {$certPath} -text -noout";
            $output = shell_exec($command);

            $details = [];
            
            // Extract various certificate details
            if (preg_match('/Subject: (.+)/', $output, $matches)) {
                $details['subject'] = trim($matches[1]);
            }
            
            if (preg_match('/Issuer: (.+)/', $output, $matches)) {
                $details['issuer'] = trim($matches[1]);
            }
            
            if (preg_match('/Not Before: (.+)/', $output, $matches)) {
                $details['valid_from'] = Carbon::parse(trim($matches[1]));
            }
            
            if (preg_match('/Not After : (.+)/', $output, $matches)) {
                $details['valid_until'] = Carbon::parse(trim($matches[1]));
            }
            
            if (preg_match('/Public Key Algorithm: (.+)/', $output, $matches)) {
                $details['algorithm'] = trim($matches[1]);
            }
            
            if (preg_match('/Signature Algorithm: (.+)/', $output, $matches)) {
                $details['signature_algorithm'] = trim($matches[1]);
            }

            // Get SAN (Subject Alternative Names)
            if (preg_match('/DNS:([^,\n]+)/', $output, $matches)) {
                $details['sans'] = array_map('trim', explode(',', $matches[1]));
            }

            return $details;
        } catch (\Exception $e) {
            Log::error('Certificate details extraction failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Revoke SSL certificate
     */
    public function revokeCertificate(SslCertificate $certificate): bool
    {
        try {
            $domain = $certificate->domain;
            
            if ($certificate->provider !== 'self_signed') {
                // Revoke ACME certificate
                $command = sprintf(
                    '%s/acme.sh --revoke -d %s',
                    $this->acmePath,
                    $domain->name
                );
                
                shell_exec($command);
            }

            // Remove certificate files
            $domainPath = $this->certPath . '/' . $domain->name;
            if (file_exists($domainPath)) {
                $this->removeDirectory($domainPath);
            }

            // Update database
            $certificate->update(['status' => 'revoked']);

            Log::channel('security')->info('SSL certificate revoked', [
                'domain' => $domain->name,
                'provider' => $certificate->provider
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('SSL certificate revocation failed: ' . $e->getMessage());
            return false;
        }
    }

    // Helper methods
    private function isDomainAccessible(string $domainName): bool
    {
        try {
            $response = Http::timeout(10)->get("http://{$domainName}");
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    private function installAcmeScript(): void
    {
        $installCommand = sprintf(
            'curl https://get.acme.sh | sh -s -- --install-online -d %s',
            $this->acmePath
        );
        
        shell_exec($installCommand);
        
        // Make executable
        chmod($this->acmePath . '/acme.sh', 0755);
    }

    private function isCertificateGenerated(string $domainName): bool
    {
        $certPath = $this->certPath . '/' . $domainName . '/certificate.crt';
        return file_exists($certPath);
    }

    private function installCertificate(Domain $domain, string $provider): void
    {
        $domainName = $domain->name;
        $domainPath = $this->certPath . '/' . $domainName;
        
        // Install certificate using acme.sh
        $command = sprintf(
            '%s/acme.sh --install-cert -d %s --cert-file %s/certificate.crt --key-file %s/private.key --fullchain-file %s/fullchain.crt --reloadcmd "systemctl reload nginx"',
            $this->acmePath,
            $domainName,
            $domainPath,
            $domainPath,
            $domainPath
        );
        
        shell_exec($command);
    }

    private function saveCertificateToDatabase(Domain $domain, string $provider): void
    {
        $expiresAt = $provider === 'self_signed' ? 
                    Carbon::now()->addYear() : 
                    Carbon::now()->addMonths(3);

        SslCertificate::updateOrCreate(
            ['domain_id' => $domain->id],
            [
                'provider' => $provider,
                'status' => 'active',
                'expires_at' => $expiresAt,
                'auto_renew' => $provider !== 'self_signed',
                'issued_at' => Carbon::now()
            ]
        );
    }

    private function removeDirectory(string $dir): void
    {
        if (!file_exists($dir)) return;
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
