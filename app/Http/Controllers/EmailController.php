<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmailAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class EmailController extends Controller
{
    public function index()
    {
        $emails = Auth::user()->emailAccounts()->orderBy('created_at', 'desc')->get();
        return view('emails.index', compact('emails'));
    }

    public function create()
    {
        return view('emails.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:email_accounts,email',
            'password' => 'required|string|min:6',
        ]);

        Auth::user()->emailAccounts()->create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('emails.index')->with('success', 'Email account berhasil dibuat!');
    }

    public function destroy(EmailAccount $email)
    {
        // Ensure user owns this email account
        if ($email->user_id !== Auth::id()) {
            abort(403);
        }

        $email->delete();

        return redirect()->route('emails.index')->with('success', 'Email account berhasil dihapus!');
    }
}
