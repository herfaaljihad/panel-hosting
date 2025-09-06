@extends('layouts.panel')

@section('title', 'Statistik - Panel Hosting')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">Statistik & Monitoring</h1>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card domains">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Domain</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['domains'] }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-globe fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card databases">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Database</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['databases'] }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-database fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card emails">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Email</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['emails'] }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-envelope fa-2x text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card files">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Storage</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['storage_formatted'] }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-hdd fa-2x text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Pertumbuhan Resource (12 Bulan Terakhir)</h5>
            </div>
            <div class="card-body">
                <canvas id="resourceChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Distribusi Resource</h5>
            </div>
            <div class="card-body">
                <canvas id="pieChart" width="400" height="400"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Informasi Sistem</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Server:</strong></td>
                                <td>Apache/Nginx</td>
                            </tr>
                            <tr>
                                <td><strong>PHP Version:</strong></td>
                                <td>{{ phpversion() }}</td>
                            </tr>
                            <tr>
                                <td><strong>Laravel Version:</strong></td>
                                <td>{{ app()->version() }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Database:</strong></td>
                                <td>MySQL/SQLite</td>
                            </tr>
                            <tr>
                                <td><strong>Storage Used:</strong></td>
                                <td>{{ $stats['storage_formatted'] }}</td>
                            </tr>
                            <tr>
                                <td><strong>Account Type:</strong></td>
                                <td>
                                    <span class="badge {{ Auth::user()->isAdmin() ? 'bg-danger' : 'bg-primary' }}">
                                        {{ ucfirst(Auth::user()->role) }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Line Chart for Resource Growth
const ctx = document.getElementById('resourceChart').getContext('2d');
const resourceChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: @json($monthlyData['labels']),
        datasets: [{
            label: 'Domains',
            data: @json($monthlyData['domains']),
            borderColor: '#28a745',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            tension: 0.4
        }, {
            label: 'Databases',
            data: @json($monthlyData['databases']),
            borderColor: '#ffc107',
            backgroundColor: 'rgba(255, 193, 7, 0.1)',
            tension: 0.4
        }, {
            label: 'Emails',
            data: @json($monthlyData['emails']),
            borderColor: '#dc3545',
            backgroundColor: 'rgba(220, 53, 69, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Pie Chart for Resource Distribution
const ctx2 = document.getElementById('pieChart').getContext('2d');
const pieChart = new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: ['Domains', 'Databases', 'Emails'],
        datasets: [{
            data: [{{ $stats['domains'] }}, {{ $stats['databases'] }}, {{ $stats['emails'] }}],
            backgroundColor: ['#28a745', '#ffc107', '#dc3545']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});
</script>
@endsection
