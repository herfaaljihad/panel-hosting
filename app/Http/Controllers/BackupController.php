<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Backup;
use App\Models\Domain;
use App\Jobs\CreateBackupJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $domains = $user->domains;
        $backups = Backup::whereIn('domain_id', $domains->pluck('id'))
                        ->with('domain')
                        ->orderBy('created_at', 'desc')
                        ->paginate(15);
        
        return view('backup.index', compact('backups', 'domains'));
    }

    public function show(Backup $backup)
    {
        // Ensure user owns this backup
        $backup->load('domain');
        if ($backup->domain->user_id !== Auth::id()) {
            abort(403);
        }

        return response()->json($backup);
    }

    public function store(Request $request)
    {
        $request->validate([
            'domain_id' => 'required|exists:domains,id',
            'backup_type' => 'required|in:files,database,full',
            'description' => 'nullable|string|max:500',
            'schedule_type' => 'nullable|in:manual,daily,weekly,monthly'
        ]);

        // Ensure user owns the domain
        $domain = Domain::findOrFail($request->domain_id);
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        $backup = new Backup();
        $backup->domain_id = $request->domain_id;
        $backup->backup_type = $request->backup_type;
        $backup->description = $request->description;
        $backup->schedule_type = $request->schedule_type ?? 'manual';
        $backup->status = 'pending';
        $backup->file_path = null; // Will be set when backup completes
        $backup->file_size = 0;
        $backup->save();

        // Dispatch backup job to queue
        CreateBackupJob::dispatch($backup);

        return redirect()->route('backup.index')->with('success', 'Backup job queued successfully. You will be notified when complete.');
    }

    public function destroy(Backup $backup)
    {
        // Ensure user owns this backup
        if ($backup->domain->user_id !== Auth::id()) {
            abort(403);
        }

        // Delete the backup file if it exists
        if ($backup->file_path && Storage::disk('local')->exists($backup->file_path)) {
            Storage::disk('local')->delete($backup->file_path);
        }

        $backup->delete();

        return redirect()->route('backup.index')->with('success', 'Backup deleted successfully.');
    }

    public function download(Backup $backup)
    {
        // Ensure user owns this backup
        if ($backup->domain->user_id !== Auth::id()) {
            abort(403);
        }

        if (!$backup->file_path || !Storage::disk('local')->exists($backup->file_path)) {
            return redirect()->back()->withErrors(['error' => 'Backup file not found.']);
        }

        return response()->download(Storage::disk('local')->path($backup->file_path), $backup->filename);
    }

    public function restore(Request $request, Backup $backup)
    {
        // Ensure user owns this backup
        if ($backup->domain->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'confirm_restore' => 'required|accepted'
        ]);

        if ($backup->status !== 'completed') {
            return response()->json(['success' => false, 'message' => 'Cannot restore incomplete backup.']);
        }

        // In a real application, you would queue a job to restore the backup
        // For now, we'll simulate it
        $backup->status = 'restoring';
        $backup->save();

        // Simulate restore process
        $this->simulateBackupRestore($backup);

        return response()->json(['success' => true, 'message' => 'Backup restoration started successfully.']);
    }

    public function create()
    {
        $user = Auth::user();
        $domains = $user->domains;
        
        return view('backup.create', compact('domains'));
    }

    private function simulateBackupCreation(Backup $backup)
    {
        // This is a simulation - in a real app, this would be a queued job
        $domain = $backup->domain;
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "backup_{$domain->name}_{$backup->backup_type}_{$timestamp}.tar.gz";
        
        $backup->filename = $filename;
        $backup->file_path = "backups/{$filename}";
        $backup->file_size = rand(1024, 1024 * 1024); // Random size between 1KB and 1MB
        $backup->status = 'completed';
        $backup->save();
    }

    private function simulateBackupRestore(Backup $backup)
    {
        // This is a simulation - in a real app, this would be a queued job
        // Update status after "restoration"
        $backup->status = 'completed';
        $backup->save();
    }
}
