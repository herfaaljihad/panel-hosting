<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Plugin;
use App\Models\UpdateComment;

class PluginSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $plugins = [
            [
                'name' => 'SSL Manager',
                'slug' => 'ssl-manager',
                'version' => '1.2.3',
                'description' => 'Automatic SSL certificate management and renewal',
                'author' => 'Hosting Panel Team',
                'status' => 'active',
                'type' => 'core',
                'is_core' => true,
                'auto_update' => true,
                'install_date' => now()->subMonths(6),
                'last_update_check' => now()->subDays(1),
                'available_version' => '1.3.0',
                'update_available' => true,
                'priority' => 1
            ],
            [
                'name' => 'Email Manager',
                'slug' => 'email-manager',
                'version' => '2.1.0',
                'description' => 'Complete email account and server management',
                'author' => 'Hosting Panel Team',
                'status' => 'active',
                'type' => 'core',
                'is_core' => true,
                'auto_update' => true,
                'install_date' => now()->subMonths(8),
                'last_update_check' => now()->subHours(12),
                'priority' => 1
            ],
            [
                'name' => 'File Manager Pro',
                'slug' => 'file-manager-pro',
                'version' => '3.4.2',
                'description' => 'Advanced file management with code editing capabilities',
                'author' => 'Third Party Dev',
                'status' => 'active',
                'type' => 'extension',
                'is_core' => false,
                'auto_update' => false,
                'install_date' => now()->subMonths(3),
                'last_update_check' => now()->subDays(2),
                'available_version' => '3.5.0',
                'update_available' => true,
                'priority' => 2,
                'requirements' => [
                    'php_version' => '8.1',
                    'laravel_version' => '10.0'
                ]
            ],
            [
                'name' => 'Backup Manager',
                'slug' => 'backup-manager',
                'version' => '1.0.5',
                'description' => 'Automated backup and restore functionality',
                'author' => 'Hosting Panel Team',
                'status' => 'active',
                'type' => 'core',
                'is_core' => true,
                'auto_update' => true,
                'install_date' => now()->subMonths(4),
                'last_update_check' => now()->subHours(6),
                'priority' => 1
            ],
            [
                'name' => 'Database Tools',
                'slug' => 'database-tools',
                'version' => '2.3.1',
                'description' => 'Database management and optimization tools',
                'author' => 'Hosting Panel Team',
                'status' => 'active',
                'type' => 'core',
                'is_core' => true,
                'auto_update' => true,
                'install_date' => now()->subMonths(7),
                'last_update_check' => now()->subDays(1),
                'priority' => 1
            ],
            [
                'name' => 'Security Scanner',
                'slug' => 'security-scanner',
                'version' => '1.8.2',
                'description' => 'Comprehensive security scanning and monitoring',
                'author' => 'Security Corp',
                'status' => 'inactive',
                'type' => 'extension',
                'is_core' => false,
                'auto_update' => false,
                'install_date' => now()->subMonths(2),
                'last_update_check' => now()->subWeek(),
                'available_version' => '1.9.0',
                'update_available' => true,
                'priority' => 3,
                'requirements' => [
                    'php_version' => '8.0',
                    'memory_limit' => '256M'
                ]
            ],
            [
                'name' => 'Analytics Dashboard',
                'slug' => 'analytics-dashboard',
                'version' => '0.9.8',
                'description' => 'Advanced analytics and reporting dashboard',
                'author' => 'Analytics Inc',
                'status' => 'active',
                'type' => 'module',
                'is_core' => false,
                'auto_update' => true,
                'install_date' => now()->subMonth(),
                'last_update_check' => now()->subHours(8),
                'available_version' => '1.0.0',
                'update_available' => true,
                'priority' => 2,
                'dependencies' => [
                    'chart-js',
                    'data-tables'
                ]
            ],
            [
                'name' => 'Custom Theme Engine',
                'slug' => 'custom-theme-engine',
                'version' => '2.0.0',
                'description' => 'Custom theme and branding engine',
                'author' => 'Theme Studio',
                'status' => 'active',
                'type' => 'theme',
                'is_core' => false,
                'auto_update' => false,
                'install_date' => now()->subWeeks(3),
                'last_update_check' => now()->subDays(5),
                'priority' => 3
            ]
        ];

        foreach ($plugins as $pluginData) {
            $plugin = Plugin::create($pluginData);

            // Create some sample update comments
            if ($plugin->update_available) {
                $plugin->updateComments()->create([
                    'user_id' => null,
                    'comment_type' => UpdateComment::TYPE_UPDATE_AVAILABLE,
                    'title' => 'Update Available',
                    'message' => "Plugin {$plugin->name} has an update available from v{$plugin->version} to v{$plugin->available_version}",
                    'priority' => $plugin->is_core ? UpdateComment::PRIORITY_HIGH : UpdateComment::PRIORITY_MEDIUM,
                    'status' => UpdateComment::STATUS_PENDING,
                    'action_required' => true,
                    'metadata' => [
                        'current_version' => $plugin->version,
                        'available_version' => $plugin->available_version,
                        'plugin_type' => $plugin->type,
                        'auto_update' => $plugin->auto_update
                    ]
                ]);

                // Add security update comment for some plugins
                if (in_array($plugin->slug, ['ssl-manager', 'security-scanner'])) {
                    $plugin->updateComments()->create([
                        'user_id' => null,
                        'comment_type' => UpdateComment::TYPE_SECURITY_UPDATE,
                        'title' => 'Security Update Available',
                        'message' => "This update contains important security fixes. Please update {$plugin->name} as soon as possible.",
                        'priority' => UpdateComment::PRIORITY_CRITICAL,
                        'status' => UpdateComment::STATUS_PENDING,
                        'action_required' => true,
                        'metadata' => [
                            'security_fixes' => true,
                            'severity' => 'high'
                        ]
                    ]);
                }
            }

            // Add compatibility warning for some plugins
            if ($plugin->slug === 'analytics-dashboard') {
                $plugin->updateComments()->create([
                    'user_id' => null,
                    'comment_type' => UpdateComment::TYPE_BREAKING_CHANGE,
                    'title' => 'Breaking Changes in v1.0.0',
                    'message' => 'Version 1.0.0 introduces breaking changes to the API. Please review the changelog before updating.',
                    'priority' => UpdateComment::PRIORITY_HIGH,
                    'status' => UpdateComment::STATUS_PENDING,
                    'action_required' => true,
                    'metadata' => [
                        'breaking_changes' => true,
                        'changelog_url' => 'https://example.com/changelog'
                    ]
                ]);
            }

            // Add resolved comments for some plugins
            if ($plugin->slug === 'backup-manager') {
                $plugin->updateComments()->create([
                    'user_id' => 1, // Assuming admin user has ID 1
                    'comment_type' => UpdateComment::TYPE_UPDATE_SUCCESS,
                    'title' => 'Successfully Updated',
                    'message' => "Plugin {$plugin->name} was successfully updated to v{$plugin->version}",
                    'priority' => UpdateComment::PRIORITY_LOW,
                    'status' => UpdateComment::STATUS_RESOLVED,
                    'auto_resolve' => true,
                    'resolved_at' => now()->subDays(2),
                    'resolved_by' => 1,
                    'metadata' => [
                        'update_duration' => '45 seconds',
                        'success' => true
                    ]
                ]);
            }
        }
    }
}
