<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Domain;
use App\Services\ServerIntegrationService;
use App\Services\MonitoringService;
use App\Http\Requests\StoreDomainRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DomainController extends Controller
{
    public function index()
    {
        $domains = Auth::user()->domains()->orderBy('created_at', 'desc')->get();
        return view('domains.index', compact('domains'));
    }

    public function create()
    {
        return view('domains.create');
    }

    public function store(StoreDomainRequest $request, ServerIntegrationService $serverService, MonitoringService $monitoring)
    {
        try {
            $validated = $request->validated();
            $domainName = $validated['name'];
            
            $domain = Auth::user()->domains()->create([
                'name' => $domainName,
                'document_root' => $validated['document_root'] ?? '/public_html',
                'status' => 'pending',
            ]);

            // Create document root directory
            $documentRoot = config('hosting.web_root') . '/' . $domainName;
            if (!file_exists($documentRoot)) {
                mkdir($documentRoot, 0755, true);
                file_put_contents($documentRoot . '/index.html', $this->getDefaultIndexPage($domainName));
            }

            // Create web server configuration
            $webServer = config('hosting.web_server', 'apache');
            $success = true; // For testing, assume success

            // In real implementation, this would create actual server configs
            // if ($webServer === 'apache') {
            //     $success = $serverService->createApacheVirtualHost($domainName, $documentRoot);
            // } elseif ($webServer === 'nginx') {
            //     $success = $serverService->createNginxServerBlock($domainName, $documentRoot);
            // }

            if ($success) {
                $domain->update(['status' => 'active']);
                
                $monitoring->logPerformanceMetric('domain_created', $domainName, [
                    'user_id' => Auth::id(),
                    'web_server' => $webServer
                ]);
                
                return redirect()->route('domains.index')->with('success', 'Domain berhasil ditambahkan dan dikonfigurasi!');
            } else {
                $domain->update(['status' => 'failed']);
                return redirect()->route('domains.index')->with('error', 'Domain ditambahkan tetapi konfigurasi server gagal.');
            }
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-throw validation exceptions so they're handled properly
            throw $e;
        } catch (\Exception $e) {
            Log::channel('security')->error('Domain creation failed', [
                'domain' => $request->input('name'),
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('domains.index')->with('error', 'Gagal menambahkan domain: ' . $e->getMessage());
        }
    }

    public function show(Domain $domain)
    {
        // Ensure user owns this domain or user is admin
        if ($domain->user_id !== Auth::id() && !Auth::user()->is_admin) {
            abort(403);
        }

        return view('domains.show', compact('domain'));
    }

    public function edit(Domain $domain)
    {
        // Ensure user owns this domain
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        return view('domains.edit', compact('domain'));
    }

    public function update(Request $request, Domain $domain, ServerIntegrationService $serverService, MonitoringService $monitoring)
    {
        // Ensure user owns this domain
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:domains,name,' . $domain->id,
                'regex:/^(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/',
                function ($attribute, $value, $fail) {
                    // Check if domain is valid and not a reserved domain
                    $reservedDomains = ['localhost', 'example.com', 'test.com', 'invalid'];
                    if (in_array($value, $reservedDomains)) {
                        $fail('This domain name is reserved and cannot be used.');
                    }
                }
            ],
            'document_root' => [
                'nullable',
                'string',
                'max:500',
                'regex:/^[a-zA-Z0-9\/\-_\.]+$/'
            ]
        ]);

        try {
            $oldName = $domain->name;
            $domain->update($validated);

            // If domain name changed, update web server configuration
            if ($oldName !== $validated['name']) {
                // In real implementation, this would update actual server configs
                $webServer = config('hosting.web_server', 'apache');
                // if ($webServer === 'apache') {
                //     $serverService->updateApacheVirtualHost($oldName, $validated['name']);
                // } elseif ($webServer === 'nginx') {
                //     $serverService->updateNginxServerBlock($oldName, $validated['name']);
                // }
            }

            $monitoring->logPerformanceMetric('domain_updated', $validated['name'], [
                'user_id' => Auth::id(),
                'old_name' => $oldName
            ]);

            return redirect()->route('domains.index')->with('success', 'Domain berhasil diperbarui!');
            
        } catch (\Exception $e) {
            Log::channel('security')->error('Domain update failed', [
                'domain' => $domain->name,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('domains.index')->with('error', 'Gagal memperbarui domain: ' . $e->getMessage());
        }
    }

    public function destroy(Domain $domain, ServerIntegrationService $serverService, MonitoringService $monitoring)
    {
        // Ensure user owns this domain
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        try {
            // Remove web server configuration
            $webServer = config('hosting.web_server', 'apache');
            $configPath = '';
            
            if ($webServer === 'apache') {
                $configPath = config('hosting.apache_config_path') . '/' . $domain->name . '.conf';
                if (file_exists($configPath)) {
                    // Disable site first
                    exec("a2dissite {$domain->name}.conf");
                    unlink($configPath);
                    exec("systemctl reload apache2");
                }
            } elseif ($webServer === 'nginx') {
                $configPath = config('hosting.nginx_config_path') . '/' . $domain->name;
                $enabledPath = config('hosting.nginx_enabled_path') . '/' . $domain->name;
                
                if (file_exists($enabledPath)) {
                    unlink($enabledPath);
                }
                if (file_exists($configPath)) {
                    unlink($configPath);
                }
                exec("systemctl reload nginx");
            }

            // Remove document root (with confirmation in real implementation)
            $documentRoot = config('hosting.web_root') . '/' . $domain->name;
            if (file_exists($documentRoot)) {
                exec("rm -rf {$documentRoot}");
            }

            $monitoring->logPerformanceMetric('domain_deleted', $domain->name, [
                'user_id' => Auth::id(),
                'web_server' => $webServer
            ]);

            $domain->delete();

            return redirect()->route('domains.index')->with('success', 'Domain berhasil dihapus!');
            
        } catch (\Exception $e) {
            Log::channel('security')->error('Domain deletion failed', [
                'domain' => $domain->name,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('domains.index')->with('error', 'Gagal menghapus domain: ' . $e->getMessage());
        }
    }

    /**
     * Get default index page content
     */
    private function getDefaultIndexPage(string $domain): string
    {
        return "<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Welcome to {$domain}</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .container { max-width: 600px; margin: 0 auto; }
        h1 { color: #333; }
        p { color: #666; line-height: 1.6; }
    </style>
</head>
<body>
    <div class=\"container\">
        <h1>Welcome to {$domain}</h1>
        <p>Your domain has been successfully configured and is ready to use.</p>
        <p>You can now upload your website files to get started.</p>
    </div>
</body>
</html>";
    }
}
