@extends('layouts.panel')

@section('title', 'Installation Logs - ' . $installation->app_name)

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="{{ $installation->app_icon }} me-2"></i>{{ $installation->app_name }} - Installation Logs
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('auto-installer.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Installations
        </a>
    </div>
</div>

<!-- Installation Info -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Installation Details</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td><strong>Application:</strong></td>
                        <td>{{ $installation->app_name }} v{{ $installation->app_version }}</td>
                    </tr>
                    <tr>
                        <td><strong>Domain:</strong></td>
                        <td>{{ $installation->domain->name }}</td>
                    </tr>
                    <tr>
                        <td><strong>Path:</strong></td>
                        <td>{{ $installation->installation_path ?: '/' }}</td>
                    </tr>
                    <tr>
                        <td><strong>URL:</strong></td>
                        <td>
                            <a href="https://{{ $installation->app_url }}" target="_blank">
                                {{ $installation->app_url }}
                                <i class="fas fa-external-link-alt ms-1"></i>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td>{!! $installation->status_badge !!}</td>
                    </tr>
                    <tr>
                        <td><strong>Installed:</strong></td>
                        <td>{{ $installation->installed_at?->format('M d, Y H:i:s') ?? 'N/A' }}</td>
                    </tr>
                    @if($installation->last_updated_at)
                    <tr>
                        <td><strong>Last Updated:</strong></td>
                        <td>{{ $installation->last_updated_at->format('M d, Y H:i:s') }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-cog me-2"></i>Configuration</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td><strong>Admin Username:</strong></td>
                        <td>{{ $installation->admin_username }}</td>
                    </tr>
                    <tr>
                        <td><strong>Admin Email:</strong></td>
                        <td>{{ $installation->admin_email }}</td>
                    </tr>
                    @if($installation->database_name)
                    <tr>
                        <td><strong>Database:</strong></td>
                        <td>{{ $installation->database_name }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td><strong>Auto Update:</strong></td>
                        <td>
                            @if($installation->auto_update)
                                <span class="badge bg-success">Enabled</span>
                            @else
                                <span class="badge bg-secondary">Disabled</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Auto Backup:</strong></td>
                        <td>
                            @if($installation->backup_enabled)
                                <span class="badge bg-success">Enabled</span>
                            @else
                                <span class="badge bg-secondary">Disabled</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td><strong>SSL:</strong></td>
                        <td>
                            @if($installation->ssl_enabled)
                                <span class="badge bg-success">Enabled</span>
                            @else
                                <span class="badge bg-secondary">Disabled</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Installation Logs -->
<div class="card shadow">
    <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-file-alt me-2"></i>Installation Logs</h6>
    </div>
    <div class="card-body">
        @if($installation->installation_log)
            <div class="bg-dark text-light p-3 rounded" style="font-family: 'Courier New', monospace; font-size: 12px;">
                <pre class="mb-0">{{ $installation->installation_log }}</pre>
            </div>
        @else
            <div class="text-center py-4">
                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No Logs Available</h5>
                <p class="text-muted">Installation logs will appear here once the process starts.</p>
            </div>
        @endif
    </div>
</div>

<!-- Actions -->
<div class="card shadow mt-4">
    <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-tools me-2"></i>Actions</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                @if($installation->status === 'installed' && $installation->isUpdateAvailable())
                <form method="POST" action="{{ route('auto-installer.update', $installation) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-warning w-100 mb-2" 
                            onclick="return confirm('Update {{ $installation->app_name }}?')">
                        <i class="fas fa-arrow-up me-2"></i>Update Application
                    </button>
                </form>
                @endif
            </div>
            
            <div class="col-md-3">
                @if($installation->backup_enabled)
                <form method="POST" action="{{ route('auto-installer.backup', $installation) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success w-100 mb-2">
                        <i class="fas fa-download me-2"></i>Create Backup
                    </button>
                </form>
                @endif
            </div>
            
            <div class="col-md-3">
                <a href="https://{{ $installation->app_url }}" target="_blank" class="btn btn-info w-100 mb-2">
                    <i class="fas fa-external-link-alt me-2"></i>Open Application
                </a>
            </div>
            
            <div class="col-md-3">
                <form method="POST" action="{{ route('auto-installer.uninstall', $installation) }}" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger w-100 mb-2" 
                            onclick="return confirm('Uninstall {{ $installation->app_name }}? This action cannot be undone!')">
                        <i class="fas fa-trash me-2"></i>Uninstall
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@if($installation->status === 'installing' || $installation->status === 'updating')
<script>
// Auto-refresh page every 10 seconds if installation is in progress
setTimeout(function() {
    location.reload();
}, 10000);
</script>
@endif

@endsection
