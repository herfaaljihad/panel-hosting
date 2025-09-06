@extends('layouts.panel')

@section('title', 'Admin Dashboard - Panel Hosting')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2 text-danger">
        <i class="fas fa-user-shield me-2"></i>Admin Dashboard
    </h1>
    <div>
        <a href="{{ route('admin.users') }}" class="btn btn-primary me-2">
            <i class="fas fa-users me-1"></i>Kelola Users
        </a>
        <a href="{{ route('admin.settings') }}" class="btn btn-secondary">
            <i class="fas fa-cog me-1"></i>Settings
        </a>
    </div>
</div>

<!-- Global Statistics -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card border-primary">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Users</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_users'] }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card domains">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Domains</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_domains'] }}</div>
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
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Databases</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_databases'] }}</div>
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
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Emails</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_emails'] }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-envelope fa-2x text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">User Terbaru</h5>
            </div>
            <div class="card-body">
                @if($recentUsers->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Bergabung</th>
                                    <th>Resource</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentUsers as $user)
                                <tr>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        <span class="badge {{ $user->isAdmin() ? 'bg-danger' : 'bg-primary' }}">
                                            {{ ucfirst($user->role) }}
                                        </span>
                                    </td>
                                    <td>{{ $user->created_at->diffForHumans() }}</td>
                                    <td>
                                        <small>
                                            D: {{ $user->domains->count() }} | 
                                            DB: {{ $user->databases->count() }} | 
                                            E: {{ $user->emailAccounts->count() }}
                                        </small>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="{{ route('admin.users') }}" class="btn btn-primary">
                            <i class="fas fa-users me-1"></i>Lihat Semua Users
                        </a>
                    </div>
                @else
                    <div class="text-center py-3">
                        <i class="fas fa-users fa-2x text-muted mb-2"></i>
                        <p class="text-muted">Belum ada user terdaftar</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.users') }}" class="btn btn-outline-primary">
                        <i class="fas fa-users me-2"></i>Kelola Users
                    </a>
                    <a href="{{ route('admin.settings') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-cog me-2"></i>Pengaturan Sistem
                    </a>
                    <hr>
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-info">
                        <i class="fas fa-tachometer-alt me-2"></i>Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h5 class="card-title mb-0">System Info</h5>
            </div>
            <div class="card-body">
                <small class="text-muted">
                    <div class="mb-2">
                        <strong>Laravel:</strong> {{ app()->version() }}
                    </div>
                    <div class="mb-2">
                        <strong>PHP:</strong> {{ phpversion() }}
                    </div>
                    <div class="mb-2">
                        <strong>Environment:</strong> {{ app()->environment() }}
                    </div>
                    <div>
                        <strong>Debug Mode:</strong> {{ config('app.debug') ? 'On' : 'Off' }}
                    </div>
                </small>
            </div>
        </div>
    </div>
</div>
@endsection
