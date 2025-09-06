<?php

namespace App\Http\Controllers;

use App\Models\AppInstallation;
use App\Models\Domain;
use App\Models\Database;
use App\Services\AutoInstallerService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AutoInstallerController extends Controller
{
    protected AutoInstallerService $installerService;

    public function __construct(AutoInstallerService $installerService)
    {
        $this->installerService = $installerService;
    }

    public function index(): View
    {
        $installations = AppInstallation::where('user_id', Auth::id())
            ->with(['domain'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $availableApps = collect(config('auto_installer.apps'));
        $categories = config('auto_installer.categories');

        return view('auto-installer.index', compact('installations', 'availableApps', 'categories'));
    }

    public function apps(Request $request): View
    {
        $category = $request->get('category', 'all');
        $search = $request->get('search');
        
        $availableApps = collect(config('auto_installer.apps'));
        
        if ($category !== 'all') {
            $availableApps = $availableApps->where('category', $category);
        }
        
        if ($search) {
            $availableApps = $availableApps->filter(function ($app) use ($search) {
                return stripos($app['name'], $search) !== false || 
                       stripos($app['description'], $search) !== false;
            });
        }

        $categories = config('auto_installer.categories');
        $installations = AppInstallation::where('user_id', Auth::id())->get();

        return view('auto-installer.apps', compact('availableApps', 'categories', 'category', 'search', 'installations'));
    }

    public function show(string $appSlug): View
    {
        $availableApps = collect(config('auto_installer.apps'));
        $app = $availableApps->firstWhere('slug', $appSlug);

        if (!$app) {
            abort(404, 'Application not found');
        }

        $userDomains = Domain::where('user_id', Auth::id())->get();
        $installations = AppInstallation::where('user_id', Auth::id())
            ->where('app_name', $app['name'])
            ->with(['domain'])
            ->get();

        return view('auto-installer.show', compact('app', 'userDomains', 'installations'));
    }

    public function install(Request $request): RedirectResponse
    {
        $request->validate([
            'app_slug' => 'required|string',
            'domain_id' => 'required|exists:domains,id',
            'installation_path' => 'required|string|max:255',
            'admin_username' => 'required|string|max:255',
            'admin_email' => 'required|email',
            'admin_password' => 'required|string|min:8',
            'database_name' => 'nullable|string|max:64',
            'auto_update' => 'boolean',
            'backup_enabled' => 'boolean',
            'ssl_enabled' => 'boolean'
        ]);

        $availableApps = collect(config('auto_installer.apps'));
        $app = $availableApps->firstWhere('slug', $request->app_slug);

        if (!$app) {
            return back()->withErrors(['app_slug' => 'Application not found']);
        }

        $domain = Domain::where('id', $request->domain_id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$domain) {
            return back()->withErrors(['domain_id' => 'Domain not found or not owned by you']);
        }

        // Check if app already installed on this path
        $existingInstallation = AppInstallation::where('domain_id', $domain->id)
            ->where('installation_path', $request->installation_path)
            ->first();

        if ($existingInstallation) {
            return back()->withErrors(['installation_path' => 'An application is already installed on this path']);
        }

        DB::beginTransaction();
        try {
            // Create database if needed
            $databaseName = $request->database_name ?: $this->generateDatabaseName($domain->name, $app['name']);
            $databaseUser = $databaseName;
            $databasePassword = Str::random(16);

            if ($app['requires_database']) {
                $database = Database::create([
                    'user_id' => Auth::id(),
                    'name' => $databaseName,
                    'username' => $databaseUser,
                    'password' => $databasePassword,
                    'host' => 'localhost',
                    'port' => 3306
                ]);
            }

            // Create installation record
            $installation = AppInstallation::create([
                'user_id' => Auth::id(),
                'domain_id' => $domain->id,
                'app_name' => $app['name'],
                'app_version' => $app['version'],
                'installation_path' => $request->installation_path,
                'database_name' => $databaseName ?? null,
                'database_user' => $databaseUser ?? null,
                'admin_username' => $request->admin_username,
                'admin_email' => $request->admin_email,
                'app_url' => $domain->name . '/' . trim($request->installation_path, '/'),
                'status' => 'installing',
                'auto_update' => $request->boolean('auto_update'),
                'backup_enabled' => $request->boolean('backup_enabled'),
                'ssl_enabled' => $request->boolean('ssl_enabled'),
                'installed_at' => now()
            ]);

            // Queue installation job
            $installationData = [
                'app' => $app,
                'domain' => $domain,
                'installation' => $installation,
                'admin_password' => $request->admin_password,
                'database_password' => $databasePassword ?? null
            ];

            $this->installerService->queueInstallation($installationData);

            DB::commit();

            return redirect()->route('auto-installer.index')
                ->with('success', "Installation of {$app['name']} has been queued. You will be notified when complete.");

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Installation failed: ' . $e->getMessage()]);
        }
    }

    public function uninstall(AppInstallation $installation): RedirectResponse
    {
        if ($installation->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        try {
            $this->installerService->uninstallApp($installation);
            
            return redirect()->route('auto-installer.index')
                ->with('success', "{$installation->app_name} has been uninstalled successfully.");

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Uninstallation failed: ' . $e->getMessage()]);
        }
    }

    public function update(AppInstallation $installation): RedirectResponse
    {
        if ($installation->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        if (!$installation->isUpdateAvailable()) {
            return back()->withErrors(['error' => 'No updates available']);
        }

        try {
            $this->installerService->updateApp($installation);
            
            return redirect()->route('auto-installer.index')
                ->with('success', "{$installation->app_name} update has been queued.");

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Update failed: ' . $e->getMessage()]);
        }
    }

    public function backup(AppInstallation $installation): RedirectResponse
    {
        if ($installation->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        try {
            $this->installerService->backupApp($installation);
            
            return redirect()->route('auto-installer.index')
                ->with('success', "Backup of {$installation->app_name} has been queued.");

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Backup failed: ' . $e->getMessage()]);
        }
    }

    public function logs(AppInstallation $installation): View
    {
        if ($installation->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        return view('auto-installer.logs', compact('installation'));
    }

    private function generateDatabaseName(string $domainName, string $appName): string
    {
        $prefix = str_replace(['.', '-'], '_', $domainName);
        $appPrefix = strtolower(substr($appName, 0, 3));
        $random = Str::random(4);
        
        return substr($prefix . '_' . $appPrefix . '_' . $random, 0, 64);
    }
}
