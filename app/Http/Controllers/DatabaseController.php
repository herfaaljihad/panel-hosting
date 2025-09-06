<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Database;
use Illuminate\Support\Facades\Auth;

class DatabaseController extends Controller
{
    public function index()
    {
        $databases = Auth::user()->databases()->orderBy('created_at', 'desc')->get();
        return view('databases.index', compact('databases'));
    }

    public function create()
    {
        return view('databases.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|alpha_dash',
        ]);

        Auth::user()->databases()->create([
            'name' => $request->name,
        ]);

        return redirect()->route('databases.index')->with('success', 'Database berhasil dibuat!');
    }

    public function destroy(Database $database)
    {
        // Ensure user owns this database
        if ($database->user_id !== Auth::id()) {
            abort(403);
        }

        $database->delete();

        return redirect()->route('databases.index')->with('success', 'Database berhasil dihapus!');
    }
}
