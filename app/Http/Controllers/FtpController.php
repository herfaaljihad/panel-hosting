<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FtpAccount;
use App\Models\Domain;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class FtpController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $domains = $user->domains;
        $ftpAccounts = FtpAccount::whereIn('domain_id', $domains->pluck('id'))
                                ->with('domain')
                                ->orderBy('created_at', 'desc')
                                ->paginate(15);
        
        return view('ftp.index', compact('ftpAccounts', 'domains'));
    }

    public function show(FtpAccount $ftpAccount)
    {
        // Ensure user owns this FTP account
        if ($ftpAccount->domain->user_id !== Auth::id()) {
            abort(403);
        }

        return response()->json($ftpAccount->load('domain'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'domain_id' => 'required|exists:domains,id',
            'username' => 'required|string|max:255|regex:/^[a-zA-Z0-9_-]+$/',
            'password' => 'required|string|min:8',
            'home_directory' => 'required|string',
            'quota_mb' => 'nullable|integer|min:1',
            'bandwidth_limit_mb' => 'nullable|integer|min:1',
            'is_active' => 'boolean'
        ]);

        // Ensure user owns the domain
        $domain = Domain::findOrFail($request->domain_id);
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        // Check if username already exists for this domain
        $existingAccount = FtpAccount::where('domain_id', $request->domain_id)
                                   ->where('username', $request->username)
                                   ->first();
        
        if ($existingAccount) {
            return redirect()->back()->withErrors(['username' => 'FTP username already exists for this domain.']);
        }

        $ftpAccount = new FtpAccount();
        $ftpAccount->domain_id = $request->domain_id;
        $ftpAccount->username = $request->username;
        $ftpAccount->password = Hash::make($request->password);
        $ftpAccount->home_directory = $request->home_directory;
        $ftpAccount->quota_mb = $request->quota_mb;
        $ftpAccount->bandwidth_limit_mb = $request->bandwidth_limit_mb;
        $ftpAccount->is_active = $request->boolean('is_active', true);
        $ftpAccount->save();

        return redirect()->route('ftp.index')->with('success', 'FTP account created successfully.');
    }

    public function update(Request $request, FtpAccount $ftpAccount)
    {
        // Ensure user owns this FTP account
        if ($ftpAccount->domain->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'domain_id' => 'required|exists:domains,id',
            'home_directory' => 'required|string',
            'quota_mb' => 'nullable|integer|min:1',
            'bandwidth_limit_mb' => 'nullable|integer|min:1',
            'is_active' => 'boolean'
        ]);

        $ftpAccount->home_directory = $request->home_directory;
        $ftpAccount->quota_mb = $request->quota_mb;
        $ftpAccount->bandwidth_limit_mb = $request->bandwidth_limit_mb;
        $ftpAccount->is_active = $request->boolean('is_active');
        $ftpAccount->save();

        return redirect()->route('ftp.index')->with('success', 'FTP account updated successfully.');
    }

    public function destroy(FtpAccount $ftpAccount)
    {
        // Ensure user owns this FTP account
        if ($ftpAccount->domain->user_id !== Auth::id()) {
            abort(403);
        }

        $ftpAccount->delete();

        return redirect()->route('ftp.index')->with('success', 'FTP account deleted successfully.');
    }

    public function resetPassword(Request $request, FtpAccount $ftpAccount)
    {
        // Ensure user owns this FTP account
        if ($ftpAccount->domain->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'password' => 'required|string|min:8'
        ]);

        $ftpAccount->password = Hash::make($request->password);
        $ftpAccount->save();

        return response()->json(['success' => true, 'message' => 'Password updated successfully.']);
    }
}
