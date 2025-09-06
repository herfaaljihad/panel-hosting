@extends('layouts.panel')

@section('title', 'Available Applications')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-store me-2"></i>Application Store</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('auto-installer.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Installations
        </a>
    </div>
</div>

<!-- Search and Filter -->
<div class="card shadow mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('auto-installer.apps') }}">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search applications..." value="{{ $search }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <select name="category" class="form-control">
                            <option value="all">All Categories</option>
                            @foreach($categories as $key => $name)
                                <option value="{{ $key }}" {{ $category === $key ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Applications Grid -->
<div class="row">
    @forelse($availableApps as $app)
    <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
        <div class="card h-100 shadow border-left-primary">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <i class="{{ $app['icon'] }} fa-3x text-primary"></i>
                    </div>
                    <div class="col">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="card-title mb-1">{{ $app['name'] }}</h5>
                                <small class="text-muted">{{ $categories[$app['category']] ?? $app['category'] }}</small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-primary">v{{ $app['version'] }}</span>
                                @if($installations->where('app_name', $app['name'])->count() > 0)
                                    <span class="badge bg-success">Installed</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                
                <p class="card-text mt-3">{{ Str::limit($app['description'], 120) }}</p>
                
                <div class="row small text-muted mb-3">
                    <div class="col-6">
                        <i class="fas fa-download me-1"></i>{{ $app['size'] }}
                    </div>
                    <div class="col-6">
                        <i class="fab fa-php me-1"></i>PHP {{ $app['min_php_version'] }}+
                    </div>
                    <div class="col-6">
                        <i class="fas fa-star me-1"></i>{{ $app['popularity'] }}% Popular
                    </div>
                    <div class="col-6">
                        @if($app['requires_database'])
                            <i class="fas fa-database me-1"></i>Database Required
                        @else
                            <i class="fas fa-check me-1"></i>No Database
                        @endif
                    </div>
                </div>

                <!-- Features -->
                @if(isset($app['features']) && count($app['features']) > 0)
                <div class="mb-3">
                    <small class="text-muted d-block mb-1">Key Features:</small>
                    <ul class="list-unstyled small">
                        @foreach(array_slice($app['features'], 0, 3) as $feature)
                        <li><i class="fas fa-check text-success me-1"></i>{{ $feature }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
            
            <div class="card-footer bg-transparent">
                <div class="d-flex justify-content-between">
                    <a href="{{ $app['demo_url'] }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-external-link-alt me-1"></i>Demo
                    </a>
                    <a href="{{ route('auto-installer.show', $app['slug']) }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-download me-1"></i>Install
                    </a>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="card shadow">
            <div class="card-body text-center py-5">
                <i class="fas fa-search fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">No Applications Found</h5>
                <p class="text-muted">Try adjusting your search criteria</p>
                <a href="{{ route('auto-installer.apps') }}" class="btn btn-primary">
                    <i class="fas fa-refresh me-2"></i>Reset Filters
                </a>
            </div>
        </div>
    </div>
    @endforelse
</div>

<!-- Category Shortcuts -->
<div class="card shadow mt-4">
    <div class="card-header">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-tags me-2"></i>Browse by Category
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            @foreach($categories as $key => $name)
            <div class="col-md-3 col-sm-6 mb-2">
                <a href="{{ route('auto-installer.apps', ['category' => $key]) }}" 
                   class="btn btn-outline-primary w-100 {{ $category === $key ? 'active' : '' }}">
                    {{ $name }}
                    <span class="badge bg-secondary ms-2">
                        {{ $availableApps->where('category', $key)->count() }}
                    </span>
                </a>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
