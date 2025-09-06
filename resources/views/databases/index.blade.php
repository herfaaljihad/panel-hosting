@extends('layouts.panel')

@section('title', 'Database Management - Panel Hosting')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">Database Management</h1>
    <a href="{{ route('databases.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Buat Database
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
        <h5 class="card-title mb-0">Daftar Database Anda</h5>
    </div>
    <div class="card-body">
        @if($databases->count() > 0)
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Database Name</th>
                            <th>Tanggal Dibuat</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($databases as $database)
                        <tr>
                            <td>
                                <i class="fas fa-database text-warning me-2"></i>
                                {{ $database->name }}
                            </td>
                            <td>{{ $database->created_at->format('d M Y H:i') }}</td>
                            <td>
                                <span class="badge bg-success">Active</span>
                            </td>
                            <td>
                                <form action="{{ route('databases.destroy', $database) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" 
                                            onclick="return confirm('Yakin ingin menghapus database {{ $database->name }}? Data akan hilang permanen!')">
                                        <i class="fas fa-trash"></i> Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-4">
                <i class="fas fa-database fa-3x text-muted mb-3"></i>
                <h5>Belum ada database</h5>
                <p class="text-muted">Buat database pertama Anda untuk menyimpan data aplikasi.</p>
                <a href="{{ route('databases.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>Buat Database
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
