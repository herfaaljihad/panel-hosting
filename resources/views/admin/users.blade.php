@extends('layouts.panel')

@section('title', 'Kelola Users - Admin Panel')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2 text-danger">
        <i class="fas fa-users me-2"></i>Kelola Users
    </h1>
    <a href="{{ route('admin.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i>Kembali ke Admin
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Daftar Semua Users</h5>
    </div>
    <div class="card-body">
        @if($users->count() > 0)
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Resource</th>
                            <th>Bergabung</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-user-circle fa-lg me-2 text-muted"></i>
                                    <div>
                                        <div class="fw-bold">{{ $user->name }}</div>
                                        @if($user->id === auth()->id())
                                            <small class="text-muted">(Anda)</small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <span class="badge {{ $user->isAdmin() ? 'bg-danger' : 'bg-primary' }}">
                                    {{ ucfirst($user->role) }}
                                </span>
                            </td>
                            <td>
                                <small>
                                    <i class="fas fa-globe text-success me-1"></i>{{ $user->domains->count() }}
                                    <i class="fas fa-database text-warning me-1 ms-2"></i>{{ $user->databases->count() }}
                                    <i class="fas fa-envelope text-danger me-1 ms-2"></i>{{ $user->emailAccounts->count() }}
                                </small>
                            </td>
                            <td>
                                <small>{{ $user->created_at->format('d M Y') }}</small>
                                <br>
                                <small class="text-muted">{{ $user->created_at->diffForHumans() }}</small>
                            </td>
                            <td>
                                @if($user->id !== auth()->id() && !$user->isAdmin())
                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" 
                                                onclick="return confirm('Yakin ingin menghapus user {{ $user->name }}? Semua data miliknya akan ikut terhapus!')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted small">
                                        @if($user->id === auth()->id())
                                            Akun Anda
                                        @else
                                            Admin
                                        @endif
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center">
                {{ $users->links() }}
            </div>
        @else
            <div class="text-center py-4">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h5>Belum ada users</h5>
                <p class="text-muted">Sistem belum memiliki user terdaftar.</p>
            </div>
        @endif
    </div>
</div>

<!-- User Statistics -->
<div class="row mt-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <h5 class="card-title">Total Users</h5>
                <h2 class="text-primary">{{ $users->total() }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <h5 class="card-title">Admin Users</h5>
                <h2 class="text-danger">{{ $users->where('role', 'admin')->count() }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <h5 class="card-title">Regular Users</h5>
                <h2 class="text-success">{{ $users->where('role', 'user')->count() }}</h2>
            </div>
        </div>
    </div>
</div>
@endsection
