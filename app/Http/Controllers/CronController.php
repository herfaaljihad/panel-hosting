<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CronJob;
use App\Models\Domain;
use App\Jobs\ExecuteCronJob;
use Illuminate\Support\Facades\Auth;
use Cron\CronExpression;

class CronController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $domains = $user->domains;
        $cronJobs = CronJob::whereIn('domain_id', $domains->pluck('id'))
                          ->with('domain')
                          ->orderBy('created_at', 'desc')
                          ->paginate(15);
        
        return view('cron.index', compact('cronJobs', 'domains'));
    }

    public function show(CronJob $cronJob)
    {
        // Ensure user owns this cron job
        if ($cronJob->domain->user_id !== Auth::id()) {
            abort(403);
        }

        return response()->json($cronJob->load('domain'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'domain_id' => 'required|exists:domains,id',
            'command' => 'required|string|max:1000',
            'schedule' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean'
        ]);

        // Validate cron expression
        try {
            $cron = new CronExpression($request->schedule);
        } catch (\Exception $e) {
            return back()->withErrors(['schedule' => 'Invalid cron expression format.']);
        }

        // Ensure user owns the domain
        $domain = Domain::findOrFail($request->domain_id);
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        $cronJob = new CronJob();
        $cronJob->domain_id = $request->domain_id;
        $cronJob->command = $request->command;
        $cronJob->schedule = $request->schedule;
        $cronJob->description = $request->description;
        $cronJob->is_active = $request->boolean('is_active', true);
        $cronJob->save();

        return redirect()->route('cron.index')->with('success', 'Cron job created successfully.');
    }

    public function update(Request $request, CronJob $cronJob)
    {
        // Ensure user owns this cron job
        if ($cronJob->domain->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'command' => 'required|string|max:1000',
            'schedule' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean'
        ]);

        // Validate cron expression
        try {
            $cron = new CronExpression($request->schedule);
        } catch (\Exception $e) {
            return back()->withErrors(['schedule' => 'Invalid cron expression format.']);
        }

        $cronJob->command = $request->command;
        $cronJob->schedule = $request->schedule;
        $cronJob->description = $request->description;
        $cronJob->is_active = $request->boolean('is_active');
        $cronJob->save();

        return redirect()->route('cron.index')->with('success', 'Cron job updated successfully.');
    }

    public function destroy(CronJob $cronJob)
    {
        // Ensure user owns this cron job
        if ($cronJob->domain->user_id !== Auth::id()) {
            abort(403);
        }

        $cronJob->delete();

        return redirect()->route('cron.index')->with('success', 'Cron job deleted successfully.');
    }

    public function run(CronJob $cronJob)
    {
        // Ensure user owns this cron job
        if ($cronJob->domain->user_id !== Auth::id()) {
            abort(403);
        }

        if (!$cronJob->is_active) {
            return response()->json(['success' => false, 'message' => 'Cron job is not active.']);
        }

        // Dispatch the job to queue
        ExecuteCronJob::dispatch($cronJob);

        return response()->json(['success' => true, 'message' => 'Cron job queued for execution.']);
    }

    public function toggle(CronJob $cronJob)
    {
        // Ensure user owns this cron job
        if ($cronJob->domain->user_id !== Auth::id()) {
            abort(403);
        }

        $cronJob->is_active = !$cronJob->is_active;
        $cronJob->save();

        $status = $cronJob->is_active ? 'enabled' : 'disabled';
        return response()->json(['success' => true, 'message' => "Cron job {$status} successfully.", 'is_active' => $cronJob->is_active]);
    }

    public function logs(CronJob $cronJob)
    {
        // Ensure user owns this cron job
        if ($cronJob->domain->user_id !== Auth::id()) {
            abort(403);
        }

        // This would typically fetch logs from a log file or database
        // For now, return example logs
        $logs = [
            ['timestamp' => now()->subMinutes(30), 'status' => 'success', 'output' => 'Job completed successfully'],
            ['timestamp' => now()->subHours(1), 'status' => 'error', 'output' => 'Command not found'],
            ['timestamp' => now()->subHours(2), 'status' => 'success', 'output' => 'Job completed successfully'],
        ];

        return response()->json(['logs' => $logs]);
    }
}
