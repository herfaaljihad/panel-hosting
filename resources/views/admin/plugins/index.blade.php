@extends('layouts.panel')

@section('title', 'Plugin Management')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">Plugin Management</h1>
                <div>
                    <button type="button" class="btn btn-outline-primary me-2" onclick="checkForUpdates()">
                        <i class="fas fa-sync-alt"></i> Check Updates
                    </button>
                    <button type="button" class="btn btn-success" onclick="bulkUpdate()">
                        <i class="fas fa-download"></i> Update All
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">Total Plugins</h5>
                            <h2 class="mb-0">{{ $stats['total'] }}</h2>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-puzzle-piece fa-2x"></i>
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
                            <h5 class="card-title">Active</h5>
                            <h2 class="mb-0">{{ $stats['active'] }}</h2>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
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
                            <h5 class="card-title">Updates Available</h5>
                            <h2 class="mb-0">{{ $stats['updates_available'] }}</h2>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-download fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">Critical Issues</h5>
                            <h2 class="mb-0">{{ $stats['critical_comments'] }}</h2>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Plugins Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Installed Plugins</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Plugin</th>
                            <th>Version</th>
                            <th>Status</th>
                            <th>Update Status</th>
                            <th>Comments</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($plugins as $plugin)
                        <tr>
                            <td>
                                <div>
                                    <strong>{{ $plugin->name }}</strong>
                                    @if($plugin->is_core)
                                        <span class="badge bg-info ms-1">Core</span>
                                    @endif
                                    <br>
                                    <small class="text-muted">{{ $plugin->description }}</small>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <strong>{{ $plugin->version }}</strong>
                                    @if($plugin->update_available)
                                        <br>
                                        <small class="text-warning">
                                            <i class="fas fa-arrow-up"></i> {{ $plugin->available_version }}
                                        </small>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $plugin->getStatusColor() }}">
                                    {{ ucfirst($plugin->status) }}
                                </span>
                            </td>
                            <td>
                                @if($plugin->update_available)
                                    <span class="badge bg-warning">
                                        <i class="fas fa-download"></i> Update Available
                                    </span>
                                @else
                                    <span class="badge bg-success">
                                        <i class="fas fa-check"></i> Up to Date
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($plugin->updateComments->count() > 0)
                                    <span class="badge bg-{{ $plugin->updateComments->first()->getPriorityColor() }}">
                                        {{ $plugin->updateComments->count() }} Comment(s)
                                    </span>
                                @else
                                    <span class="text-muted">None</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    @if($plugin->update_available)
                                        <button type="button" class="btn btn-warning" 
                                                onclick="updatePlugin({{ $plugin->id }})">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    @endif
                                    <button type="button" class="btn btn-outline-{{ $plugin->status === 'active' ? 'danger' : 'success' }}" 
                                            onclick="togglePlugin({{ $plugin->id }})">
                                        <i class="fas fa-{{ $plugin->status === 'active' ? 'pause' : 'play' }}"></i>
                                    </button>
                                    <a href="{{ route('admin.plugins.show', $plugin) }}" class="btn btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No plugins installed
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Comments -->
    @if($plugins->flatMap->updateComments->where('status', '!=', 'resolved')->count() > 0)
    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Recent Update Comments</h5>
            <a href="{{ route('admin.plugins.comments') }}" class="btn btn-sm btn-outline-primary">
                View All Comments
            </a>
        </div>
        <div class="card-body">
            @foreach($plugins->flatMap->updateComments->where('status', '!=', 'resolved')->take(5) as $comment)
            <div class="alert alert-{{ $comment->getPriorityColor() }} d-flex justify-content-between align-items-start">
                <div>
                    <strong>{{ $comment->title }}</strong>
                    <p class="mb-1">{{ $comment->message }}</p>
                    <small class="text-muted">
                        {{ $comment->plugin->name }} • {{ $comment->created_at->diffForHumans() }}
                    </small>
                </div>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-success" 
                            onclick="resolveComment({{ $comment->id }})">
                        <i class="fas fa-check"></i> Resolve
                    </button>
                    <button type="button" class="btn btn-outline-secondary" 
                            onclick="dismissComment({{ $comment->id }})">
                        <i class="fas fa-times"></i> Dismiss
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
function checkForUpdates() {
    const btn = event.target.closest('button');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking...';
    btn.disabled = true;

    fetch('{{ route("admin.plugins.check-updates") }}', {
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
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert('danger', data.message);
        }
    })
    .catch(error => {
        showAlert('danger', 'Error checking for updates');
        console.error('Error:', error);
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

function updatePlugin(pluginId) {
    const btn = event.target.closest('button');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;

    fetch(`/admin/plugins/${pluginId}/update`, {
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
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert('danger', data.message);
        }
    })
    .catch(error => {
        showAlert('danger', 'Error updating plugin');
        console.error('Error:', error);
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

function togglePlugin(pluginId) {
    const btn = event.target.closest('button');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;

    fetch(`/admin/plugins/${pluginId}/toggle`, {
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
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert('danger', data.message);
        }
    })
    .catch(error => {
        showAlert('danger', 'Error toggling plugin status');
        console.error('Error:', error);
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

function bulkUpdate() {
    if (!confirm('Are you sure you want to update all plugins with available updates?')) {
        return;
    }

    const btn = event.target.closest('button');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
    btn.disabled = true;

    fetch('{{ route("admin.plugins.bulk-update") }}', {
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
            setTimeout(() => location.reload(), 2000);
        } else {
            showAlert('danger', data.message);
        }
    })
    .catch(error => {
        showAlert('danger', 'Error performing bulk update');
        console.error('Error:', error);
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

function resolveComment(commentId) {
    fetch(`/admin/plugins/comments/${commentId}/resolve`, {
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
            event.target.closest('.alert').remove();
        } else {
            showAlert('danger', data.message);
        }
    })
    .catch(error => {
        showAlert('danger', 'Error resolving comment');
        console.error('Error:', error);
    });
}

function dismissComment(commentId) {
    fetch(`/admin/plugins/comments/${commentId}/dismiss`, {
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
            event.target.closest('.alert').remove();
        } else {
            showAlert('danger', data.message);
        }
    })
    .catch(error => {
        showAlert('danger', 'Error dismissing comment');
        console.error('Error:', error);
    });
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
