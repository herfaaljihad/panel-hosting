@extends('layouts.panel')

@section('title', 'Package Management')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Package Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPackageModal">
            <i class="fas fa-plus me-2"></i>Create Package
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

<!-- Package Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Packages</h6>
                        <h4>{{ $packages->count() }}</h4>
                    </div>
                    <i class="fas fa-box fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Active Packages</h6>
                        <h4>{{ $packages->where('is_active', true)->count() }}</h4>
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
                        <h6 class="card-title">Users Assigned</h6>
                        <h4>{{ $packages->sum(function($package) { return $package->users->count(); }) }}</h4>
                    </div>
                    <i class="fas fa-users fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Avg Price</h6>
                        <h4>${{ number_format($packages->avg('price'), 2) }}</h4>
                    </div>
                    <i class="fas fa-dollar-sign fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Packages Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Hosting Packages</h5>
    </div>
    <div class="card-body">
        @if($packages->count() > 0)
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Package Name</th>
                            <th>Price</th>
                            <th>Limits</th>
                            <th>Features</th>
                            <th>Users</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($packages as $package)
                            <tr>
                                <td>
                                    <strong>{{ $package->name }}</strong>
                                    @if($package->description)
                                        <br><small class="text-muted">{{ Str::limit($package->description, 50) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <strong>${{ number_format($package->price, 2) }}</strong>
                                    <br><small class="text-muted">{{ $package->billing_cycle }}</small>
                                </td>
                                <td>
                                    <small>
                                        <strong>Disk:</strong> {{ $package->disk_space_gb }}GB<br>
                                        <strong>Bandwidth:</strong> {{ $package->bandwidth_gb }}GB<br>
                                        <strong>Domains:</strong> {{ $package->max_domains ?: '∞' }}<br>
                                        <strong>Databases:</strong> {{ $package->max_databases ?: '∞' }}<br>
                                        <strong>Email:</strong> {{ $package->max_email_accounts ?: '∞' }}
                                    </small>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @if($package->ssl_enabled)
                                            <span class="badge bg-success">SSL</span>
                                        @endif
                                        @if($package->ftp_enabled)
                                            <span class="badge bg-info">FTP</span>
                                        @endif
                                        @if($package->cron_enabled)
                                            <span class="badge bg-warning">Cron</span>
                                        @endif
                                        @if($package->backup_enabled)
                                            <span class="badge bg-secondary">Backup</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-primary">{{ $package->users->count() }}</span>
                                    @if($package->users->count() > 0)
                                        <button type="button" class="btn btn-sm btn-link p-0 ms-1" 
                                                onclick="showPackageUsers({{ $package->id }})"
                                                data-bs-toggle="modal" data-bs-target="#packageUsersModal">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $package->is_active ? 'success' : 'danger' }}">
                                        {{ $package->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-primary" 
                                                onclick="editPackage({{ $package->id }})"
                                                data-bs-toggle="modal" data-bs-target="#editPackageModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-info" 
                                                onclick="duplicatePackage({{ $package->id }})">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                        @if($package->users->count() === 0)
                                            <form method="POST" action="{{ route('packages.destroy', $package) }}" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" 
                                                        onclick="return confirm('Are you sure you want to delete this package?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                {{ $packages->links() }}
            </div>
        @else
            <div class="text-center py-4">
                <i class="fas fa-box fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No Packages Found</h5>
                <p class="text-muted">Create your first hosting package to assign to users.</p>
            </div>
        @endif
    </div>
</div>

<!-- Create Package Modal -->
<div class="modal fade" id="addPackageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('packages.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Create Package</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Package Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="price" class="form-label">Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="price" name="price" step="0.01" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="billing_cycle" class="form-label">Billing Cycle</label>
                                <select class="form-select" id="billing_cycle" name="billing_cycle" required>
                                    <option value="monthly">Monthly</option>
                                    <option value="quarterly">Quarterly</option>
                                    <option value="semi-annually">Semi-Annually</option>
                                    <option value="annually">Annually</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sort_order" class="form-label">Sort Order</label>
                                <input type="number" class="form-control" id="sort_order" name="sort_order" value="0">
                            </div>
                        </div>
                    </div>
                    
                    <h6 class="border-bottom pb-2 mb-3">Resource Limits</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="disk_space_gb" class="form-label">Disk Space (GB)</label>
                                <input type="number" class="form-control" id="disk_space_gb" name="disk_space_gb" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="bandwidth_gb" class="form-label">Bandwidth (GB)</label>
                                <input type="number" class="form-control" id="bandwidth_gb" name="bandwidth_gb" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="max_domains" class="form-label">Max Domains</label>
                                <input type="number" class="form-control" id="max_domains" name="max_domains" 
                                       placeholder="Leave empty for unlimited">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="max_databases" class="form-label">Max Databases</label>
                                <input type="number" class="form-control" id="max_databases" name="max_databases"
                                       placeholder="Leave empty for unlimited">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="max_email_accounts" class="form-label">Max Email Accounts</label>
                                <input type="number" class="form-control" id="max_email_accounts" name="max_email_accounts"
                                       placeholder="Leave empty for unlimited">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="max_ftp_accounts" class="form-label">Max FTP Accounts</label>
                                <input type="number" class="form-control" id="max_ftp_accounts" name="max_ftp_accounts"
                                       placeholder="Leave empty for unlimited">
                            </div>
                        </div>
                    </div>
                    
                    <h6 class="border-bottom pb-2 mb-3">Features</h6>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="ssl_enabled" name="ssl_enabled" value="1" checked>
                                <label class="form-check-label" for="ssl_enabled">SSL Certificates</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="ftp_enabled" name="ftp_enabled" value="1" checked>
                                <label class="form-check-label" for="ftp_enabled">FTP Access</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="cron_enabled" name="cron_enabled" value="1" checked>
                                <label class="form-check-label" for="cron_enabled">Cron Jobs</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="backup_enabled" name="backup_enabled" value="1" checked>
                                <label class="form-check-label" for="backup_enabled">Backup Service</label>
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
                    <button type="submit" class="btn btn-primary">Create Package</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Package Modal -->
<div class="modal fade" id="editPackageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="editPackageForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Package</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Same form fields as create, but with edit_ prefix IDs -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_name" class="form-label">Package Name</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_price" class="form-label">Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="edit_price" name="price" step="0.01" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="2"></textarea>
                    </div>
                    <!-- Resource limits and features similar to create form -->
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
                    <button type="submit" class="btn btn-primary">Update Package</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Package Users Modal -->
<div class="modal fade" id="packageUsersModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Package Users</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="packageUsersContent">
                    <!-- Users will be loaded here -->
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
function editPackage(id) {
    fetch(`/packages/${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('editPackageForm').action = `/packages/${id}`;
            document.getElementById('edit_name').value = data.name;
            document.getElementById('edit_price').value = data.price;
            document.getElementById('edit_description').value = data.description || '';
            document.getElementById('edit_is_active').checked = data.is_active;
            // Add other fields as needed
        });
}

function duplicatePackage(id) {
    if (confirm('Create a copy of this package?')) {
        fetch(`/packages/${id}/duplicate`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to duplicate package: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error duplicating package');
        });
    }
}

function showPackageUsers(id) {
    fetch(`/packages/${id}/users`)
        .then(response => response.json())
        .then(data => {
            let usersHtml = '<div class="table-responsive"><table class="table table-sm">';
            usersHtml += '<thead><tr><th>Name</th><th>Email</th><th>Registration</th><th>Status</th></tr></thead><tbody>';
            
            if (data.users && data.users.length > 0) {
                data.users.forEach(user => {
                    usersHtml += `<tr>
                        <td>${user.name}</td>
                        <td>${user.email}</td>
                        <td>${user.created_at}</td>
                        <td><span class="badge bg-${user.email_verified_at ? 'success' : 'warning'}">${user.email_verified_at ? 'Verified' : 'Pending'}</span></td>
                    </tr>`;
                });
            } else {
                usersHtml += '<tr><td colspan="4" class="text-center text-muted">No users assigned to this package</td></tr>';
            }
            
            usersHtml += '</tbody></table></div>';
            document.getElementById('packageUsersContent').innerHTML = usersHtml;
        });
}
</script>
@endsection
