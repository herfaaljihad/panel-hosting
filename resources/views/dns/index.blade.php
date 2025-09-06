@extends('layouts.panel')

@section('title', 'DNS Management')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">DNS Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDnsModal">
            <i class="fas fa-plus me-2"></i>Add DNS Record
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

<!-- DNS Records Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">DNS Records</h5>
    </div>
    <div class="card-body">
        @if($dnsRecords->count() > 0)
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Domain</th>
                            <th>Type</th>
                            <th>Name</th>
                            <th>Value</th>
                            <th>TTL</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dnsRecords as $record)
                            <tr>
                                <td>{{ $record->domain->name }}</td>
                                <td><span class="badge bg-info">{{ $record->type }}</span></td>
                                <td>{{ $record->name ?: '@' }}</td>
                                <td class="text-break">{{ Str::limit($record->value, 50) }}</td>
                                <td>{{ $record->ttl }}</td>
                                <td>{{ $record->priority ?: '-' }}</td>
                                <td>
                                    <span class="badge bg-{{ $record->is_active ? 'success' : 'danger' }}">
                                        {{ $record->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-primary" 
                                                onclick="editDnsRecord({{ $record->id }})"
                                                data-bs-toggle="modal" data-bs-target="#editDnsModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" action="{{ route('dns.destroy', $record) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" 
                                                    onclick="return confirm('Are you sure you want to delete this DNS record?')">
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
                {{ $dnsRecords->links() }}
            </div>
        @else
            <div class="text-center py-4">
                <i class="fas fa-network-wired fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No DNS Records Found</h5>
                <p class="text-muted">Create your first DNS record to manage your domain's DNS settings.</p>
            </div>
        @endif
    </div>
</div>

<!-- Add DNS Record Modal -->
<div class="modal fade" id="addDnsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('dns.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add DNS Record</h5>
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
                        <label for="type" class="form-label">Record Type</label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="">Select Type</option>
                            <option value="A">A Record</option>
                            <option value="AAAA">AAAA Record</option>
                            <option value="CNAME">CNAME Record</option>
                            <option value="MX">MX Record</option>
                            <option value="TXT">TXT Record</option>
                            <option value="NS">NS Record</option>
                            <option value="PTR">PTR Record</option>
                            <option value="SRV">SRV Record</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               placeholder="Leave empty for root domain">
                    </div>
                    <div class="mb-3">
                        <label for="value" class="form-label">Value</label>
                        <input type="text" class="form-control" id="value" name="value" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="ttl" class="form-label">TTL</label>
                                <select class="form-select" id="ttl" name="ttl">
                                    <option value="300">5 minutes</option>
                                    <option value="900">15 minutes</option>
                                    <option value="1800">30 minutes</option>
                                    <option value="3600" selected>1 hour</option>
                                    <option value="14400">4 hours</option>
                                    <option value="43200">12 hours</option>
                                    <option value="86400">24 hours</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="priority" class="form-label">Priority</label>
                                <input type="number" class="form-control" id="priority" name="priority" 
                                       placeholder="For MX/SRV records">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit DNS Record Modal -->
<div class="modal fade" id="editDnsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="editDnsForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit DNS Record</h5>
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
                        <label for="edit_type" class="form-label">Record Type</label>
                        <select class="form-select" id="edit_type" name="type" required>
                            <option value="A">A Record</option>
                            <option value="AAAA">AAAA Record</option>
                            <option value="CNAME">CNAME Record</option>
                            <option value="MX">MX Record</option>
                            <option value="TXT">TXT Record</option>
                            <option value="NS">NS Record</option>
                            <option value="PTR">PTR Record</option>
                            <option value="SRV">SRV Record</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name">
                    </div>
                    <div class="mb-3">
                        <label for="edit_value" class="form-label">Value</label>
                        <input type="text" class="form-control" id="edit_value" name="value" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_ttl" class="form-label">TTL</label>
                                <select class="form-select" id="edit_ttl" name="ttl">
                                    <option value="300">5 minutes</option>
                                    <option value="900">15 minutes</option>
                                    <option value="1800">30 minutes</option>
                                    <option value="3600">1 hour</option>
                                    <option value="14400">4 hours</option>
                                    <option value="43200">12 hours</option>
                                    <option value="86400">24 hours</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_priority" class="form-label">Priority</label>
                                <input type="number" class="form-control" id="edit_priority" name="priority">
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
                    <button type="submit" class="btn btn-primary">Update Record</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function editDnsRecord(id) {
    // Fetch DNS record data and populate edit modal
    fetch(`/dns/${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('editDnsForm').action = `/dns/${id}`;
            document.getElementById('edit_domain_id').value = data.domain_id;
            document.getElementById('edit_type').value = data.type;
            document.getElementById('edit_name').value = data.name || '';
            document.getElementById('edit_value').value = data.value;
            document.getElementById('edit_ttl').value = data.ttl;
            document.getElementById('edit_priority').value = data.priority || '';
            document.getElementById('edit_is_active').checked = data.is_active;
        });
}
</script>
@endsection
