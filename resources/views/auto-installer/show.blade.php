@extends('layouts.panel')

@section('title', 'Install ' . $app['name'])

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="{{ $app['icon'] }} me-2"></i>Install {{ $app['name'] }}
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('auto-installer.apps') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Apps
        </a>
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
        @if(session('error'))
            {{ session('error') }}
        @else
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row">
    <!-- Application Info -->
    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-body text-center">
                <i class="{{ $app['icon'] }} fa-5x text-primary mb-3"></i>
                <h4>{{ $app['name'] }}</h4>
                <p class="text-muted">{{ $app['description'] }}</p>
                
                <div class="row text-center mb-3">
                    <div class="col-6">
                        <small class="text-muted d-block">Version</small>
                        <strong>{{ $app['version'] }}</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Size</small>
                        <strong>{{ $app['size'] }}</strong>
                    </div>
                </div>

                <div class="row text-center mb-3">
                    <div class="col-6">
                        <small class="text-muted d-block">Min PHP</small>
                        <strong>{{ $app['min_php_version'] }}+</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Popularity</small>
                        <strong>{{ $app['popularity'] }}%</strong>
                    </div>
                </div>

                <a href="{{ $app['demo_url'] }}" target="_blank" class="btn btn-outline-primary w-100 mb-2">
                    <i class="fas fa-external-link-alt me-2"></i>View Demo
                </a>
            </div>
        </div>

        <!-- Features -->
        @if(isset($app['features']) && count($app['features']) > 0)
        <div class="card shadow mb-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Key Features</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    @foreach($app['features'] as $feature)
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>{{ $feature }}
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        <!-- Existing Installations -->
        @if($installations->count() > 0)
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Your Installations</h6>
            </div>
            <div class="card-body">
                @foreach($installations as $installation)
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <strong>{{ $installation->domain->name }}</strong>
                        <br><small class="text-muted">{{ $installation->installation_path }}</small>
                    </div>
                    <div>
                        {!! $installation->status_badge !!}
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <!-- Installation Form -->
    <div class="col-lg-8">
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-cog me-2"></i>Installation Settings
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('auto-installer.install') }}">
                    @csrf
                    <input type="hidden" name="app_slug" value="{{ $app['slug'] }}">

                    <!-- Domain Selection -->
                    <div class="form-group mb-3">
                        <label for="domain_id" class="form-label">
                            <i class="fas fa-globe me-1"></i>Domain <span class="text-danger">*</span>
                        </label>
                        <select class="form-control" id="domain_id" name="domain_id" required>
                            <option value="">Select domain...</option>
                            @foreach($userDomains as $domain)
                                <option value="{{ $domain->id }}" {{ old('domain_id') == $domain->id ? 'selected' : '' }}>
                                    {{ $domain->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Choose the domain where you want to install {{ $app['name'] }}</small>
                    </div>

                    <!-- Installation Path -->
                    <div class="form-group mb-3">
                        <label for="installation_path" class="form-label">
                            <i class="fas fa-folder me-1"></i>Installation Path <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">/</span>
                            <input type="text" class="form-control" id="installation_path" name="installation_path" 
                                   value="{{ old('installation_path', '') }}" placeholder="Leave empty for root">
                        </div>
                        <small class="form-text text-muted">
                            Path where the application will be installed (e.g., "blog" for domain.com/blog, empty for root)
                        </small>
                    </div>

                    <!-- Admin Settings -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="admin_username" class="form-label">
                                    <i class="fas fa-user me-1"></i>Admin Username <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="admin_username" name="admin_username" 
                                       value="{{ old('admin_username') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="admin_email" class="form-label">
                                    <i class="fas fa-envelope me-1"></i>Admin Email <span class="text-danger">*</span>
                                </label>
                                <input type="email" class="form-control" id="admin_email" name="admin_email" 
                                       value="{{ old('admin_email', Auth::user()->email) }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="admin_password" class="form-label">
                            <i class="fas fa-lock me-1"></i>Admin Password <span class="text-danger">*</span>
                        </label>
                        <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                        <small class="form-text text-muted">Minimum 8 characters</small>
                    </div>

                    <!-- Database Settings -->
                    @if($app['requires_database'])
                    <div class="form-group mb-3">
                        <label for="database_name" class="form-label">
                            <i class="fas fa-database me-1"></i>Database Name
                        </label>
                        <input type="text" class="form-control" id="database_name" name="database_name" 
                               value="{{ old('database_name') }}" placeholder="Auto-generated if empty">
                        <small class="form-text text-muted">Leave empty to auto-generate database name</small>
                    </div>
                    @endif

                    <!-- Options -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-secondary">Additional Options</h6>
                        </div>
                        <div class="card-body">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="auto_update" name="auto_update" value="1" 
                                       {{ old('auto_update') ? 'checked' : '' }}>
                                <label class="form-check-label" for="auto_update">
                                    <i class="fas fa-sync me-1"></i>Enable automatic updates
                                </label>
                            </div>
                            
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="backup_enabled" name="backup_enabled" value="1" 
                                       {{ old('backup_enabled', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="backup_enabled">
                                    <i class="fas fa-download me-1"></i>Enable automatic backups
                                </label>
                            </div>
                            
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="ssl_enabled" name="ssl_enabled" value="1" 
                                       {{ old('ssl_enabled', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="ssl_enabled">
                                    <i class="fas fa-lock me-1"></i>Enable SSL certificate
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Installation Warning -->
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Important:</strong> The installation process may take several minutes. 
                        You will be notified when the installation is complete.
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('auto-installer.apps') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-download me-2"></i>Install {{ $app['name'] }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
