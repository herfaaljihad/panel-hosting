<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ResellerPackage;

class ResellerPackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = [
            [
                'name' => 'Basic Reseller',
                'description' => 'Perfect for small resellers starting their business',
                'bandwidth_limit' => 10000, // 10GB
                'disk_space_limit' => 5000, // 5GB
                'domain_limit' => 10,
                'subdomain_limit' => 50,
                'email_account_limit' => 100,
                'database_limit' => 10,
                'ftp_account_limit' => 10,
                'reseller_users_limit' => 25,
                'monthly_price' => 29.99,
                'yearly_price' => 299.99,
                'is_active' => true,
                'features' => [
                    'ssl_certificates',
                    'daily_backups',
                    'email_support',
                    'control_panel_access'
                ]
            ],
            [
                'name' => 'Professional Reseller',
                'description' => 'Ideal for growing reseller businesses',
                'bandwidth_limit' => 50000, // 50GB
                'disk_space_limit' => 25000, // 25GB
                'domain_limit' => 50,
                'subdomain_limit' => 250,
                'email_account_limit' => 500,
                'database_limit' => 50,
                'ftp_account_limit' => 50,
                'reseller_users_limit' => 100,
                'monthly_price' => 59.99,
                'yearly_price' => 599.99,
                'is_active' => true,
                'features' => [
                    'ssl_certificates',
                    'daily_backups',
                    'priority_support',
                    'control_panel_access',
                    'white_label_branding',
                    'advanced_statistics'
                ]
            ],
            [
                'name' => 'Enterprise Reseller',
                'description' => 'For large scale reseller operations',
                'bandwidth_limit' => 200000, // 200GB
                'disk_space_limit' => 100000, // 100GB
                'domain_limit' => 200,
                'subdomain_limit' => 1000,
                'email_account_limit' => 2000,
                'database_limit' => 200,
                'ftp_account_limit' => 200,
                'reseller_users_limit' => 500,
                'monthly_price' => 149.99,
                'yearly_price' => 1499.99,
                'is_active' => true,
                'features' => [
                    'ssl_certificates',
                    'daily_backups',
                    '24_7_support',
                    'control_panel_access',
                    'white_label_branding',
                    'advanced_statistics',
                    'dedicated_ip',
                    'priority_processing',
                    'custom_nameservers'
                ]
            ]
        ];

        foreach ($packages as $package) {
            ResellerPackage::create($package);
        }
    }
}
