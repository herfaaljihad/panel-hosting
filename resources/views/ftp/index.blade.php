@extends('layouts.panel')

@section('title', 'FTP Accounts')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">FTP Accounts</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFtpModal">
            <i class="fas fa-plus me-2"></i>Create FTP Account
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

<!-- FTP Usage Summary -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Accounts</h6>
                        <h4>{{ $ftpAccounts->count() }}</h4>
                    </div>
                    <i class="fas fa-users fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Active</h6>
                        <h4>{{ $ftpAccounts->where('is_active', true)->count() }}</h4>
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
                        <h6 class="card-title">Data Transfer</h6>
                        <h4>{{ number_format($ftpAccounts->sum('bandwidth_used') / 1024 / 1024, 1) }} MB</h4>
                    </div>
                    <i class="fas fa-exchange-alt fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Quota Used</h6>
                        <h4>{{ number_format($ftpAccounts->sum('quota_used') / 1024 / 1024, 1) }} MB</h4>
                    </div>
                    <i class="fas fa-hdd fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FTP Accounts Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">FTP Accounts</h5>
    </div>
    <div class="card-body">
        @if($ftpAccounts->count() > 0)
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Domain</th>
                            <th>Directory</th>
                            <th>Quota</th>
                            <th>Used</th>
                            <th>Bandwidth</th>
                            <th>Last Login</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ftpAccounts as $account)
                            <tr>
                                <td><strong>{{ $account->username }}</strong></td>
                                <td>{{ $account->domain->name }}</td>
                                <td><code>{{ $account->home_directory }}</code></td>
                                <td>
                                    @if($account->quota_mb)
                                        {{ $account->quota_mb }} MB
                                    @else
                                        <span class="text-muted">Unlimited</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        {{ number_format($account->quota_used / 1024 / 1024, 1) }} MB
                                        @if($account->quota_mb)
                                            <div class="progress ms-2" style="width: 60px; height: 8px;">
                                                <div class="progress-bar" role="progressbar" 
                                                     style="width: {{ min(100, ($account->quota_used / 1024 / 1024) / $account->quota_mb * 100) }}%"></div>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ number_format($account->bandwidth_used / 1024 / 1024, 1) }} MB</td>
                                <td>
                                    @if($account->last_login_at)
                                        {{ $account->last_login_at->diffForHumans() }}
                                    @else
                                        <span class="text-muted">Never</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $account->is_active ? 'success' : 'danger' }}">
                                        {{ $account->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-primary" 
                                                onclick="editFtpAccount({{ $account->id }})"
                                                data-bs-toggle="modal" data-bs-target="#editFtpModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-info" 
                                                onclick="resetPassword({{ $account->id }})">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        <form method="POST" action="{{ route('ftp.destroy', $account) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" 
                                                    onclick="return confirm('Are you sure you want to delete this FTP account?')">
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
                {{ $ftpAccounts->links() }}
            </div>
        @else
            <div class="text-center py-4">
                <i class="fas fa-upload fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No FTP Accounts Found</h5>
                <p class="text-muted">Create your first FTP account to manage files via FTP.</p>
            </div>
        @endif
    </div>
</div>

<!-- Create FTP Account Modal -->
<div class="modal fade" id="addFtpModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('ftp.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Create FTP Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="domain_id" class="form-label">Domain</label>
                        <select class="form-select" id="domain_id" name="domain_id" required>
                            <option value="">Select Domain</option>
                            @foreach($domains as $domain)
                                <option value="{{ $domain->id }}">{{ $domain->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required
                               pattern="[a-zA-Z0-9_-]+" title="Only letters, numbers, underscores and hyphens allowed">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="generatePassword()">
                                <i class="fas fa-random"></i>
                            </button>
                        </div>
                        <div class="form-text">Minimum 8 characters</div>
                    </div>
                    <div class="mb-3">
                        <label for="home_directory" class="form-label">Home Directory</label>
                        <input type="text" class="form-control" id="home_directory" name="home_directory" 
                               placeholder="/public_html" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="quota_mb" class="form-label">Quota (MB)</label>
                                <input type="number" class="form-control" id="quota_mb" name="quota_mb" 
                                       placeholder="Leave empty for unlimited">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="bandwidth_limit_mb" class="form-label">Bandwidth Limit (MB)</label>
                                <input type="number" class="form-control" id="bandwidth_limit_mb" name="bandwidth_limit_mb" 
                                       placeholder="Monthly bandwidth limit">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit FTP Account Modal -->
<div class="modal fade" id="editFtpModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="editFtpForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit FTP Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_domain_id" class="form-label">Domain</label>
                        <select class="form-select" id="edit_domain_id" name="domain_id" required>
                            @foreach($domains as $domain)
                                <option value="{{ $domain->id }}">{{ $domain->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="edit_username" name="username" required readonly>
                    </div>
                    <div class="mb-3">
                        <label for="edit_home_directory" class="form-label">Home Directory</label>
                        <input type="text" class="form-control" id="edit_home_directory" name="home_directory" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_quota_mb" class="form-label">Quota (MB)</label>
                                <input type="number" class="form-control" id="edit_quota_mb" name="quota_mb">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_bandwidth_limit_mb" class="form-label">Bandwidth Limit (MB)</label>
                                <input type="number" class="form-control" id="edit_bandwidth_limit_mb" name="bandwidth_limit_mb">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active" value="1">
                            <label class="form-check-label" for="edit_is_active">
                                Active
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Account</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function generatePassword() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
    let password = '';
    for (let i = 0; i < 12; i++) {
        password += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('password').value = password;
}

function editFtpAccount(id) {
    // Fetch FTP account data and populate edit modal
    fetch(`/ftp/${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('editFtpForm').action = `/ftp/${id}`;
            document.getElementById('edit_domain_id').value = data.domain_id;
            document.getElementById('edit_username').value = data.username;
            document.getElementById('edit_home_directory').value = data.home_directory;
            document.getElementById('edit_quota_mb').value = data.quota_mb || '';
            document.getElementById('edit_bandwidth_limit_mb').value = data.bandwidth_limit_mb || '';
            document.getElementById('edit_is_active').checked = data.is_active;
        });
}

function resetPassword(id) {
    const newPassword = prompt('Enter new password:');
    if (newPassword && newPassword.length >= 8) {
        fetch(`/ftp/${id}/reset-password`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ password: newPassword })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Password updated successfully');
            } else {
                alert('Failed to update password: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error updating password');
        });
    } else if (newPassword) {
        alert('Password must be at least 8 characters long');
    }
}
</script>
@endsection
