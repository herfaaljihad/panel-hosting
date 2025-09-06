<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Package;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class PackageController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        if ($user->is_admin) {
            // Admin can see all packages with user counts
            $packages = Package::withCount('users')->orderBy('sort_order')->orderBy('price')->get();
            return view('admin.packages.index', compact('packages'));
        } else {
            // Regular users see available packages
            $packages = Package::where('is_active', true)->orderBy('price')->get();
            return view('packages.index', compact('packages'));
        }
    }

    public function create()
    {
        // Only admins can create packages
        if (!Auth::user()->is_admin) {
            abort(403);
        }
        
        return view('admin.packages.create');
    }

    public function store(Request $request)
    {
        // Only admins can create packages
        if (!Auth::user()->is_admin) {
            abort(403);
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:monthly,quarterly,annually',
            'max_domains' => 'required|integer|min:1',
            'max_subdomains' => 'required|integer|min:0',
            'max_databases' => 'required|integer|min:0',
            'max_email_accounts' => 'required|integer|min:0',
            'max_ftp_accounts' => 'required|integer|min:0',
            'disk_quota_mb' => 'required|integer|min:100',
            'bandwidth_quota_mb' => 'required|integer|min:1000',
            'max_cron_jobs' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        // If this is set as default, remove default from others
        if ($request->is_default) {
            Package::where('is_default', true)->update(['is_default' => false]);
        }

        $packageData = $request->all();
        $packageData['is_active'] = $request->boolean('is_active', true);
        
        Package::create($packageData);

        return redirect()->route('admin.packages.index')->with('success', 'Package berhasil dibuat!');
    }

    public function edit(Package $package)
    {
        // Only admins can edit packages
        if (!Auth::user()->is_admin) {
            abort(403);
        }
        
        return view('admin.packages.edit', compact('package'));
    }

    public function update(Request $request, Package $package)
    {
        // Only admins can update packages
        if (!Auth::user()->is_admin) {
            abort(403);
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:monthly,quarterly,annually',
            'max_domains' => 'required|integer|min:1',
            'max_subdomains' => 'required|integer|min:0',
            'max_databases' => 'required|integer|min:0',
            'max_email_accounts' => 'required|integer|min:0',
            'max_ftp_accounts' => 'required|integer|min:0',
            'disk_quota_mb' => 'required|integer|min:100',
            'bandwidth_quota_mb' => 'required|integer|min:1000',
            'max_cron_jobs' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        // If this is set as default, remove default from others
        if ($request->is_default && !$package->is_default) {
            Package::where('is_default', true)->update(['is_default' => false]);
        }

        $packageData = $request->all();
        $packageData['is_active'] = $request->boolean('is_active', $package->is_active);
        
        $package->update($packageData);

        return redirect()->route('admin.packages.index')->with('success', 'Package berhasil diupdate!');
    }

    public function destroy(Package $package)
    {
        // Only admins can delete packages
        if (!Auth::user()->is_admin) {
            abort(403);
        }
        
        // Check if package has users
        if ($package->users()->count() > 0) {
            return redirect()->route('admin.packages.index')->with('error', 'Package tidak dapat dihapus karena masih digunakan user!');
        }

        $package->delete();

        return redirect()->route('admin.packages.index')->with('success', 'Package berhasil dihapus!');
    }

    public function assignToUser(Request $request, User $user)
    {
        // Only admins can assign packages
        if (!Auth::user()->is_admin) {
            abort(403);
        }
        
        $request->validate([
            'package_id' => 'required|exists:packages,id',
        ]);

        $user->update(['package_id' => $request->package_id]);

        return redirect()->route('admin.users')->with('success', 'Package berhasil diassign ke user!');
    }

    public function show(Package $package)
    {
        $usersCount = $package->users()->count();
        
        if (Auth::user()->is_admin) {
            return view('admin.packages.show', compact('package', 'usersCount'));
        } else {
            return view('packages.show', compact('package'));
        }
    }

    public function toggle(Package $package)
    {
        // Only admins can toggle package status
        if (!Auth::user()->is_admin) {
            abort(403);
        }

        $package->is_active = !$package->is_active;
        $package->save();

        $status = $package->is_active ? 'activated' : 'deactivated';
        return response()->json(['success' => true, 'message' => "Package {$status} successfully.", 'is_active' => $package->is_active]);
    }
}
