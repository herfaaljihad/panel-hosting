<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plugin;
use App\Models\UpdateComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PluginController extends Controller
{
    /**
     * Display plugin list
     */
    public function index()
    {
        $plugins = Plugin::with(['updateComments' => function($query) {
            $query->whereIn('status', ['pending', 'acknowledged'])
                  ->orderBy('priority', 'desc');
        }])->get();

        $stats = [
            'total' => $plugins->count(),
            'active' => $plugins->where('status', 'active')->count(),
            'updates_available' => $plugins->where('update_available', true)->count(),
            'critical_comments' => UpdateComment::getCritical()->count()
        ];

        return view('admin.plugins.index', compact('plugins', 'stats'));
    }

    /**
     * Check for updates
     */
    public function checkUpdates()
    {
        try {
            $plugins = Plugin::all();
            $updatedCount = 0;

            foreach ($plugins as $plugin) {
                if ($plugin->checkForUpdates()) {
                    $updatedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Update check completed. {$updatedCount} plugins have updates available.",
                'updated_count' => $updatedCount
            ]);

        } catch (\Exception $e) {
            Log::error('Plugin update check failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to check for updates: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update specific plugin
     */
    public function update(Plugin $plugin)
    {
        try {
            // Simulate update process
            $plugin->update([
                'current_version' => $plugin->available_version,
                'update_available' => false,
                'last_checked' => now(),
                'last_updated' => now()
            ]);

            // Create success comment
            $plugin->updateComments()->create([
                'user_id' => auth()->id(),
                'comment_type' => \App\Models\UpdateComment::TYPE_UPDATE_SUCCESS,
                'title' => 'Plugin Updated Successfully',
                'message' => "Plugin {$plugin->name} has been updated to version {$plugin->current_version}",
                'priority' => \App\Models\UpdateComment::PRIORITY_LOW,
                'status' => \App\Models\UpdateComment::STATUS_RESOLVED,
                'auto_resolve' => true,
                'resolved_at' => now(),
                'resolved_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Plugin updated successfully',
                'new_version' => $plugin->current_version
            ]);

        } catch (\Exception $e) {
            Log::error("Plugin update failed for {$plugin->name}: " . $e->getMessage());
            
            // Create error comment
            $plugin->updateComments()->create([
                'user_id' => auth()->id(),
                'comment_type' => \App\Models\UpdateComment::TYPE_UPDATE_FAILED,
                'title' => 'Plugin Update Failed',
                'message' => "Failed to update plugin {$plugin->name}: " . $e->getMessage(),
                'priority' => \App\Models\UpdateComment::PRIORITY_HIGH,
                'status' => \App\Models\UpdateComment::STATUS_PENDING,
                'action_required' => true
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle plugin status
     */
    public function toggleStatus(Plugin $plugin)
    {
        try {
            $newStatus = $plugin->status === 'active' ? 'inactive' : 'active';
            $plugin->update(['status' => $newStatus]);

            return response()->json([
                'success' => true,
                'message' => "Plugin {$newStatus} successfully",
                'status' => $newStatus
            ]);

        } catch (\Exception $e) {
            Log::error("Plugin status toggle failed for {$plugin->name}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show plugin details
     */
    public function show(Plugin $plugin)
    {
        $plugin->load(['updateComments' => function($query) {
            $query->orderBy('priority', 'desc')->orderBy('created_at', 'desc');
        }]);

        return view('admin.plugins.show', compact('plugin'));
    }

    /**
     * Get update comments
     */
    public function getComments(Request $request)
    {
        $comments = UpdateComment::with(['plugin', 'user', 'resolver'])
                                ->when($request->status, function($query, $status) {
                                    return $query->where('status', $status);
                                })
                                ->when($request->priority, function($query, $priority) {
                                    return $query->where('priority', $priority);
                                })
                                ->orderBy('priority', 'desc')
                                ->orderBy('created_at', 'desc')
                                ->paginate(20);

        return view('admin.plugins.comments', compact('comments'));
    }

    /**
     * Resolve comment
     */
    public function resolveComment(UpdateComment $comment)
    {
        try {
            $comment->markAsResolved();

            return response()->json([
                'success' => true,
                'message' => 'Comment resolved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error("Comment resolution failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to resolve comment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dismiss comment
     */
    public function dismissComment(UpdateComment $comment)
    {
        try {
            $comment->update(['status' => UpdateComment::STATUS_DISMISSED]);

            return response()->json([
                'success' => true,
                'message' => 'Comment dismissed successfully'
            ]);

        } catch (\Exception $e) {
            Log::error("Comment dismissal failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to dismiss comment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update all plugins
     */
    public function bulkUpdate(Request $request)
    {
        try {
            $plugins = Plugin::where('update_available', true);
            
            if ($request->has('plugin_ids')) {
                $plugins = $plugins->whereIn('id', $request->plugin_ids);
            }

            $plugins = $plugins->get();
            $results = [];

            foreach ($plugins as $plugin) {
                try {
                    // Simulate update process
                    $plugin->update([
                        'current_version' => $plugin->available_version,
                        'update_available' => false,
                        'last_checked' => now(),
                        'last_updated' => now()
                    ]);

                    $results[] = [
                        'plugin' => $plugin->name,
                        'success' => true,
                        'message' => 'Updated successfully'
                    ];

                    // Create success comment
                    $plugin->updateComments()->create([
                        'user_id' => auth()->id(),
                        'comment_type' => \App\Models\UpdateComment::TYPE_UPDATE_SUCCESS,
                        'title' => 'Plugin Updated (Bulk)',
                        'message' => "Plugin {$plugin->name} updated to version {$plugin->current_version} via bulk update",
                        'priority' => \App\Models\UpdateComment::PRIORITY_LOW,
                        'status' => \App\Models\UpdateComment::STATUS_RESOLVED,
                        'auto_resolve' => true,
                        'resolved_at' => now(),
                        'resolved_by' => auth()->id()
                    ]);

                } catch (\Exception $e) {
                    $results[] = [
                        'plugin' => $plugin->name,
                        'success' => false,
                        'message' => $e->getMessage()
                    ];

                    // Create error comment
                    $plugin->updateComments()->create([
                        'user_id' => auth()->id(),
                        'comment_type' => \App\Models\UpdateComment::TYPE_UPDATE_FAILED,
                        'title' => 'Plugin Update Failed (Bulk)',
                        'message' => "Failed to update plugin {$plugin->name} during bulk update: " . $e->getMessage(),
                        'priority' => \App\Models\UpdateComment::PRIORITY_HIGH,
                        'status' => \App\Models\UpdateComment::STATUS_PENDING,
                        'action_required' => true
                    ]);
                }
            }

            $successCount = collect($results)->where('success', true)->count();
            $totalCount = count($results);

            return response()->json([
                'success' => true,
                'message' => "Bulk update completed. {$successCount}/{$totalCount} plugins updated successfully.",
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk plugin update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Bulk update failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
