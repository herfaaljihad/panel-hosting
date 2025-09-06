@extends('layouts.panel')

@section('title', 'Backup Management')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Backup Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBackupModal">
            <i class="fas fa-plus me-2"></i>Create Backup
        </button>
        <button type="button" class="btn btn-outline-secondary ms-2" data-bs-toggle="modal" data-bs-target="#scheduleBackupModal">
            <i class="fas fa-clock me-2"></i>Schedule Backup
        </button>
    </div>
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

<!-- Backup Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Backups</h6>
                        <h4>{{ $backups->count() }}</h4>
                    </div>
                    <i class="fas fa-download fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Completed</h6>
                        <h4>{{ $backups->where('status', 'completed')->count() }}</h4>
                    </div>
                    <i class="fas fa-check-circle fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Size</h6>
                        <h4>{{ number_format($backups->where('status', 'completed')->sum('file_size') / 1024 / 1024, 1) }} MB</h4>
                    </div>
                    <i class="fas fa-hdd fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Scheduled</h6>
                        <h4>{{ $backups->where('is_scheduled', true)->count() }}</h4>
                    </div>
                    <i class="fas fa-calendar-alt fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Backup Files Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Backup Files</h5>
    </div>
    <div class="card-body">
        @if($backups->count() > 0)
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Domain</th>
                            <th>Size</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Expires</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($backups as $backup)
                            <tr>
                                <td>
                                    <strong>{{ $backup->filename }}</strong>
                                    @if($backup->is_scheduled)
                                        <span class="badge bg-info ms-2">Scheduled</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ ucfirst($backup->type) }}</span>
                                </td>
                                <td>{{ $backup->domain ? $backup->domain->name : 'All' }}</td>
                                <td>
                                    @if($backup->file_size)
                                        {{ number_format($backup->file_size / 1024 / 1024, 1) }} MB
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($backup->status === 'completed')
                                        <span class="badge bg-success">Completed</span>
                                    @elseif($backup->status === 'processing')
                                        <span class="badge bg-warning">Processing</span>
                                    @elseif($backup->status === 'failed')
                                        <span class="badge bg-danger">Failed</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($backup->status) }}</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $backup->created_at->format('M j, Y H:i') }}
                                    <br><small class="text-muted">{{ $backup->created_at->diffForHumans() }}</small>
                                </td>
                                <td>
                                    @if($backup->expires_at)
                                        {{ $backup->expires_at->format('M j, Y') }}
                                        @if($backup->expires_at->isPast())
                                            <span class="badge bg-danger ms-1">Expired</span>
                                        @elseif($backup->expires_at->diffInDays() <= 7)
                                            <span class="badge bg-warning ms-1">Expires soon</span>
                                        @endif
                                    @else
                                        <span class="text-muted">Never</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        @if($backup->status === 'completed')
                                            <a href="{{ route('backups.download', $backup) }}" 
                                               class="btn btn-outline-primary" title="Download">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-success" 
                                                    onclick="restoreBackup({{ $backup->id }})" title="Restore">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                        @endif
                                        <button type="button" class="btn btn-outline-info" 
                                                onclick="viewBackupDetails({{ $backup->id }})"
                                                data-bs-toggle="modal" data-bs-target="#backupDetailsModal">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <form method="POST" action="{{ route('backups.destroy', $backup) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" 
                                                    onclick="return confirm('Are you sure you want to delete this backup?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                {{ $backups->links() }}
            </div>
        @else
            <div class="text-center py-4">
                <i class="fas fa-download fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No Backups Found</h5>
                <p class="text-muted">Create your first backup to protect your data.</p>
            </div>
        @endif
    </div>
</div>

<!-- Create Backup Modal -->
<div class="modal fade" id="createBackupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('backups.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Create Backup</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="type" class="form-label">Backup Type</label>
                        <select class="form-select" id="type" name="type" required onchange="toggleDomainField()">
                            <option value="">Select Type</option>
                            <option value="full">Full Backup (All data)</option>
                            <option value="files">Files Only</option>
                            <option value="database">Database Only</option>
                            <option value="domain">Domain Specific</option>
                        </select>
                    </div>
                    <div class="mb-3" id="domainField" style="display: none;">
                        <label for="domain_id" class="form-label">Domain</label>
                        <select class="form-select" id="domain_id" name="domain_id">
                            <option value="">Select Domain</option>
                            @foreach($domains as $domain)
                                <option value="{{ $domain->id }}">{{ $domain->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="filename" class="form-label">Backup Name</label>
                        <input type="text" class="form-control" id="filename" name="filename" 
                               placeholder="backup-{{ date('Y-m-d-H-i') }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="retention_days" class="form-label">Retention (Days)</label>
                        <select class="form-select" id="retention_days" name="retention_days">
                            <option value="">Keep forever</option>
                            <option value="7">7 days</option>
                            <option value="30" selected>30 days</option>
                            <option value="90">90 days</option>
                            <option value="365">1 year</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="compress" name="compress" value="1" checked>
                            <label class="form-check-label" for="compress">
                                Compress backup
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="encrypt" name="encrypt" value="1">
                            <label class="form-check-label" for="encrypt">
                                Encrypt backup
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Backup</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Schedule Backup Modal -->
<div class="modal fade" id="scheduleBackupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('backups.schedule') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Schedule Backup</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="schedule_name" class="form-label">Schedule Name</label>
                        <input type="text" class="form-control" id="schedule_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="schedule_type" class="form-label">Backup Type</label>
                        <select class="form-select" id="schedule_type" name="type" required>
                            <option value="">Select Type</option>
                            <option value="full">Full Backup</option>
                            <option value="files">Files Only</option>
                            <option value="database">Database Only</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="schedule_frequency" class="form-label">Frequency</label>
                        <select class="form-select" id="schedule_frequency" name="frequency" required>
                            <option value="">Select Frequency</option>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="schedule_time" class="form-label">Time</label>
                        <input type="time" class="form-control" id="schedule_time" name="time" value="02:00" required>
                    </div>
                    <div class="mb-3">
                        <label for="schedule_retention" class="form-label">Keep Backups For</label>
                        <select class="form-select" id="schedule_retention" name="retention_days">
                            <option value="30" selected>30 days</option>
                            <option value="90">90 days</option>
                            <option value="365">1 year</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="schedule_active" name="is_active" value="1" checked>
                            <label class="form-check-label" for="schedule_active">
                                Active
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Schedule Backup</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Backup Details Modal -->
<div class="modal fade" id="backupDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Backup Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="backupDetailsContent">
                    <!-- Backup details will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function toggleDomainField() {
    const type = document.getElementById('type').value;
    const domainField = document.getElementById('domainField');
    
    if (type === 'domain') {
        domainField.style.display = 'block';
        document.getElementById('domain_id').required = true;
    } else {
        domainField.style.display = 'none';
        document.getElementById('domain_id').required = false;
    }
}

function viewBackupDetails(id) {
    fetch(`/backups/${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('backupDetailsContent').innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <strong>Filename:</strong> ${data.filename}<br>
                        <strong>Type:</strong> ${data.type}<br>
                        <strong>Domain:</strong> ${data.domain ? data.domain.name : 'All'}<br>
                        <strong>Status:</strong> <span class="badge bg-${data.status === 'completed' ? 'success' : 'warning'}">${data.status}</span><br>
                        <strong>Size:</strong> ${data.file_size ? (data.file_size / 1024 / 1024).toFixed(1) + ' MB' : 'N/A'}
                    </div>
                    <div class="col-md-6">
                        <strong>Created:</strong> ${data.created_at}<br>
                        <strong>Expires:</strong> ${data.expires_at || 'Never'}<br>
                        <strong>Compressed:</strong> ${data.is_compressed ? 'Yes' : 'No'}<br>
                        <strong>Encrypted:</strong> ${data.is_encrypted ? 'Yes' : 'No'}<br>
                        <strong>Storage Path:</strong> <code>${data.storage_path || 'N/A'}</code>
                    </div>
                </div>
                ${data.error_message ? `
                <div class="mt-3">
                    <strong>Error Message:</strong>
                    <div class="alert alert-danger mt-2">${data.error_message}</div>
                </div>
                ` : ''}
                ${data.metadata ? `
                <div class="mt-3">
                    <strong>Metadata:</strong>
                    <pre class="bg-light p-2 mt-2">${JSON.stringify(data.metadata, null, 2)}</pre>
                </div>
                ` : ''}
            `;
        });
}

function restoreBackup(id) {
    if (confirm('Are you sure you want to restore this backup? This will overwrite existing data.')) {
        fetch(`/backups/${id}/restore`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Backup restore initiated. You will be notified when complete.');
                location.reload();
            } else {
                alert('Failed to restore backup: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error restoring backup');
        });
    }
}

// Auto-generate filename based on type
document.getElementById('type').addEventListener('change', function() {
    const type = this.value;
    const now = new Date();
    const timestamp = now.toISOString().slice(0, 16).replace('T', '-').replace(':', '-');
    
    if (type) {
        document.getElementById('filename').value = `${type}-backup-${timestamp}`;
    }
});
</script>
@endsection
