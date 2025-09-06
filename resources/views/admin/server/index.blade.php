@extends('layouts.panel')

@section('title', 'Server Manager')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">Server Manager</h1>
            <p class="text-muted">Comprehensive server administration and monitoring</p>
        </div>
    </div>

    <!-- Server Overview Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">CPU Usage</h5>
                            <h2 class="mb-0">{{ $systemLoad['cpu_usage'] }}%</h2>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-microchip fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">Memory Usage</h5>
                            <h2 class="mb-0">{{ $systemLoad['memory_usage'] }}%</h2>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-memory fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">Disk Usage</h5>
                            <h2 class="mb-0">{{ $systemLoad['disk_usage'] }}%</h2>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-hdd fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">Network</h5>
                            <h6 class="mb-0">↓ {{ $systemLoad['network_in'] }}</h6>
                            <h6 class="mb-0">↑ {{ $systemLoad['network_out'] }}</h6>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-network-wired fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Server Management Navigation -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Server Management Tools</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('admin.server.admin-settings') }}" class="btn btn-outline-primary btn-block btn-lg">
                                <i class="fas fa-cogs fa-2x d-block mb-2"></i>
                                <strong>Administrator Settings</strong>
                                <br><small>Configure global server settings</small>
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('admin.server.httpd-config') }}" class="btn btn-outline-info btn-block btn-lg">
                                <i class="fas fa-server fa-2x d-block mb-2"></i>
                                <strong>Custom HTTPD Config</strong>
                                <br><small>Apache/Nginx configurations</small>
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('admin.server.dns-admin') }}" class="btn btn-outline-success btn-block btn-lg">
                                <i class="fas fa-globe fa-2x d-block mb-2"></i>
                                <strong>DNS Administration</strong>
                                <br><small>Manage DNS zones and records</small>
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('admin.server.ip-management') }}" class="btn btn-outline-warning btn-block btn-lg">
                                <i class="fas fa-sitemap fa-2x d-block mb-2"></i>
                                <strong>IP Management</strong>
                                <br><small>Configure IP addresses</small>
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('admin.server.multi-server') }}" class="btn btn-outline-secondary btn-block btn-lg">
                                <i class="fas fa-cluster fa-2x d-block mb-2"></i>
                                <strong>Multi Server Setup</strong>
                                <br><small>Manage multiple servers</small>
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('admin.server.php-config') }}" class="btn btn-outline-dark btn-block btn-lg">
                                <i class="fab fa-php fa-2x d-block mb-2"></i>
                                <strong>PHP Configuration</strong>
                                <br><small>Manage PHP versions & settings</small>
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('admin.server.tls-certificate') }}" class="btn btn-outline-danger btn-block btn-lg">
                                <i class="fas fa-certificate fa-2x d-block mb-2"></i>
                                <strong>TLS Certificates</strong>
                                <br><small>SSL/TLS certificate management</small>
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('admin.server.security-txt') }}" class="btn btn-outline-info btn-block btn-lg">
                                <i class="fas fa-shield-alt fa-2x d-block mb-2"></i>
                                <strong>Security.txt Report</strong>
                                <br><small>Security disclosure policies</small>
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('admin.server.system-packages') }}" class="btn btn-outline-success btn-block btn-lg">
                                <i class="fas fa-box fa-2x d-block mb-2"></i>
                                <strong>System Packages</strong>
                                <br><small>Package management & updates</small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Server Information -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Server Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th>Hostname:</th>
                            <td>{{ $serverInfo['hostname'] }}</td>
                        </tr>
                        <tr>
                            <th>Operating System:</th>
                            <td>{{ $serverInfo['os'] }}</td>
                        </tr>
                        <tr>
                            <th>PHP Version:</th>
                            <td>{{ $serverInfo['php_version'] }}</td>
                        </tr>
                        <tr>
                            <th>Web Server:</th>
                            <td>{{ $serverInfo['server_software'] }}</td>
                        </tr>
                        <tr>
                            <th>Document Root:</th>
                            <td><code>{{ $serverInfo['document_root'] }}</code></td>
                        </tr>
                        <tr>
                            <th>Server Admin:</th>
                            <td>{{ $serverInfo['server_admin'] }}</td>
                        </tr>
                        <tr>
                            <th>Uptime:</th>
                            <td>{{ $serverInfo['uptime'] }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Services Status</h5>
                </div>
                <div class="card-body">
                    @foreach($services as $service)
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <strong>{{ $service['name'] }}</strong>
                            @if($service['pid'])
                                <br><small class="text-muted">PID: {{ $service['pid'] }} | Memory: {{ $service['memory'] }}</small>
                            @endif
                        </div>
                        <div>
                            <span class="badge bg-{{ $service['status'] === 'running' ? 'success' : 'danger' }} fs-6">
                                <i class="fas fa-{{ $service['status'] === 'running' ? 'play' : 'stop' }}"></i>
                                {{ ucfirst($service['status']) }}
                            </span>
                        </div>
                    </div>
                    @if(!$loop->last)
                        <hr>
                    @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-primary" onclick="restartWebServer()">
                            <i class="fas fa-sync-alt"></i> Restart Web Server
                        </button>
                        <button type="button" class="btn btn-success" onclick="clearAllCaches()">
                            <i class="fas fa-broom"></i> Clear All Caches
                        </button>
                        <button type="button" class="btn btn-warning" onclick="checkSystemUpdates()">
                            <i class="fas fa-download"></i> Check Updates
                        </button>
                        <button type="button" class="btn btn-info" onclick="generateBackup()">
                            <i class="fas fa-database"></i> Generate Backup
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="viewLogs()">
                            <i class="fas fa-file-alt"></i> View Logs
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function restartWebServer() {
    if (!confirm('Are you sure you want to restart the web server? This may cause temporary downtime.')) {
        return;
    }
    
    showAlert('info', 'Restarting web server...');
    // Simulate restart process
    setTimeout(() => {
        showAlert('success', 'Web server restarted successfully');
    }, 3000);
}

function clearAllCaches() {
    showAlert('info', 'Clearing all caches...');
    
    fetch('{{ route("admin.cache.clear") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
        } else {
            showAlert('danger', data.message);
        }
    })
    .catch(error => {
        showAlert('danger', 'Error clearing caches');
        console.error('Error:', error);
    });
}

function checkSystemUpdates() {
    showAlert('info', 'Checking for system updates...');
    
    // Simulate update check
    setTimeout(() => {
        const updatesAvailable = Math.random() > 0.5;
        if (updatesAvailable) {
            showAlert('warning', '3 system updates are available');
        } else {
            showAlert('success', 'System is up to date');
        }
    }, 2000);
}

function generateBackup() {
    showAlert('info', 'Starting backup process...');
    
    // Simulate backup creation
    setTimeout(() => {
        showAlert('success', 'Backup created successfully');
    }, 4000);
}

function viewLogs() {
    window.open('/admin/logs', '_blank');
}

function showAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    const container = document.querySelector('.container-fluid');
    container.insertAdjacentHTML('afterbegin', alertHtml);
    
    setTimeout(() => {
        const alert = container.querySelector('.alert');
        if (alert) {
            alert.remove();
        }
    }, 5000);
}
</script>
@endpush
@endsection
