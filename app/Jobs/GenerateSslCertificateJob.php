<?php

namespace App\Jobs;

use App\Models\SslCertificate;
use App\Models\Domain;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class GenerateSslCertificateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes timeout
    public $tries = 3;

    public function __construct(
        public SslCertificate $certificate
    ) {}

    public function handle(): void
    {
        try {
            Log::info('Starting SSL certificate generation', [
                'certificate_id' => $this->certificate->id,
                'domain' => $this->certificate->domain->name
            ]);

            $this->certificate->update(['status' => 'issuing']);

            // Simulate Let's Encrypt certificate generation
            $certificateData = $this->generateCertificate();

            $this->certificate->update([
                'status' => 'active',
                'certificate' => $certificateData['certificate'],
                'private_key' => $certificateData['private_key'],
                'certificate_chain' => $certificateData['chain'],
                'issued_at' => now(),
                'expires_at' => now()->addDays(90), // Let's Encrypt certificates are valid for 90 days
            ]);

            Log::info('SSL certificate generated successfully', [
                'certificate_id' => $this->certificate->id,
                'expires_at' => $this->certificate->expires_at
            ]);

        } catch (\Exception $e) {
            Log::error('SSL certificate generation failed', [
                'certificate_id' => $this->certificate->id,
                'error' => $e->getMessage()
            ]);

            $this->certificate->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function generateCertificate(): array
    {
        $domain = $this->certificate->domain->name;
        
        // In a real implementation, this would use ACME protocol with Let's Encrypt
        // For now, we'll simulate the process
        
        // Generate RSA private key
        $privateKey = openssl_pkey_new([
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($privateKey, $privateKeyString);

        // Generate Certificate Signing Request (CSR)
        $dn = [
            "countryName" => "US",
            "stateOrProvinceName" => "California",
            "localityName" => "San Francisco",
            "organizationName" => "Hosting Panel",
            "organizationalUnitName" => "IT Department",
            "commonName" => $domain,
            "emailAddress" => "admin@{$domain}"
        ];

        $csr = openssl_csr_new($dn, $privateKey, ['digest_alg' => 'sha256']);

        // Generate self-signed certificate (in real implementation, this would be from Let's Encrypt)
        $certificate = openssl_csr_sign($csr, null, $privateKey, 365, ['digest_alg' => 'sha256']);
        
        openssl_x509_export($certificate, $certificateString);

        // Generate certificate chain (simplified)
        $chain = "-----BEGIN CERTIFICATE-----\n" .
                base64_encode("Intermediate CA Certificate") . "\n" .
                "-----END CERTIFICATE-----\n" .
                "-----BEGIN CERTIFICATE-----\n" .
                base64_encode("Root CA Certificate") . "\n" .
                "-----END CERTIFICATE-----";

        return [
            'certificate' => $certificateString,
            'private_key' => $privateKeyString,
            'chain' => $chain,
        ];
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SSL certificate generation job failed', [
            'certificate_id' => $this->certificate->id,
            'exception' => $exception->getMessage()
        ]);

        $this->certificate->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);
    }
}
