@extends('layouts.panel')

@section('title', 'Plugin Details')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">{{ $plugin->name }}</h1>
                    <p class="text-muted mb-0">{{ $plugin->description }}</p>
                </div>
                <div>
                    <a href="{{ route('admin.plugins.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Plugins
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Plugin Information -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Plugin Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th>Name:</th>
                                    <td>{{ $plugin->name }}</td>
                                </tr>
                                <tr>
                                    <th>Version:</th>
                                    <td>
                                        {{ $plugin->version }}
                                        @if($plugin->update_available)
                                            <span class="badge bg-warning ms-2">
                                                Update to {{ $plugin->available_version }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Author:</th>
                                    <td>{{ $plugin->author ?? 'Unknown' }}</td>
                                </tr>
                                <tr>
                                    <th>Type:</th>
                                    <td>
                                        <span class="badge bg-info">{{ ucfirst($plugin->type) }}</span>
                                        @if($plugin->is_core)
                                            <span class="badge bg-primary ms-1">Core</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td>
                                        <span class="badge bg-{{ $plugin->getStatusColor() }}">
                                            {{ ucfirst($plugin->status) }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th>Install Date:</th>
                                    <td>{{ $plugin->install_date ? $plugin->install_date->format('M d, Y') : 'Unknown' }}</td>
                                </tr>
                                <tr>
                                    <th>Last Updated:</th>
                                    <td>{{ $plugin->updated_at->format('M d, Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <th>Last Check:</th>
                                    <td>{{ $plugin->last_update_check ? $plugin->last_update_check->format('M d, Y H:i') : 'Never' }}</td>
                                </tr>
                                <tr>
                                    <th>Auto Update:</th>
                                    <td>
                                        <span class="badge bg-{{ $plugin->auto_update ? 'success' : 'secondary' }}">
                                            {{ $plugin->auto_update ? 'Enabled' : 'Disabled' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Priority:</th>
                                    <td>{{ $plugin->priority ?? 'Normal' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($plugin->dependencies)
                    <hr>
                    <h6>Dependencies:</h6>
                    <div class="row">
                        @foreach($plugin->dependencies as $dependency)
                        <div class="col-md-6">
                            <span class="badge bg-light text-dark">{{ $dependency }}</span>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    @if($plugin->requirements)
                    <hr>
                    <h6>Requirements:</h6>
                    <div class="row">
                        @foreach($plugin->requirements as $key => $requirement)
                        <div class="col-md-6">
                            <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ $requirement }}
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

            <!-- Update Comments -->
            @if($plugin->updateComments->count() > 0)
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Update Comments & Notifications</h5>
                </div>
                <div class="card-body">
                    @foreach($plugin->updateComments as $comment)
                    <div class="alert alert-{{ $comment->getPriorityColor() }} d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="alert-heading">
                                {{ $comment->title }}
                                <span class="badge bg-{{ $comment->getStatusColor() }} ms-2">
                                    {{ ucfirst($comment->status) }}
                                </span>
                            </h6>
                            <p class="mb-2">{{ $comment->message }}</p>
                            <small class="text-muted">
                                <i class="fas fa-clock"></i> {{ $comment->created_at->format('M d, Y H:i') }}
                                @if($comment->user)
                                    • by {{ $comment->user->name }}
                                @endif
                                @if($comment->resolved_at)
                                    <br><i class="fas fa-check"></i> Resolved {{ $comment->resolved_at->format('M d, Y H:i') }}
                                    @if($comment->resolver)
                                        by {{ $comment->resolver->name }}
                                    @endif
                                @endif
                            </small>
                            @if($comment->metadata)
                            <details class="mt-2">
                                <summary class="text-muted">Additional Details</summary>
                                <pre class="mt-2 text-small">{{ json_encode($comment->metadata, JSON_PRETTY_PRINT) }}</pre>
                            </details>
                            @endif
                        </div>
                        @if($comment->status === 'pending' || $comment->status === 'acknowledged')
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
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Actions Sidebar -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    @if($plugin->update_available)
                    <button type="button" class="btn btn-warning w-100 mb-2" onclick="updatePlugin({{ $plugin->id }})">
                        <i class="fas fa-download"></i> Update to {{ $plugin->available_version }}
                    </button>
                    @endif

                    <button type="button" class="btn btn-outline-{{ $plugin->status === 'active' ? 'danger' : 'success' }} w-100 mb-2" 
                            onclick="togglePlugin({{ $plugin->id }})">
                        <i class="fas fa-{{ $plugin->status === 'active' ? 'pause' : 'play' }}"></i> 
                        {{ $plugin->status === 'active' ? 'Deactivate' : 'Activate' }}
                    </button>

                    <button type="button" class="btn btn-outline-primary w-100 mb-2" onclick="checkUpdates({{ $plugin->id }})">
                        <i class="fas fa-sync-alt"></i> Check for Updates
                    </button>

                    <hr>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="autoUpdate" 
                               {{ $plugin->auto_update ? 'checked' : '' }}
                               onchange="toggleAutoUpdate({{ $plugin->id }})">
                        <label class="form-check-label" for="autoUpdate">
                            Auto Update
                        </label>
                    </div>
                </div>
            </div>

            <!-- Plugin Stats -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <h4 class="text-primary">{{ $plugin->updateComments->count() }}</h4>
                            <small class="text-muted">Total Comments</small>
                        </div>
                        <div class="col-6">
                            <h4 class="text-warning">{{ $plugin->updateComments->where('status', 'pending')->count() }}</h4>
                            <small class="text-muted">Pending</small>
                        </div>
                    </div>
                    <hr>
                    <div class="row text-center">
                        <div class="col-6">
                            <h4 class="text-success">{{ $plugin->updateComments->where('status', 'resolved')->count() }}</h4>
                            <small class="text-muted">Resolved</small>
                        </div>
                        <div class="col-6">
                            <h4 class="text-danger">{{ $plugin->updateComments->where('priority', 4)->count() }}</h4>
                            <small class="text-muted">Critical</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function updatePlugin(pluginId) {
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
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
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
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

function checkUpdates(pluginId) {
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking...';
    btn.disabled = true;

    fetch(`/admin/plugins/${pluginId}/check-updates`, {
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
            setTimeout(() => location.reload(), 1000);
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
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('danger', data.message);
        }
    })
    .catch(error => {
        showAlert('danger', 'Error dismissing comment');
        console.error('Error:', error);
    });
}

function toggleAutoUpdate(pluginId) {
    const checkbox = event.target;
    const autoUpdate = checkbox.checked;

    fetch(`/admin/plugins/${pluginId}/auto-update`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ auto_update: autoUpdate })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
        } else {
            checkbox.checked = !autoUpdate; // Revert checkbox
            showAlert('danger', data.message);
        }
    })
    .catch(error => {
        checkbox.checked = !autoUpdate; // Revert checkbox
        showAlert('danger', 'Error updating auto-update setting');
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
