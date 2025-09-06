<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Package;

class DefaultPackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = [
            [
                'name' => 'Basic',
                'description' => 'Perfect for personal websites and small projects',
                'price' => 5.99,
                'billing_cycle' => 'monthly',
                'max_domains' => 1,
                'max_subdomains' => 5,
                'max_databases' => 1,
                'max_email_accounts' => 5,
                'max_ftp_accounts' => 2,
                'disk_quota_mb' => 1000, // 1GB
                'bandwidth_quota_mb' => 10000, // 10GB
                'max_cron_jobs' => 2,
                'ssl_enabled' => true,
                'backup_enabled' => true,
                'dns_management' => true,
                'file_manager' => true,
                'statistics' => true,
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Professional',
                'description' => 'Ideal for growing businesses and e-commerce sites',
                'price' => 12.99,
                'billing_cycle' => 'monthly',
                'max_domains' => 5,
                'max_subdomains' => 25,
                'max_databases' => 5,
                'max_email_accounts' => 25,
                'max_ftp_accounts' => 10,
                'disk_quota_mb' => 5000, // 5GB
                'bandwidth_quota_mb' => 50000, // 50GB
                'max_cron_jobs' => 10,
                'ssl_enabled' => true,
                'backup_enabled' => true,
                'dns_management' => true,
                'file_manager' => true,
                'statistics' => true,
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 2,
            ],
            [
                'name' => 'Business',
                'description' => 'Perfect for high-traffic websites and applications',
                'price' => 24.99,
                'billing_cycle' => 'monthly',
                'max_domains' => 25,
                'max_subdomains' => 100,
                'max_databases' => 25,
                'max_email_accounts' => 100,
                'max_ftp_accounts' => 25,
                'disk_quota_mb' => 15000, // 15GB
                'bandwidth_quota_mb' => 150000, // 150GB
                'max_cron_jobs' => 25,
                'ssl_enabled' => true,
                'backup_enabled' => true,
                'dns_management' => true,
                'file_manager' => true,
                'statistics' => true,
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 3,
            ],
            [
                'name' => 'Enterprise',
                'description' => 'Unlimited resources for enterprise applications',
                'price' => 49.99,
                'billing_cycle' => 'monthly',
                'max_domains' => -1, // Unlimited
                'max_subdomains' => -1, // Unlimited
                'max_databases' => -1, // Unlimited
                'max_email_accounts' => -1, // Unlimited
                'max_ftp_accounts' => -1, // Unlimited
                'disk_quota_mb' => 50000, // 50GB
                'bandwidth_quota_mb' => 500000, // 500GB
                'max_cron_jobs' => -1, // Unlimited
                'ssl_enabled' => true,
                'backup_enabled' => true,
                'dns_management' => true,
                'file_manager' => true,
                'statistics' => true,
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 4,
            ],
        ];

        foreach ($packages as $packageData) {
            Package::firstOrCreate(
                ['name' => $packageData['name']],
                $packageData
            );
        }

        $this->command->info('Default hosting packages created successfully!');
    }
}
