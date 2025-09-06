<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default admin user
        User::firstOrCreate(
            ['email' => 'admin@hostingpanel.com'],
            [
                'name' => 'Administrator',
                'email' => 'admin@hostingpanel.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'is_admin' => true,
                'email_verified_at' => now(),
                'status' => 'active',
            ]
        );

        // Create demo user
        User::firstOrCreate(
            ['email' => 'user@demo.com'],
            [
                'name' => 'Demo User',
                'email' => 'user@demo.com',
                'password' => Hash::make('demo123'),
                'role' => 'user',
                'is_admin' => false,
                'email_verified_at' => now(),
                'status' => 'active',
            ]
        );

        $this->command->info('Admin and demo users created successfully!');
        $this->command->line('Admin: admin@hostingpanel.com / admin123');
        $this->command->line('Demo User: user@demo.com / demo123');
    }
}
