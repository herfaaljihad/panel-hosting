<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Domain;
use App\Models\Database;
use App\Models\EmailAccount;

class AdminController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'total_domains' => Domain::count(),
            'total_databases' => Database::count(),
            'total_emails' => EmailAccount::count(),
        ];

        $recentUsers = User::orderBy('created_at', 'desc')->take(5)->get();

        return view('admin.index', compact('stats', 'recentUsers'));
    }

    public function users()
    {
        $users = User::with(['domains', 'databases', 'emailAccounts'])
                     ->orderBy('created_at', 'desc')
                     ->paginate(15);

        return view('admin.users', compact('users'));
    }

    public function destroyUser(User $user)
    {
        // Prevent admin from deleting themselves
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users')->with('error', 'Anda tidak dapat menghapus akun sendiri!');
        }

        // Prevent deleting other admins
        if ($user->isAdmin()) {
            return redirect()->route('admin.users')->with('error', 'Tidak dapat menghapus admin lain!');
        }

        $user->delete();

        return redirect()->route('admin.users')->with('success', 'User berhasil dihapus!');
    }

    public function settings()
    {
        return view('admin.settings');
    }
}
