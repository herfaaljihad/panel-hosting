<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Reseller Management Controller
 * DirectAdmin-style reseller functionality
 */
class ResellerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin')->except(['dashboard', 'myUsers', 'createUser', 'storeUser']);
    }

    /**
     * Reseller Dashboard
     */
    public function dashboard()
    {
        $user = Auth::user();
        
        // Only resellers and admins can access
        if (!in_array($user->role, ['admin', 'reseller'])) {
            abort(403, 'Access denied');
        }

        $stats = [
            'total_users' => $this->getUsersCount($user),
            'active_users' => $this->getActiveUsersCount($user),
            'total_domains' => $this->getDomainsCount($user),
            'total_databases' => $this->getDatabasesCount($user),
            'disk_usage' => $this->getDiskUsage($user),
            'bandwidth_usage' => $this->getBandwidthUsage($user),
        ];

        $recentUsers = $this->getRecentUsers($user, 5);
        $recentActivities = $this->getRecentActivities($user, 10);

        return view('reseller.dashboard', compact('stats', 'recentUsers', 'recentActivities'));
    }

    /**
     * Display list of reseller's users
     */
    public function myUsers(Request $request)
    {
        $user = Auth::user();
        
        if (!in_array($user->role, ['admin', 'reseller'])) {
            abort(403, 'Access denied');
        }

        $query = User::query();
        
        if ($user->role === 'reseller') {
            $query->where('reseller_id', $user->id);
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $users = $query->with(['package', 'domains', 'databases'])
                      ->orderBy('created_at', 'desc')
                      ->paginate(15);

        return view('reseller.users.index', compact('users'));
    }

    /**
     * Show form to create new user
     */
    public function createUser()
    {
        $user = Auth::user();
        
        if (!in_array($user->role, ['admin', 'reseller'])) {
            abort(403, 'Access denied');
        }

        // Check if reseller can create more users
        if ($user->role === 'reseller' && !$this->canCreateMoreUsers($user)) {
            return redirect()->route('reseller.users')
                           ->with('error', 'You have reached your maximum user limit.');
        }

        $packages = Package::where('is_active', true)->get();
        
        return view('reseller.users.create', compact('packages'));
    }

    /**
     * Store new user created by reseller
     */
    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'package_id' => 'required|exists:packages,id',
        ]);

        $reseller = Auth::user();
        
        if (!in_array($reseller->role, ['admin', 'reseller'])) {
            abort(403, 'Access denied');
        }

        // Check reseller limits
        if ($reseller->role === 'reseller' && !$this->canCreateMoreUsers($reseller)) {
            return back()->with('error', 'You have reached your maximum user limit.');
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'user',
                'package_id' => $request->package_id,
                'reseller_id' => $reseller->role === 'reseller' ? $reseller->id : null,
                'status' => 'active',
                'email_verified_at' => now(),
            ]);

            Log::info('User created by reseller', [
                'reseller_id' => $reseller->id,
                'reseller_email' => $reseller->email,
                'new_user_id' => $user->id,
                'new_user_email' => $user->email,
            ]);

            return redirect()->route('reseller.users')
                           ->with('success', 'User created successfully!');

        } catch (\Exception $e) {
            Log::error('Failed to create user', [
                'reseller_id' => $reseller->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to create user. Please try again.');
        }
    }

    /**
     * Helper methods
     */
    private function getUsersCount(User $reseller): int
    {
        if ($reseller->role === 'admin') {
            return User::where('role', 'user')->count();
        }
        
        return User::where('reseller_id', $reseller->id)->count();
    }

    private function getActiveUsersCount(User $reseller): int
    {
        if ($reseller->role === 'admin') {
            return User::where('role', 'user')->where('status', 'active')->count();
        }
        
        return User::where('reseller_id', $reseller->id)->where('status', 'active')->count();
    }

    private function getDomainsCount(User $reseller): int
    {
        if ($reseller->role === 'admin') {
            return \App\Models\Domain::count();
        }
        
        return \App\Models\Domain::whereHas('user', function ($query) use ($reseller) {
            $query->where('reseller_id', $reseller->id);
        })->count();
    }

    private function getDatabasesCount(User $reseller): int
    {
        if ($reseller->role === 'admin') {
            return \App\Models\Database::count();
        }
        
        return \App\Models\Database::whereHas('user', function ($query) use ($reseller) {
            $query->where('reseller_id', $reseller->id);
        })->count();
    }

    private function getDiskUsage(User $reseller): array
    {
        // Simulated data - in real implementation, calculate actual disk usage
        return [
            'used' => 1250, // MB
            'total' => 5000, // MB
            'percentage' => 25
        ];
    }

    private function getBandwidthUsage(User $reseller): array
    {
        // Simulated data - in real implementation, calculate actual bandwidth usage
        return [
            'used' => 2300, // MB
            'total' => 10000, // MB
            'percentage' => 23
        ];
    }

    private function getRecentUsers(User $reseller, int $limit): \Illuminate\Database\Eloquent\Collection
    {
        $query = User::with('package');
        
        if ($reseller->role === 'reseller') {
            $query->where('reseller_id', $reseller->id);
        } else {
            $query->where('role', 'user');
        }
        
        return $query->orderBy('created_at', 'desc')->limit($limit)->get();
    }

    private function getRecentActivities(User $reseller, int $limit): array
    {
        // Simulated activities - in real implementation, get from audit logs
        return [
            [
                'action' => 'User created',
                'description' => 'New user john@example.com created',
                'timestamp' => now()->subMinutes(30),
            ],
            [
                'action' => 'Domain added',
                'description' => 'Domain example.com added by user@demo.com',
                'timestamp' => now()->subHours(2),
            ],
            [
                'action' => 'Database created',
                'description' => 'Database example_db created',
                'timestamp' => now()->subHours(4),
            ],
        ];
    }

    private function canCreateMoreUsers(User $reseller): bool
    {
        if ($reseller->role === 'admin') {
            return true;
        }
        
        $currentUserCount = $this->getUsersCount($reseller);
        $maxUsers = $reseller->reseller_max_users ?? 0;
        
        return $currentUserCount < $maxUsers;
    }
}
