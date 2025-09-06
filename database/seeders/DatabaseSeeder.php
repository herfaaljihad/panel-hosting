<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            DefaultPackageSeeder::class,
            AdminUserSeeder::class,
            AutoInstallerAppsSeeder::class,
            ResellerPackageSeeder::class,
            IpAddressSeeder::class,
        ]);
    }
}
