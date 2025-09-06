<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Package>
 */
class PackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Basic', 'Professional', 'Business', 'Enterprise']),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->randomFloat(2, 5, 100),
            'billing_cycle' => $this->faker->randomElement(['monthly', 'quarterly', 'annually']),
            'max_domains' => $this->faker->numberBetween(1, 25),
            'max_subdomains' => $this->faker->numberBetween(5, 100),
            'max_databases' => $this->faker->numberBetween(1, 25),
            'max_email_accounts' => $this->faker->numberBetween(5, 100),
            'max_ftp_accounts' => $this->faker->numberBetween(2, 25),
            'disk_quota_mb' => $this->faker->numberBetween(1000, 50000),
            'bandwidth_quota_mb' => $this->faker->numberBetween(10000, 500000),
            'max_cron_jobs' => $this->faker->numberBetween(2, 25),
            'ssl_enabled' => true,
            'backup_enabled' => true,
            'dns_management' => true,
            'file_manager' => true,
            'statistics' => true,
            'is_active' => true,
            'is_default' => false,
            'sort_order' => $this->faker->numberBetween(1, 10),
        ];
    }

    /**
     * Indicate that the package is default.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Indicate that the package is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
