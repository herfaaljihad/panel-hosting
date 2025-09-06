@extends('layouts.panel')

@section('title', 'Cache & Performance Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-tachometer-alt text-primary me-2"></i>
                    Cache & Performance
                </h1>
                <div>
                    <button class="btn btn-outline-secondary btn-sm" onclick="refreshStats()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-broom"></i> Clear Cache
                    </h5>
                </div>
                <div class="card-body">
                    <p class="card-text">Bersihkan cache aplikasi untuk membebaskan ruang dan memperbarui data.</p>
                    
                    <div class="row g-2">
                        <div class="col-6">
                            <button class="btn btn-outline-warning w-100" onclick="clearCache('application')">
                                <i class="fas fa-database"></i> Application
                            </button>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-outline-warning w-100" onclick="clearCache('config')">
                                <i class="fas fa-cog"></i> Config
                            </button>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-outline-warning w-100" onclick="clearCache('route')">
                                <i class="fas fa-route"></i> Routes
                            </button>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-outline-warning w-100" onclick="clearCache('view')">
                                <i class="fas fa-eye"></i> Views
                            </button>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <button class="btn btn-warning w-100" onclick="clearCache('all')">
                        <i class="fas fa-trash"></i> Clear All Caches
                    </button>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-rocket"></i> Optimize
                    </h5>
                </div>
                <div class="card-body">
                    <p class="card-text">Optimasi konfigurasi aplikasi untuk performa maksimal.</p>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="forceOptimize">
                            <label class="form-check-label" for="forceOptimize">
                                Force optimize (development mode)
                            </label>
                        </div>
                    </div>
                    
                    <button class="btn btn-success w-100" onclick="optimizeApp()">
                        <i class="fas fa-magic"></i> Optimize Application
                    </button>
                    
                    <small class="text-muted mt-2 d-block">
                        <i class="fas fa-info-circle"></i> 
                        Mengoptimalkan config, routes, views, dan autoloader
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Cache Statistics -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie"></i> Cache Statistics
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row" id="cacheStatsContainer">
                        <!-- Stats will be loaded here -->
                        <div class="col-12 text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading cache statistics...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Log -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-history"></i> Recent Activity
                    </h5>
                </div>
                <div class="card-body">
                    <div id="activityLog">
                        <p class="text-muted">No recent activity</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mb-0" id="loadingText">Processing...</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.cache-stat-card {
    transition: all 0.3s ease;
}

.cache-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.activity-item {
    border-left: 3px solid #007bff;
    padding-left: 15px;
    margin-bottom: 10px;
}

.activity-item.success {
    border-color: #28a745;
}

.activity-item.warning {
    border-color: #ffc107;
}

.activity-item.error {
    border-color: #dc3545;
}
</style>
@endpush

@push('scripts')
<script>
let activityLog = [];

// Load cache stats on page load
document.addEventListener('DOMContentLoaded', function() {
    loadCacheStats();
});

function loadCacheStats() {
    fetch('{{ route("admin.cache.stats") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayCacheStats(data.data);
            } else {
                showAlert('error', 'Failed to load cache statistics');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'Error loading cache statistics');
        });
}

function displayCacheStats(stats) {
    const container = document.getElementById('cacheStatsContainer');
    
    container.innerHTML = `
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card cache-stat-card border-primary">
                <div class="card-body text-center">
                    <i class="fas fa-database fa-2x text-primary mb-2"></i>
                    <h6>Application Cache</h6>
                    <h4 class="text-primary">${stats.application_cache?.size || '0 B'}</h4>
                    <small class="text-muted">${stats.application_cache?.files || 0} files</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card cache-stat-card border-success">
                <div class="card-body text-center">
                    <i class="fas fa-cog fa-2x text-success mb-2"></i>
                    <h6>Config Cache</h6>
                    <h4 class="text-success">${stats.config_cache?.size || '0 B'}</h4>
                    <small class="text-muted">${stats.config_cache?.exists ? 'Cached' : 'Not cached'}</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card cache-stat-card border-info">
                <div class="card-body text-center">
                    <i class="fas fa-route fa-2x text-info mb-2"></i>
                    <h6>Route Cache</h6>
                    <h4 class="text-info">${stats.route_cache?.size || '0 B'}</h4>
                    <small class="text-muted">${stats.route_cache?.exists ? 'Cached' : 'Not cached'}</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card cache-stat-card border-warning">
                <div class="card-body text-center">
                    <i class="fas fa-eye fa-2x text-warning mb-2"></i>
                    <h6>View Cache</h6>
                    <h4 class="text-warning">${stats.view_cache?.size || '0 B'}</h4>
                    <small class="text-muted">${stats.view_cache?.files || 0} files</small>
                </div>
            </div>
        </div>
        
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Total Cache Size:</strong> ${stats.total_size || '0 B'}
            </div>
        </div>
    `;
}

function clearCache(type) {
    const typeNames = {
        'application': 'Application Cache',
        'config': 'Configuration Cache',
        'route': 'Route Cache',
        'view': 'View Cache',
        'all': 'All Caches'
    };
    
    if (!confirm(`Are you sure you want to clear ${typeNames[type]}?`)) {
        return;
    }
    
    showLoading(`Clearing ${typeNames[type]}...`);
    
    fetch('{{ route("admin.cache.clear") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ type: type })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            showAlert('success', data.message);
            addActivity('success', `Cleared ${typeNames[type]}`);
            loadCacheStats(); // Refresh stats
        } else {
            showAlert('error', data.message);
            addActivity('error', `Failed to clear ${typeNames[type]}`);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showAlert('error', 'An error occurred while clearing cache');
        addActivity('error', `Error clearing ${typeNames[type]}`);
    });
}

function optimizeApp() {
    const force = document.getElementById('forceOptimize').checked;
    
    if (!confirm('Are you sure you want to optimize the application? This may take a few moments.')) {
        return;
    }
    
    showLoading('Optimizing application...');
    
    fetch('{{ route("admin.cache.optimize") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ force: force })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            showAlert('success', data.message);
            addActivity('success', 'Application optimized successfully');
            loadCacheStats(); // Refresh stats
        } else {
            showAlert('error', data.message);
            addActivity('error', 'Application optimization failed');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showAlert('error', 'An error occurred during optimization');
        addActivity('error', 'Optimization error occurred');
    });
}

function refreshStats() {
    loadCacheStats();
    showAlert('info', 'Cache statistics refreshed');
}

function showLoading(text) {
    document.getElementById('loadingText').textContent = text;
    new bootstrap.Modal(document.getElementById('loadingModal')).show();
}

function hideLoading() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('loadingModal'));
    if (modal) {
        modal.hide();
    }
}

function showAlert(type, message) {
    const alertTypes = {
        'success': 'alert-success',
        'error': 'alert-danger',
        'warning': 'alert-warning',
        'info': 'alert-info'
    };
    
    const alertHtml = `
        <div class="alert ${alertTypes[type]} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Insert at the top of the container
    const container = document.querySelector('.container-fluid');
    container.insertAdjacentHTML('afterbegin', alertHtml);
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        const alert = container.querySelector('.alert');
        if (alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    }, 5000);
}

function addActivity(type, message) {
    const now = new Date().toLocaleTimeString();
    activityLog.unshift({
        type: type,
        message: message,
        time: now
    });
    
    // Keep only last 10 activities
    if (activityLog.length > 10) {
        activityLog = activityLog.slice(0, 10);
    }
    
    updateActivityDisplay();
}

function updateActivityDisplay() {
    const container = document.getElementById('activityLog');
    
    if (activityLog.length === 0) {
        container.innerHTML = '<p class="text-muted">No recent activity</p>';
        return;
    }
    
    const html = activityLog.map(activity => `
        <div class="activity-item ${activity.type}">
            <small class="text-muted">${activity.time}</small>
            <div>${activity.message}</div>
        </div>
    `).join('');
    
    container.innerHTML = html;
}
</script>
@endpush
