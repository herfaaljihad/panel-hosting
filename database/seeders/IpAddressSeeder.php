<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\IpAddress;

class IpAddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ipAddresses = [
            // IPv4 addresses
            [
                'ip_address' => '192.168.1.100',
                'type' => 'ipv4',
                'is_available' => true,
                'is_shared' => false,
                'server_name' => 'web-server-01',
                'location' => 'US-East',
                'description' => 'Primary shared hosting IP'
            ],
            [
                'ip_address' => '192.168.1.101',
                'type' => 'ipv4',
                'is_available' => true,
                'is_shared' => false,
                'server_name' => 'web-server-01',
                'location' => 'US-East',
                'description' => 'Dedicated IP for premium accounts'
            ],
            [
                'ip_address' => '192.168.1.102',
                'type' => 'ipv4',
                'is_available' => true,
                'is_shared' => true,
                'server_name' => 'web-server-01',
                'location' => 'US-East',
                'description' => 'Shared IP for standard accounts'
            ],
            [
                'ip_address' => '203.0.113.10',
                'type' => 'ipv4',
                'is_available' => true,
                'is_shared' => false,
                'server_name' => 'web-server-02',
                'location' => 'US-West',
                'description' => 'West coast server IP'
            ],
            [
                'ip_address' => '203.0.113.11',
                'type' => 'ipv4',
                'is_available' => true,
                'is_shared' => true,
                'server_name' => 'web-server-02',
                'location' => 'US-West',
                'description' => 'Shared IP for west coast users'
            ],
            // IPv6 addresses
            [
                'ip_address' => '2001:db8:85a3::8a2e:370:7334',
                'type' => 'ipv6',
                'is_available' => true,
                'is_shared' => false,
                'server_name' => 'web-server-01',
                'location' => 'US-East',
                'description' => 'IPv6 dedicated address'
            ],
            [
                'ip_address' => '2001:db8:85a3::8a2e:370:7335',
                'type' => 'ipv6',
                'is_available' => true,
                'is_shared' => true,
                'server_name' => 'web-server-01',
                'location' => 'US-East',
                'description' => 'IPv6 shared address'
            ],
            [
                'ip_address' => '2001:db8:85a3::8a2e:370:7340',
                'type' => 'ipv6',
                'is_available' => true,
                'is_shared' => false,
                'server_name' => 'web-server-02',
                'location' => 'US-West',
                'description' => 'IPv6 west coast dedicated'
            ]
        ];

        foreach ($ipAddresses as $ip) {
            IpAddress::create($ip);
        }
    }
}
