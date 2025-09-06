<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <title>@yield('title', 'Panel Hosting')</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .sidebar {
            min-height: calc(100vh - 56px);
            box-shadow: 0 0 35px 0 rgba(154, 161, 171, 0.15);
        }
        .sidebar .nav-link {
            color: #495057;
            border-radius: 0.375rem;
            margin: 0.125rem 0;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: #e9ecef;
            color: #212529;
        }
        .main-content {
            background-color: #f8f9fa;
        }
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid rgba(0, 0, 0, 0.125);
        }
        .stats-card {
            border-left: 4px solid #007bff;
        }
        .stats-card.domains {
            border-left-color: #28a745;
        }
        .stats-card.databases {
            border-left-color: #ffc107;
        }
        .stats-card.emails {
            border-left-color: #dc3545;
        }
        .stats-card.files {
            border-left-color: #6f42c1;
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <i class="fas fa-server me-2"></i>Panel Hosting
            </a>

            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i>{{ Auth::user()->name }}
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="fas fa-user-edit me-2"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item"><i class="fas fa-sign-out-alt me-2"></i>Logout</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-white sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('domains.*') ? 'active' : '' }}" href="{{ route('domains.index') }}">
                                <i class="fas fa-globe me-2"></i>Domain
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('databases.*') ? 'active' : '' }}" href="{{ route('databases.index') }}">
                                <i class="fas fa-database me-2"></i>Database
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('files.*') ? 'active' : '' }}" href="{{ route('files.index') }}">
                                <i class="fas fa-folder me-2"></i>File Manager
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('emails.*') ? 'active' : '' }}" href="{{ route('emails.index') }}">
                                <i class="fas fa-envelope me-2"></i>Email
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('stats.*') ? 'active' : '' }}" href="{{ route('stats.index') }}">
                                <i class="fas fa-chart-bar me-2"></i>Statistik
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('auto-installer.*') ? 'active' : '' }}" href="{{ route('auto-installer.index') }}">
                                <i class="fas fa-magic me-2"></i>Auto Installer
                            </a>
                        </li>
                        
                        <!-- Advanced Features -->
                        <li class="nav-item mt-3">
                            <h6 class="nav-header text-muted px-3">Advanced Features</h6>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('dns.*') ? 'active' : '' }}" href="{{ route('dns.index') }}">
                                <i class="fas fa-network-wired me-2"></i>DNS Management
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('ssl.*') ? 'active' : '' }}" href="{{ route('ssl.index') }}">
                                <i class="fas fa-lock me-2"></i>SSL Certificate
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('ftp.*') ? 'active' : '' }}" href="{{ route('ftp.index') }}">
                                <i class="fas fa-upload me-2"></i>FTP Accounts
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('cron.*') ? 'active' : '' }}" href="{{ route('cron.index') }}">
                                <i class="fas fa-clock me-2"></i>Cron Jobs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('backups.*') ? 'active' : '' }}" href="{{ route('backups.index') }}">
                                <i class="fas fa-download me-2"></i>Backup
                            </a>
                        </li>
                        
                        <!-- Security -->
                        <li class="nav-item mt-3">
                            <h6 class="nav-header text-muted px-3">Security</h6>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('2fa.*') ? 'active' : '' }}" href="{{ route('2fa.show') }}">
                                <i class="fas fa-shield-alt me-2"></i>Two-Factor Auth
                            </a>
                        </li>
                        
                        @if(Auth::user() && Auth::user()->role === 'admin')
                        <!-- Admin Features -->
                        <li class="nav-item mt-3">
                            <h6 class="nav-header text-muted px-3">Admin Panel</h6>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.index') ? 'active' : '' }}" href="{{ route('admin.index') }}">
                                <i class="fas fa-user-shield me-2"></i>User Management
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('packages.*') ? 'active' : '' }}" href="{{ route('packages.index') }}">
                                <i class="fas fa-box me-2"></i>Package Management
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.cache.*') ? 'active' : '' }}" href="{{ route('admin.cache.index') }}">
                                <i class="fas fa-tachometer-alt me-2"></i>Cache & Performance
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('admin/plugins*') ? 'active' : '' }}" href="{{ route('admin.plugins.index') }}">
                                <i class="fas fa-puzzle-piece me-2"></i>Plugin Manager
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('admin/server*') ? 'active' : '' }}" href="{{ route('admin.server.index') }}">
                                <i class="fas fa-server me-2"></i>Server Manager
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="pt-3 pb-2 mb-3">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    @yield('scripts')
</body>
</html>
