<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * IP Management Controller
 * DirectAdmin-style IP address management
 */
class IPManagementController extends Controller
{
    /**
     * Display IP management dashboard
     */
    public function index()
    {
        // Simulated IP data for now (will be from database when IP table is ready)
        $ipAddresses = [
            [
                'id' => 1,
                'ip' => '192.168.1.100',
                'type' => 'shared',
                'status' => 'active',
                'assigned_to' => null,
                'domains_count' => 15,
                'server_name' => 'Web Server 1',
                'last_used' => now()->subHours(1)
            ],
            [
                'id' => 2,
                'ip' => '192.168.1.101',
                'type' => 'dedicated',
                'status' => 'active',
                'assigned_to' => 'john@example.com',
                'domains_count' => 3,
                'server_name' => 'Web Server 1',
                'last_used' => now()->subMinutes(30)
            ],
            [
                'id' => 3,
                'ip' => '192.168.1.102',
                'type' => 'shared',
                'status' => 'inactive',
                'assigned_to' => null,
                'domains_count' => 0,
                'server_name' => 'Web Server 2',
                'last_used' => now()->subDays(5)
            ],
            [
                'id' => 4,
                'ip' => '2001:db8::1',
                'type' => 'shared',
                'status' => 'active',
                'assigned_to' => null,
                'domains_count' => 8,
                'server_name' => 'Web Server 1',
                'last_used' => now()->subHours(3)
            ],
        ];

        $stats = [
            'total_ips' => count($ipAddresses),
            'active_ips' => collect($ipAddresses)->where('status', 'active')->count(),
            'dedicated_ips' => collect($ipAddresses)->where('type', 'dedicated')->count(),
            'shared_ips' => collect($ipAddresses)->where('type', 'shared')->count(),
        ];

        return view('admin.ip-management.index', compact('ipAddresses', 'stats'));
    }

    /**
     * Assign IP to user
     */
    public function assign(Request $request)
    {
        $request->validate([
            'ip_id' => 'required|integer',
            'user_id' => 'required|exists:users,id',
            'type' => 'required|in:shared,dedicated'
        ]);

        try {
            $user = User::findOrFail($request->user_id);
            
            // Simulated assignment logic
            Log::info('IP assigned to user', [
                'ip_id' => $request->ip_id,
                'user_id' => $request->user_id,
                'user_email' => $user->email,
                'type' => $request->type,
                'assigned_by' => auth()->user()->email
            ]);

            return response()->json([
                'success' => true,
                'message' => "IP assigned to {$user->email} successfully"
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to assign IP', [
                'error' => $e->getMessage(),
                'ip_id' => $request->ip_id,
                'user_id' => $request->user_id
            ]);

            return response()->json([
                'error' => 'Failed to assign IP: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unassign IP from user
     */
    public function unassign(Request $request)
    {
        $request->validate([
            'ip_id' => 'required|integer'
        ]);

        try {
            // Simulated unassignment logic
            Log::info('IP unassigned', [
                'ip_id' => $request->ip_id,
                'unassigned_by' => auth()->user()->email
            ]);

            return response()->json([
                'success' => true,
                'message' => 'IP unassigned successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to unassign IP', [
                'error' => $e->getMessage(),
                'ip_id' => $request->ip_id
            ]);

            return response()->json([
                'error' => 'Failed to unassign IP: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add new IP address
     */
    public function add(Request $request)
    {
        $request->validate([
            'ip_address' => 'required|ip',
            'type' => 'required|in:shared,dedicated',
            'server_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500'
        ]);

        try {
            // Simulated IP addition logic
            Log::info('New IP added', [
                'ip_address' => $request->ip_address,
                'type' => $request->type,
                'server_name' => $request->server_name,
                'added_by' => auth()->user()->email
            ]);

            return response()->json([
                'success' => true,
                'message' => "IP {$request->ip_address} added successfully"
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to add IP', [
                'error' => $e->getMessage(),
                'ip_address' => $request->ip_address
            ]);

            return response()->json([
                'error' => 'Failed to add IP: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove IP address
     */
    public function remove(Request $request)
    {
        $request->validate([
            'ip_id' => 'required|integer'
        ]);

        try {
            // Simulated IP removal logic
            Log::info('IP removed', [
                'ip_id' => $request->ip_id,
                'removed_by' => auth()->user()->email
            ]);

            return response()->json([
                'success' => true,
                'message' => 'IP removed successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to remove IP', [
                'error' => $e->getMessage(),
                'ip_id' => $request->ip_id
            ]);

            return response()->json([
                'error' => 'Failed to remove IP: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get IP usage statistics
     */
    public function stats()
    {
        // Simulated statistics
        $stats = [
            'hourly_traffic' => [
                '00:00' => 150,
                '01:00' => 120,
                '02:00' => 80,
                '03:00' => 60,
                '04:00' => 45,
                '05:00' => 70,
                '06:00' => 110,
                '07:00' => 180,
                '08:00' => 220,
                '09:00' => 280,
                '10:00' => 320,
                '11:00' => 350,
            ],
            'top_domains' => [
                ['domain' => 'example.com', 'requests' => 1250],
                ['domain' => 'test.com', 'requests' => 980],
                ['domain' => 'demo.net', 'requests' => 750],
                ['domain' => 'sample.org', 'requests' => 650],
            ]
        ];

        return response()->json($stats);
    }

    /**
     * Get available users for IP assignment
     */
    public function getUsers()
    {
        $users = User::where('role', 'user')
                    ->where('status', 'active')
                    ->select('id', 'name', 'email')
                    ->orderBy('name')
                    ->get();

        return response()->json($users);
    }
}
