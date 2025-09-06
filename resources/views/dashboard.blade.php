@extends('layouts.panel')

@section('title', 'Dashboard - Panel Hosting')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card domains">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Domain</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['domains'] }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-globe fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card databases">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Database</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['databases'] }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-database fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card emails">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Email Account</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['emails'] }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-envelope fa-2x text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card files">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Storage</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['storage'] / 1024, 2) }} KB</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-hdd fa-2x text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Menu Utama</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3 mb-3">
                        <a href="{{ route('domains.index') }}" class="text-decoration-none">
                            <div class="p-3 border rounded">
                                <i class="fas fa-globe fa-3x text-success mb-2"></i>
                                <h6>Domain</h6>
                                <small class="text-muted">Kelola domain website</small>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="{{ route('databases.index') }}" class="text-decoration-none">
                            <div class="p-3 border rounded">
                                <i class="fas fa-database fa-3x text-warning mb-2"></i>
                                <h6>Database</h6>
                                <small class="text-muted">Kelola database MySQL</small>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="{{ route('files.index') }}" class="text-decoration-none">
                            <div class="p-3 border rounded">
                                <i class="fas fa-folder fa-3x text-primary mb-2"></i>
                                <h6>File Manager</h6>
                                <small class="text-muted">Upload dan kelola file</small>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="{{ route('emails.index') }}" class="text-decoration-none">
                            <div class="p-3 border rounded">
                                <i class="fas fa-envelope fa-3x text-danger mb-2"></i>
                                <h6>Email</h6>
                                <small class="text-muted">Kelola email account</small>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Informasi Akun</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Nama:</strong> {{ Auth::user()->name }}
                </div>
                <div class="mb-3">
                    <strong>Email:</strong> {{ Auth::user()->email }}
                </div>
                <div class="mb-3">
                    <strong>Role:</strong> 
                    <span class="badge {{ Auth::user()->isAdmin() ? 'bg-danger' : 'bg-primary' }}">
                        {{ ucfirst(Auth::user()->role) }}
                    </span>
                </div>
                <div class="mb-3">
                    <strong>Bergabung:</strong> {{ Auth::user()->created_at->format('d M Y') }}
                </div>
                @if(Auth::user()->isAdmin())
                <div class="mt-3">
                    <a href="{{ route('admin.index') }}" class="btn btn-danger btn-sm">
                        <i class="fas fa-user-shield me-1"></i>Admin Panel
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
