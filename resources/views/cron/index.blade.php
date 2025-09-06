@extends('layouts.panel')

@section('title', 'Cron Jobs')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Cron Jobs</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCronModal">
            <i class="fas fa-plus me-2"></i>Create Cron Job
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

<!-- Cron Jobs Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Jobs</h6>
                        <h4>{{ $cronJobs->count() }}</h4>
                    </div>
                    <i class="fas fa-clock fa-2x opacity-75"></i>
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
                        <h4>{{ $cronJobs->where('is_active', true)->count() }}</h4>
                    </div>
                    <i class="fas fa-play-circle fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Successful Runs</h6>
                        <h4>{{ $cronJobs->sum('success_count') }}</h4>
                    </div>
                    <i class="fas fa-check-circle fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Failed Runs</h6>
                        <h4>{{ $cronJobs->sum('failure_count') }}</h4>
                    </div>
                    <i class="fas fa-exclamation-triangle fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cron Jobs Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Cron Jobs</h5>
    </div>
    <div class="card-body">
        @if($cronJobs->count() > 0)
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Schedule</th>
                            <th>Command</th>
                            <th>Next Run</th>
                            <th>Last Run</th>
                            <th>Success/Fail</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cronJobs as $job)
                            <tr>
                                <td><strong>{{ $job->name }}</strong></td>
                                <td>
                                    <code>{{ $job->schedule }}</code>
                                    <br><small class="text-muted">{{ $job->getScheduleDescription() }}</small>
                                </td>
                                <td>
                                    <code class="text-break">{{ Str::limit($job->command, 50) }}</code>
                                    @if(strlen($job->command) > 50)
                                        <button type="button" class="btn btn-sm btn-link p-0" 
                                                onclick="showFullCommand('{{ addslashes($job->command) }}')"
                                                title="View full command">
                                            <i class="fas fa-expand-alt"></i>
                                        </button>
                                    @endif
                                </td>
                                <td>
                                    @if($job->next_run_at)
                                        {{ $job->next_run_at->format('M j, Y H:i') }}
                                        <br><small class="text-muted">{{ $job->next_run_at->diffForHumans() }}</small>
                                    @else
                                        <span class="text-muted">Not scheduled</span>
                                    @endif
                                </td>
                                <td>
                                    @if($job->last_run_at)
                                        {{ $job->last_run_at->format('M j, Y H:i') }}
                                        <br><small class="text-muted">{{ $job->last_run_at->diffForHumans() }}</small>
                                    @else
                                        <span class="text-muted">Never</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-success">{{ $job->success_count }}</span> / 
                                    <span class="badge bg-danger">{{ $job->failure_count }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $job->is_active ? 'success' : 'secondary' }}">
                                        {{ $job->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-primary" 
                                                onclick="editCronJob({{ $job->id }})"
                                                data-bs-toggle="modal" data-bs-target="#editCronModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-success" 
                                                onclick="runCronJob({{ $job->id }})"
                                                title="Run now">
                                            <i class="fas fa-play"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-info" 
                                                onclick="viewLogs({{ $job->id }})"
                                                data-bs-toggle="modal" data-bs-target="#logsModal">
                                            <i class="fas fa-file-alt"></i>
                                        </button>
                                        <form method="POST" action="{{ route('cron.destroy', $job) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" 
                                                    onclick="return confirm('Are you sure you want to delete this cron job?')">
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
                {{ $cronJobs->links() }}
            </div>
        @else
            <div class="text-center py-4">
                <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No Cron Jobs Found</h5>
                <p class="text-muted">Create your first cron job to automate tasks.</p>
            </div>
        @endif
    </div>
</div>

<!-- Create Cron Job Modal -->
<div class="modal fade" id="addCronModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('cron.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Create Cron Job</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Job Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="command" class="form-label">Command</label>
                        <textarea class="form-control" id="command" name="command" rows="3" required
                                  placeholder="php /path/to/your/script.php"></textarea>
                        <div class="form-text">Enter the full command to execute</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Schedule</label>
                        <div class="row">
                            <div class="col-md-6">
                                <select class="form-select" id="schedule_preset" onchange="setSchedule()">
                                    <option value="">Custom Schedule</option>
                                    <option value="* * * * *">Every minute</option>
                                    <option value="0 * * * *">Every hour</option>
                                    <option value="0 0 * * *">Daily at midnight</option>
                                    <option value="0 0 * * 0">Weekly (Sunday)</option>
                                    <option value="0 0 1 * *">Monthly</option>
                                    <option value="0 0 1 1 *">Yearly</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control" id="schedule" name="schedule" 
                                       placeholder="* * * * *" required>
                            </div>
                        </div>
                        <div class="form-text">
                            Format: minute hour day month weekday
                            <a href="#" onclick="showCronHelp()" class="ms-2">Need help?</a>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="timeout_seconds" class="form-label">Timeout (seconds)</label>
                                <input type="number" class="form-control" id="timeout_seconds" name="timeout_seconds" 
                                       value="300" min="1" max="3600">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="max_retries" class="form-label">Max Retries</label>
                                <input type="number" class="form-control" id="max_retries" name="max_retries" 
                                       value="0" min="0" max="5">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="email_output" class="form-label">Email Output To</label>
                        <input type="email" class="form-control" id="email_output" name="email_output" 
                               placeholder="Leave empty to disable email notifications">
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
                    <button type="submit" class="btn btn-primary">Create Job</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Cron Job Modal -->
<div class="modal fade" id="editCronModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="editCronForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Cron Job</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Job Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_command" class="form-label">Command</label>
                        <textarea class="form-control" id="edit_command" name="command" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_schedule" class="form-label">Schedule</label>
                        <input type="text" class="form-control" id="edit_schedule" name="schedule" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_timeout_seconds" class="form-label">Timeout (seconds)</label>
                                <input type="number" class="form-control" id="edit_timeout_seconds" name="timeout_seconds">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_max_retries" class="form-label">Max Retries</label>
                                <input type="number" class="form-control" id="edit_max_retries" name="max_retries">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email_output" class="form-label">Email Output To</label>
                        <input type="email" class="form-control" id="edit_email_output" name="email_output">
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
                    <button type="submit" class="btn btn-primary">Update Job</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Logs Modal -->
<div class="modal fade" id="logsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cron Job Logs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="cronLogs">
                    <!-- Logs will be loaded here -->
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
function setSchedule() {
    const preset = document.getElementById('schedule_preset').value;
    if (preset) {
        document.getElementById('schedule').value = preset;
    }
}

function showCronHelp() {
    alert(`Cron Schedule Format:
    
* * * * *
│ │ │ │ │
│ │ │ │ └─── Weekday (0-7, Sunday = 0 or 7)
│ │ │ └───── Month (1-12)
│ │ └─────── Day (1-31)
│ └───────── Hour (0-23)
└─────────── Minute (0-59)

Examples:
0 0 * * * - Daily at midnight
0 */6 * * * - Every 6 hours
30 2 * * 1 - Every Monday at 2:30 AM
0 0 1 * * - First day of every month`);
}

function showFullCommand(command) {
    alert('Full Command:\n\n' + command);
}

function editCronJob(id) {
    fetch(`/cron/${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('editCronForm').action = `/cron/${id}`;
            document.getElementById('edit_name').value = data.name;
            document.getElementById('edit_command').value = data.command;
            document.getElementById('edit_schedule').value = data.schedule;
            document.getElementById('edit_timeout_seconds').value = data.timeout_seconds;
            document.getElementById('edit_max_retries').value = data.max_retries;
            document.getElementById('edit_email_output').value = data.email_output || '';
            document.getElementById('edit_is_active').checked = data.is_active;
        });
}

function runCronJob(id) {
    if (confirm('Are you sure you want to run this cron job now?')) {
        fetch(`/cron/${id}/run`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Cron job executed successfully');
                location.reload();
            } else {
                alert('Failed to execute cron job: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error executing cron job');
        });
    }
}

function viewLogs(id) {
    fetch(`/cron/${id}/logs`)
        .then(response => response.json())
        .then(data => {
            let logsHtml = '<div class="table-responsive"><table class="table table-sm">';
            logsHtml += '<thead><tr><th>Date</th><th>Status</th><th>Output</th><th>Duration</th></tr></thead><tbody>';
            
            if (data.logs && data.logs.length > 0) {
                data.logs.forEach(log => {
                    logsHtml += `<tr>
                        <td>${log.created_at}</td>
                        <td><span class="badge bg-${log.status === 'success' ? 'success' : 'danger'}">${log.status}</span></td>
                        <td><pre class="mb-0" style="max-height: 100px; overflow-y: auto;">${log.output || 'No output'}</pre></td>
                        <td>${log.duration}s</td>
                    </tr>`;
                });
            } else {
                logsHtml += '<tr><td colspan="4" class="text-center text-muted">No logs found</td></tr>';
            }
            
            logsHtml += '</tbody></table></div>';
            document.getElementById('cronLogs').innerHTML = logsHtml;
        });
}
</script>
@endsection
