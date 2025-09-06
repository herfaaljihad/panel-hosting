@extends('layouts.panel')

@section('title', 'SSL Certificates')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">SSL Certificates</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSslModal">
            <i class="fas fa-plus me-2"></i>Request SSL Certificate
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

<!-- SSL Certificates Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">SSL Certificates</h5>
    </div>
    <div class="card-body">
        @if($certificates->count() > 0)
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Domain</th>
                            <th>Issued By</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Issue Date</th>
                            <th>Expiry Date</th>
                            <th>Auto Renew</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($certificates as $cert)
                            <tr>
                                <td>{{ $cert->domain->name }}</td>
                                <td>{{ $cert->issuer }}</td>
                                <td><span class="badge bg-info">{{ $cert->type }}</span></td>
                                <td>
                                    @if($cert->status === 'active')
                                        <span class="badge bg-success">Active</span>
                                    @elseif($cert->status === 'pending')
                                        <span class="badge bg-warning">Pending</span>
                                    @elseif($cert->status === 'expired')
                                        <span class="badge bg-danger">Expired</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($cert->status) }}</span>
                                    @endif
                                </td>
                                <td>{{ $cert->issued_at ? $cert->issued_at->format('M j, Y') : '-' }}</td>
                                <td>
                                    {{ $cert->expires_at ? $cert->expires_at->format('M j, Y') : '-' }}
                                    @if($cert->expires_at && $cert->expires_at->diffInDays() <= 30)
                                        <i class="fas fa-exclamation-triangle text-warning ms-1" title="Expires soon"></i>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $cert->auto_renew ? 'success' : 'secondary' }}">
                                        {{ $cert->auto_renew ? 'Yes' : 'No' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-info" 
                                                onclick="viewCertificate({{ $cert->id }})"
                                                data-bs-toggle="modal" data-bs-target="#viewSslModal">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        @if($cert->status === 'active')
                                            <button type="button" class="btn btn-outline-warning" 
                                                    onclick="renewCertificate({{ $cert->id }})">
                                                <i class="fas fa-sync"></i>
                                            </button>
                                        @endif
                                        <form method="POST" action="{{ route('ssl.destroy', $cert) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" 
                                                    onclick="return confirm('Are you sure you want to delete this SSL certificate?')">
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
                {{ $certificates->links() }}
            </div>
        @else
            <div class="text-center py-4">
                <i class="fas fa-lock fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No SSL Certificates Found</h5>
                <p class="text-muted">Request your first SSL certificate to secure your domain.</p>
            </div>
        @endif
    </div>
</div>

<!-- Request SSL Certificate Modal -->
<div class="modal fade" id="addSslModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('ssl.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Request SSL Certificate</h5>
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
                        <label for="type" class="form-label">Certificate Type</label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="">Select Type</option>
                            <option value="lets_encrypt">Let's Encrypt (Free)</option>
                            <option value="self_signed">Self-Signed</option>
                            <option value="custom">Upload Custom</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="auto_renew" name="auto_renew" value="1" checked>
                            <label class="form-check-label" for="auto_renew">
                                Enable auto-renewal
                            </label>
                        </div>
                    </div>
                    <div id="customCertFields" style="display: none;">
                        <div class="mb-3">
                            <label for="certificate" class="form-label">Certificate (PEM format)</label>
                            <textarea class="form-control" id="certificate" name="certificate" rows="4" 
                                      placeholder="-----BEGIN CERTIFICATE-----"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="private_key" class="form-label">Private Key (PEM format)</label>
                            <textarea class="form-control" id="private_key" name="private_key" rows="4" 
                                      placeholder="-----BEGIN PRIVATE KEY-----"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="ca_bundle" class="form-label">CA Bundle (Optional)</label>
                            <textarea class="form-control" id="ca_bundle" name="ca_bundle" rows="3" 
                                      placeholder="-----BEGIN CERTIFICATE-----"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Request Certificate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View SSL Certificate Modal -->
<div class="modal fade" id="viewSslModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">SSL Certificate Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="certificateDetails">
                    <!-- Certificate details will be loaded here -->
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
document.getElementById('type').addEventListener('change', function() {
    const customFields = document.getElementById('customCertFields');
    if (this.value === 'custom') {
        customFields.style.display = 'block';
    } else {
        customFields.style.display = 'none';
    }
});

function viewCertificate(id) {
    // Fetch certificate details and populate view modal
    fetch(`/ssl/${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('certificateDetails').innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <strong>Domain:</strong> ${data.domain.name}<br>
                        <strong>Type:</strong> ${data.type}<br>
                        <strong>Issuer:</strong> ${data.issuer}<br>
                        <strong>Status:</strong> <span class="badge bg-${data.status === 'active' ? 'success' : 'warning'}">${data.status}</span><br>
                        <strong>Auto Renew:</strong> ${data.auto_renew ? 'Yes' : 'No'}
                    </div>
                    <div class="col-md-6">
                        <strong>Issued:</strong> ${data.issued_at || 'N/A'}<br>
                        <strong>Expires:</strong> ${data.expires_at || 'N/A'}<br>
                        <strong>Serial:</strong> ${data.serial_number || 'N/A'}<br>
                        <strong>Algorithm:</strong> ${data.signature_algorithm || 'N/A'}
                    </div>
                </div>
                ${data.certificate ? `
                <div class="mt-3">
                    <strong>Certificate:</strong>
                    <pre class="bg-light p-2 mt-2" style="max-height: 200px; overflow-y: auto;">${data.certificate}</pre>
                </div>
                ` : ''}
            `;
        });
}

function renewCertificate(id) {
    if (confirm('Are you sure you want to renew this certificate?')) {
        fetch(`/ssl/${id}/renew`, {
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
                alert('Failed to renew certificate: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error renewing certificate');
        });
    }
}
</script>
@endsection
