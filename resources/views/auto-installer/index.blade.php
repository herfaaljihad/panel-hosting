@extends('layouts.panel')

@section('title', 'Auto Installer')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-magic me-2"></i>Auto Installer</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="{{ route('auto-installer.apps') }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Install New App
            </a>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error') || $errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        {{ session('error') ?: $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Installations
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $installations->total() }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-download fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Active Apps
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $installations->where('status', 'installed')->count() }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Updates Available
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $installations->filter(function($app) { return $app->isUpdateAvailable(); })->count() }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-arrow-up fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Available Apps
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ count($availableApps) }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-cube fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Installed Applications -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-list me-2"></i>Your Installed Applications
        </h6>
    </div>
    <div class="card-body">
        @if($installations->count() > 0)
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Application</th>
                            <th>Domain</th>
                            <th>Version</th>
                            <th>Status</th>
                            <th>Installed</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($installations as $installation)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="{{ $installation->app_icon }} fa-2x me-3 text-primary"></i>
                                    <div>
                                        <div class="fw-bold">{{ $installation->app_name }}</div>
                                        <small class="text-muted">{{ $installation->installation_path }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a href="https://{{ $installation->app_url }}" target="_blank" class="text-decoration-none">
                                    {{ $installation->domain->name }}
                                    <i class="fas fa-external-link-alt ms-1"></i>
                                </a>
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ $installation->app_version }}</span>
                                @if($installation->isUpdateAvailable())
                                    <span class="badge bg-warning ms-1">Update Available</span>
                                @endif
                            </td>
                            <td>{!! $installation->status_badge !!}</td>
                            <td>{{ $installation->installed_at?->format('M d, Y') }}</td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('auto-installer.logs', $installation) }}" class="btn btn-sm btn-outline-info">
                                        <i class="fas fa-file-alt"></i>
                                    </a>
                                    @if($installation->isUpdateAvailable())
                                        <form method="POST" action="{{ route('auto-installer.update', $installation) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-warning" 
                                                    onclick="return confirm('Update {{ $installation->app_name }}?')">
                                                <i class="fas fa-arrow-up"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @if($installation->backup_enabled)
                                        <form method="POST" action="{{ route('auto-installer.backup', $installation) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="fas fa-download"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('auto-installer.uninstall', $installation) }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" 
                                                onclick="return confirm('Uninstall {{ $installation->app_name }}? This action cannot be undone!')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            {{ $installations->links() }}
        @else
            <div class="text-center py-5">
                <i class="fas fa-cube fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">No Applications Installed</h5>
                <p class="text-muted mb-4">Get started by installing your first application</p>
                <a href="{{ route('auto-installer.apps') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Browse Applications
                </a>
            </div>
        @endif
    </div>
</div>

<!-- Quick Install Popular Apps -->
<div class="card shadow">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-star me-2"></i>Popular Applications
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            @foreach($availableApps->sortByDesc('popularity')->take(6) as $app)
            <div class="col-md-4 col-lg-2 mb-3">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <i class="{{ $app['icon'] }} fa-3x text-primary mb-3"></i>
                        <h6 class="card-title">{{ $app['name'] }}</h6>
                        <p class="card-text small text-muted">{{ Str::limit($app['description'], 60) }}</p>
                        <a href="{{ route('auto-installer.show', $app['slug']) }}" class="btn btn-sm btn-primary">
                            Install
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
