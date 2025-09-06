<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class AutoInstallerAppsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $apps = [
            'wordpress' => [
                'name' => 'WordPress',
                'version' => '6.3.1',
                'category' => 'cms',
                'description' => 'WordPress is a free and open-source content management system written in PHP and paired with a MySQL or MariaDB database.',
                'requirements' => [
                    'php' => '7.4+',
                    'mysql' => '5.6+',
                    'disk_space' => '100MB'
                ],
                'features' => [
                    'Content Management',
                    'Plugin System',
                    'Theme Support',
                    'SEO Friendly',
                    'Multi-user Support'
                ],
                'download_url' => 'https://wordpress.org/latest.zip',
                'documentation' => 'https://wordpress.org/support/',
                'logo' => 'wordpress.png',
                'screenshots' => ['wp-1.jpg', 'wp-2.jpg', 'wp-3.jpg'],
                'installation_time' => '2-3 minutes',
                'popularity' => 95
            ],
            'joomla' => [
                'name' => 'Joomla!',
                'version' => '4.4.0',
                'category' => 'cms',
                'description' => 'Joomla! is a free and open-source content management system (CMS) for publishing web content.',
                'requirements' => [
                    'php' => '7.2.5+',
                    'mysql' => '5.6+',
                    'disk_space' => '200MB'
                ],
                'features' => [
                    'Content Management',
                    'Multilingual Support',
                    'Advanced User Management',
                    'Template System',
                    'Extensions'
                ],
                'download_url' => 'https://downloads.joomla.org/cms/joomla4/4-4-0/Joomla_4-4-0-Stable-Full_Package.zip',
                'documentation' => 'https://docs.joomla.org/',
                'logo' => 'joomla.png',
                'screenshots' => ['joomla-1.jpg', 'joomla-2.jpg'],
                'installation_time' => '3-5 minutes',
                'popularity' => 75
            ],
            'drupal' => [
                'name' => 'Drupal',
                'version' => '10.1.5',
                'category' => 'cms',
                'description' => 'Drupal is a free and open-source web content management framework written in PHP.',
                'requirements' => [
                    'php' => '8.1+',
                    'mysql' => '5.7.8+',
                    'disk_space' => '150MB'
                ],
                'features' => [
                    'Content Management',
                    'Advanced Taxonomy',
                    'User Management',
                    'Module System',
                    'Security'
                ],
                'download_url' => 'https://www.drupal.org/download-latest/zip',
                'documentation' => 'https://www.drupal.org/docs',
                'logo' => 'drupal.png',
                'screenshots' => ['drupal-1.jpg', 'drupal-2.jpg'],
                'installation_time' => '5-7 minutes',
                'popularity' => 60
            ],
            'laravel' => [
                'name' => 'Laravel',
                'version' => '10.x',
                'category' => 'framework',
                'description' => 'Laravel is a web application framework with expressive, elegant syntax.',
                'requirements' => [
                    'php' => '8.1+',
                    'composer' => 'required',
                    'disk_space' => '50MB'
                ],
                'features' => [
                    'MVC Architecture',
                    'Artisan CLI',
                    'Eloquent ORM',
                    'Blade Templating',
                    'Queue System'
                ],
                'download_url' => 'composer create-project laravel/laravel',
                'documentation' => 'https://laravel.com/docs',
                'logo' => 'laravel.png',
                'screenshots' => ['laravel-1.jpg', 'laravel-2.jpg'],
                'installation_time' => '3-5 minutes',
                'popularity' => 85
            ],
            'phpmyadmin' => [
                'name' => 'phpMyAdmin',
                'version' => '5.2.1',
                'category' => 'database',
                'description' => 'phpMyAdmin is a free software tool written in PHP, intended to handle the administration of MySQL over the Web.',
                'requirements' => [
                    'php' => '7.2.5+',
                    'mysql' => '5.5+',
                    'disk_space' => '50MB'
                ],
                'features' => [
                    'Database Management',
                    'SQL Query Interface',
                    'Import/Export',
                    'User Management',
                    'Visual Designer'
                ],
                'download_url' => 'https://files.phpmyadmin.net/phpMyAdmin/5.2.1/phpMyAdmin-5.2.1-all-languages.zip',
                'documentation' => 'https://docs.phpmyadmin.net/',
                'logo' => 'phpmyadmin.png',
                'screenshots' => ['pma-1.jpg', 'pma-2.jpg'],
                'installation_time' => '1-2 minutes',
                'popularity' => 90
            ],
            'prestashop' => [
                'name' => 'PrestaShop',
                'version' => '8.1.2',
                'category' => 'ecommerce',
                'description' => 'PrestaShop is a freemium, open source e-commerce platform.',
                'requirements' => [
                    'php' => '7.2.5+',
                    'mysql' => '5.6+',
                    'disk_space' => '500MB'
                ],
                'features' => [
                    'E-commerce Platform',
                    'Product Management',
                    'Order Management',
                    'Payment Integration',
                    'Multi-store Support'
                ],
                'download_url' => 'https://github.com/PrestaShop/PrestaShop/releases/download/8.1.2/prestashop_8.1.2.zip',
                'documentation' => 'https://devdocs.prestashop.com/',
                'logo' => 'prestashop.png',
                'screenshots' => ['ps-1.jpg', 'ps-2.jpg'],
                'installation_time' => '5-10 minutes',
                'popularity' => 70
            ]
        ];

        // Create apps configuration file
        $appsConfig = [
            'categories' => [
                'cms' => 'Content Management',
                'ecommerce' => 'E-Commerce',
                'forum' => 'Forums',
                'blog' => 'Blogs',
                'framework' => 'Frameworks',
                'database' => 'Database Tools',
                'other' => 'Other Applications'
            ],
            'apps' => $apps
        ];

        // Ensure the config directory exists
        if (!file_exists(config_path('auto_installer.php'))) {
            Storage::disk('local')->put('../config/auto_installer_apps.json', json_encode($appsConfig, JSON_PRETTY_PRINT));
        }

        $this->command->info('Auto installer apps configuration created successfully!');
        $this->command->info('Available apps: ' . implode(', ', array_keys($apps)));
    }
}
