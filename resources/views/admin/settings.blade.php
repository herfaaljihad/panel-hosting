@extends('layouts.panel')

@section('title', 'Settings - Admin Panel')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2 text-danger">
        <i class="fas fa-cog me-2"></i>System Settings
    </h1>
    <a href="{{ route('admin.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i>Kembali ke Admin
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Konfigurasi Sistem</h5>
            </div>
            <div class="card-body">
                <form>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nama Aplikasi</label>
                                <input type="text" class="form-control" value="{{ config('app.name') }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Environment</label>
                                <input type="text" class="form-control" value="{{ app()->environment() }}" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">URL Aplikasi</label>
                                <input type="text" class="form-control" value="{{ config('app.url') }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Timezone</label>
                                <input type="text" class="form-control" value="{{ config('app.timezone') }}" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Info:</strong> Konfigurasi sistem saat ini dalam mode read-only. 
                        Untuk mengubah pengaturan, edit file <code>.env</code> di root aplikasi.
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Cache & Performance</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <i class="fas fa-rocket fa-2x text-primary mb-2"></i>
                                <h6>Clear Cache</h6>
                                <p class="small text-muted">Bersihkan cache aplikasi</p>
                                <button class="btn btn-primary btn-sm" onclick="alert('Fitur ini perlu implementasi artisan command')">
                                    Clear Cache
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <i class="fas fa-sync fa-2x text-success mb-2"></i>
                                <h6>Optimize</h6>
                                <p class="small text-muted">Optimasi konfigurasi</p>
                                <button class="btn btn-success btn-sm" onclick="alert('Fitur ini perlu implementasi artisan command')">
                                    Optimize
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">System Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td><strong>Laravel Version:</strong></td>
                        <td>{{ app()->version() }}</td>
                    </tr>
                    <tr>
                        <td><strong>PHP Version:</strong></td>
                        <td>{{ phpversion() }}</td>
                    </tr>
                    <tr>
                        <td><strong>Server Software:</strong></td>
                        <td>{{ $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Database:</strong></td>
                        <td>{{ config('database.default') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Cache Driver:</strong></td>
                        <td>{{ config('cache.default') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Session Driver:</strong></td>
                        <td>{{ config('session.driver') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Queue Driver:</strong></td>
                        <td>{{ config('queue.default') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Mail Driver:</strong></td>
                        <td>{{ config('mail.default') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Disk Usage</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label small">Storage (Local)</label>
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" style="width: 25%">25%</div>
                    </div>
                    <small class="text-muted">2.5 GB / 10 GB</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label small">Database</label>
                    <div class="progress">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: 15%">15%</div>
                    </div>
                    <small class="text-muted">150 MB / 1 GB</small>
                </div>
                
                <div class="mb-0">
                    <label class="form-label small">Logs</label>
                    <div class="progress">
                        <div class="progress-bar bg-info" role="progressbar" style="width: 5%">5%</div>
                    </div>
                    <small class="text-muted">50 MB / 1 GB</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
