@extends('layouts.panel')

@section('title', 'Enhanced Dashboard')

@section('content')
<div class="container-fluid">
    <!-- System Health Alert -->
    @if(isset($health) && $health['status'] !== 'healthy')
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-{{ $health['status'] === 'critical' ? 'danger' : 'warning' }} alert-dismissible fade show">
                <h5 class="alert-heading">
                    <i class="fas fa-{{ $health['status'] === 'critical' ? 'exclamation-triangle' : 'exclamation-circle' }}"></i>
                    System {{ ucfirst($health['status']) }}
                </h5>
                @if(!empty($health['issues']))
                    <ul class="mb-0">
                        @foreach($health['issues'] as $issue)
                        <li>{{ $issue }}</li>
                        @endforeach
                    </ul>
                @endif
                @if(!empty($health['warnings']))
                    <ul class="mb-0">
                        @foreach($health['warnings'] as $warning)
                        <li>{{ $warning }}</li>
                        @endforeach
                    </ul>
                @endif
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    </div>
    @endif

    <!-- Quick Stats Row -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Domains</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['domains']['total'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-globe fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    @if(isset($stats['domains']['recent']) && $stats['domains']['recent'] > 0)
                    <div class="text-success small">
                        <i class="fas fa-arrow-up"></i> {{ $stats['domains']['recent'] }} new this week
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Databases</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['databases']['total'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-database fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    @if(isset($stats['databases']['recent']) && $stats['databases']['recent'] > 0)
                    <div class="text-success small">
                        <i class="fas fa-arrow-up"></i> {{ $stats['databases']['recent'] }} new this week
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Email Accounts</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['emails']['total_accounts'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-envelope fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    @if(isset($stats['emails']['recent']) && $stats['emails']['recent'] > 0)
                    <div class="text-success small">
                        <i class="fas fa-arrow-up"></i> {{ $stats['emails']['recent'] }} new this week
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Disk Usage
                            </div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">{{ $stats['system']['disk_usage'] ?? 0 }}%</div>
                                </div>
                                <div class="col">
                                    <div class="progress progress-sm mr-2">
                                        <div class="progress-bar bg-warning" role="progressbar" 
                                             style="width: {{ $stats['system']['disk_usage'] ?? 0 }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-hdd fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- System Resources -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">System Resources</h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow">
                            <a class="dropdown-item" href="#" onclick="refreshStats()">
                                <i class="fas fa-sync fa-sm fa-fw mr-2 text-gray-400"></i>
                                Refresh
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- CPU Usage -->
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted">CPU Usage</h6>
                            <div class="progress mb-2">
                                <div class="progress-bar bg-primary" role="progressbar" 
                                     style="width: {{ $stats['system']['cpu_usage'] ?? 0 }}%" 
                                     id="cpu-progress">
                                    {{ $stats['system']['cpu_usage'] ?? 0 }}%
                                </div>
                            </div>
                        </div>

                        <!-- Memory Usage -->
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted">Memory Usage</h6>
                            @if(isset($stats['system']['memory_used']) && isset($stats['system']['memory_total']))
                                @php $memPercent = ($stats['system']['memory_used'] / $stats['system']['memory_total']) * 100 @endphp
                                <div class="progress mb-2">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: {{ $memPercent }}%" 
                                         id="memory-progress">
                                        {{ number_format($memPercent, 1) }}%
                                    </div>
                                </div>
                                <small class="text-muted">
                                    {{ $stats['system']['memory_used'] }}MB / {{ $stats['system']['memory_total'] }}MB
                                </small>
                            @else
                                <div class="progress mb-2">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 0%">N/A</div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Real-time Chart -->
                    <div class="chart-container" style="position: relative; height: 300px;">
                        <canvas id="systemChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity & Alerts -->
        <div class="col-xl-4 col-lg-5">
            <!-- Alerts -->
            @if(isset($alerts) && $alerts->count() > 0)
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-danger">System Alerts</h6>
                </div>
                <div class="card-body">
                    <div class="alert-list" style="max-height: 200px; overflow-y: auto;">
                        @foreach($alerts->take(5) as $alert)
                        <div class="d-flex align-items-center p-2 mb-2 border-left border-{{ $alert['type'] === 'critical' ? 'danger' : 'warning' }}">
                            <div class="mr-3">
                                <i class="fas fa-{{ $alert['type'] === 'critical' ? 'exclamation-triangle text-danger' : 'exclamation-circle text-warning' }}"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1 text-sm">{{ $alert['title'] }}</h6>
                                <p class="mb-0 text-xs text-muted">{{ $alert['message'] }}</p>
                                <small class="text-muted">{{ $alert['timestamp']->diffForHumans() }}</small>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Quick Actions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <a href="{{ route('domains.create') }}" class="btn btn-primary btn-sm btn-block mb-2">
                                <i class="fas fa-plus"></i> Add Domain
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('databases.create') }}" class="btn btn-success btn-sm btn-block mb-2">
                                <i class="fas fa-plus"></i> Add Database
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('emails.create') }}" class="btn btn-info btn-sm btn-block mb-2">
                                <i class="fas fa-plus"></i> Add Email
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('backups.create') }}" class="btn btn-warning btn-sm btn-block mb-2">
                                <i class="fas fa-download"></i> Create Backup
                            </a>
                        </div>
                    </div>
                    
                    @if(Auth::user() && Auth::user()->is_admin)
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <a href="{{ route('admin.index') }}" class="btn btn-dark btn-sm btn-block">
                                <i class="fas fa-cogs"></i> Admin Panel
                            </a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Service Status -->
            @if(isset($services))
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Service Status</h6>
                </div>
                <div class="card-body">
                    @foreach($services as $service => $status)
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-sm">{{ ucfirst($service) }}</span>
                        <span class="badge badge-{{ $status['active'] ? 'success' : 'danger' }}">
                            {{ $status['active'] ? 'Running' : 'Stopped' }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Performance Charts Row -->
    <div class="row">
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Performance Metrics</h6>
                </div>
                <div class="card-body">
                    <canvas id="performanceChart" height="200"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Security Overview</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-6 mb-3">
                            <div class="text-warning">
                                <i class="fas fa-shield-alt fa-2x"></i>
                            </div>
                            <h5 class="mt-2">{{ $stats['security']['failed_logins_24h'] ?? 0 }}</h5>
                            <small class="text-muted">Failed Logins (24h)</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="text-success">
                                <i class="fas fa-user-shield fa-2x"></i>
                            </div>
                            <h5 class="mt-2">{{ $stats['security']['2fa_enabled_users'] ?? 0 }}</h5>
                            <small class="text-muted">2FA Enabled Users</small>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <a href="{{ route('2fa.show') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-shield-alt"></i> Security Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Auto-refresh and real-time updates -->
<script>
let systemChart, performanceChart;
let refreshInterval;

document.addEventListener('DOMContentLoaded', function() {
    initCharts();
    startAutoRefresh();
});

function initCharts() {
    // System Resource Chart
    const systemCtx = document.getElementById('systemChart').getContext('2d');
    systemChart = new Chart(systemCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'CPU %',
                data: [],
                borderColor: 'rgb(78, 115, 223)',
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                tension: 0.3
            }, {
                label: 'Memory %',
                data: [],
                borderColor: 'rgb(28, 200, 138)',
                backgroundColor: 'rgba(28, 200, 138, 0.1)',
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            },
            animation: {
                duration: 750
            }
        }
    });

    // Performance Chart
    const perfCtx = document.getElementById('performanceChart').getContext('2d');
    performanceChart = new Chart(perfCtx, {
        type: 'doughnut',
        data: {
            labels: ['Response Time', 'Error Rate', 'Cache Hit Rate'],
            datasets: [{
                data: [
                    {{ $stats['performance']['avg_response_time_24h'] ?? 0 }},
                    {{ $stats['performance']['error_rate_24h'] ?? 0 }},
                    {{ $stats['performance']['cache_hit_rate'] ?? 0 }}
                ],
                backgroundColor: [
                    'rgba(78, 115, 223, 0.8)',
                    'rgba(231, 74, 59, 0.8)',
                    'rgba(28, 200, 138, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

function refreshStats() {
    fetch('/api/dashboard/stats')
        .then(response => response.json())
        .then(data => {
            updateCharts(data);
            updateProgressBars(data);
        })
        .catch(error => console.error('Error refreshing stats:', error));
}

function updateCharts(data) {
    const now = new Date().toLocaleTimeString();
    
    // Update system chart
    systemChart.data.labels.push(now);
    systemChart.data.datasets[0].data.push(data.system.cpu_usage || 0);
    systemChart.data.datasets[1].data.push(
        data.system.memory_used && data.system.memory_total 
            ? (data.system.memory_used / data.system.memory_total) * 100 
            : 0
    );
    
    // Keep only last 20 data points
    if (systemChart.data.labels.length > 20) {
        systemChart.data.labels.shift();
        systemChart.data.datasets[0].data.shift();
        systemChart.data.datasets[1].data.shift();
    }
    
    systemChart.update('none');
}

function updateProgressBars(data) {
    // Update CPU progress bar
    const cpuProgress = document.getElementById('cpu-progress');
    if (cpuProgress) {
        const cpuUsage = data.system.cpu_usage || 0;
        cpuProgress.style.width = cpuUsage + '%';
        cpuProgress.textContent = cpuUsage + '%';
    }
    
    // Update Memory progress bar
    const memoryProgress = document.getElementById('memory-progress');
    if (memoryProgress && data.system.memory_used && data.system.memory_total) {
        const memPercent = (data.system.memory_used / data.system.memory_total) * 100;
        memoryProgress.style.width = memPercent + '%';
        memoryProgress.textContent = Math.round(memPercent * 10) / 10 + '%';
    }
}

function startAutoRefresh() {
    refreshInterval = setInterval(refreshStats, 30000); // Refresh every 30 seconds
}

function stopAutoRefresh() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
}

// Stop refresh when page is hidden
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        stopAutoRefresh();
    } else {
        startAutoRefresh();
    }
});
</script>

<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}
.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}
.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}
.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}
.progress-sm {
    height: 0.5rem;
}
.chart-container {
    position: relative;
    height: 300px;
}
</style>
@endsection
