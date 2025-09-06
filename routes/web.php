<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\DatabaseController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\DnsController;
use App\Http\Controllers\SslController;
use App\Http\Controllers\FtpController;
use App\Http\Controllers\CronController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\AutoInstallerController;
use App\Http\Controllers\Admin\CacheController;
use App\Http\Controllers\Admin\PluginController;
use App\Http\Controllers\ResellerController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\IPManagementController;
use App\Http\Controllers\Admin\ServerManagerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
});

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// API routes for real-time data
Route::prefix('api')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);
    Route::get('/system/status', [DashboardController::class, 'getSystemStatus']);
});

// Profile routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// User Panel Routes - Protected by auth and verified middleware
Route::middleware(['auth', 'verified'])->group(function () {
    
    // Domain Management
    Route::resource('domains', DomainController::class);
    Route::post('/domains/{domain}/dns', [DomainController::class, 'updateDns'])->name('domains.dns');

    // Database Management  
    Route::resource('databases', DatabaseController::class);
    Route::get('/databases/{database}/export', [DatabaseController::class, 'export'])->name('databases.export');

    // File Management
    Route::get('/files', [FileController::class, 'index'])->name('files.index');
    Route::get('/files/editor', [FileController::class, 'editor'])->name('files.editor');
    Route::post('/files/save', [FileController::class, 'saveFile'])->name('files.save');
    Route::post('/files/upload', [FileController::class, 'upload'])->name('files.upload');
    Route::post('/files/create-directory', [FileController::class, 'createDirectory'])->name('files.create-directory');
    Route::get('/files/{file}/download', [FileController::class, 'download'])->name('files.download');
    Route::delete('/files/{file}', [FileController::class, 'destroy'])->name('files.destroy');

    // Email Management
    Route::resource('emails', EmailController::class);
    Route::post('/emails/{email}/test', [EmailController::class, 'test'])->name('emails.test');

    // Statistics
    Route::get('/stats', [StatsController::class, 'index'])->name('stats.index');
    Route::get('/stats/data', [StatsController::class, 'getData'])->name('stats.data');

    // Auto Installer Routes
    Route::prefix('auto-installer')->name('auto-installer.')->group(function () {
        Route::get('/', [AutoInstallerController::class, 'index'])->name('index');
        Route::get('/apps', [AutoInstallerController::class, 'apps'])->name('apps');
        Route::get('/apps/{appSlug}', [AutoInstallerController::class, 'show'])->name('show');
        Route::post('/install', [AutoInstallerController::class, 'install'])->name('install');
        Route::delete('/uninstall/{installation}', [AutoInstallerController::class, 'uninstall'])->name('uninstall');
        Route::post('/update/{installation}', [AutoInstallerController::class, 'update'])->name('update');
        Route::post('/backup/{installation}', [AutoInstallerController::class, 'backup'])->name('backup');
        Route::get('/logs/{installation}', [AutoInstallerController::class, 'logs'])->name('logs');
    });

    // DNS Management
    Route::get('/dns', [DnsController::class, 'index'])->name('dns.index');
    Route::post('/dns/create-zone', [DnsController::class, 'createZone'])->name('dns.create-zone');

    // SSL Certificate Management
    Route::get('/ssl', [SslController::class, 'index'])->name('ssl.index');
    Route::post('/ssl/generate', [SslController::class, 'generate'])->name('ssl.generate');

    // FTP Account Management
    Route::resource('ftp', FtpController::class);
    Route::post('/ftp/{ftp}/test-connection', [FtpController::class, 'testConnection'])->name('ftp.test');

    // Cron Job Management
    Route::resource('cron', CronController::class);
    Route::post('/cron/{cron}/run', [CronController::class, 'run'])->name('cron.run');

    // Backup Management
    Route::get('/backups', [BackupController::class, 'index'])->name('backups.index');
    Route::get('/backups/create', [BackupController::class, 'create'])->name('backups.create');
    Route::post('/backups', [BackupController::class, 'store'])->name('backups.store');
    Route::get('/backups/{backup}', [BackupController::class, 'show'])->name('backups.show');
    Route::post('/backups/{backup}/restore', [BackupController::class, 'restore'])->name('backups.restore');
    Route::post('/backups/schedule', [BackupController::class, 'schedule'])->name('backups.schedule');
    Route::delete('/backups/{backup}', [BackupController::class, 'destroy'])->name('backups.destroy');
    Route::get('/backups/{backup}/download', [BackupController::class, 'download'])->name('backups.download');

    // Two-Factor Authentication routes
    Route::get('/2fa', [TwoFactorController::class, 'show'])->name('2fa.show');
    Route::post('/2fa/enable', [TwoFactorController::class, 'enable'])->name('2fa.enable');
    Route::post('/2fa/disable', [TwoFactorController::class, 'disable'])->name('2fa.disable');
});

// Package Management (Admin only)
Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::resource('packages', PackageController::class);
    Route::get('/packages/{package}/users', [PackageController::class, 'getUsers'])->name('packages.users');
    Route::post('/packages/{package}/duplicate', [PackageController::class, 'duplicate'])->name('packages.duplicate');
});

// Admin Panel Routes - Protected by admin middleware
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    
    // Admin Dashboard
    Route::get('/', [AdminController::class, 'index'])->name('index');

    // User Management
    Route::resource('users', AdminController::class);
    Route::post('/users/{user}/toggle-status', [AdminController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('/users/{user}/reset-password', [AdminController::class, 'resetPassword'])->name('users.reset-password');
    Route::post('/users/{user}/assign-package', [PackageController::class, 'assignToUser'])->name('users.assign-package');
    
    // Cache & Performance Management
    Route::get('/cache', [CacheController::class, 'index'])->name('cache.index');
    Route::post('/cache/clear', [CacheController::class, 'clearCache'])->name('cache.clear');
    Route::post('/cache/optimize', [CacheController::class, 'optimize'])->name('cache.optimize');
    Route::get('/cache/stats', [CacheController::class, 'stats'])->name('cache.stats');

    // Plugin Management
    Route::resource('plugins', PluginController::class)->only(['index', 'show']);
    Route::post('/plugins/check-updates', [PluginController::class, 'checkUpdates'])->name('plugins.check-updates');
    Route::post('/plugins/{plugin}/update', [PluginController::class, 'update'])->name('plugins.update');
    Route::post('/plugins/{plugin}/toggle', [PluginController::class, 'toggleStatus'])->name('plugins.toggle');
    Route::post('/plugins/bulk-update', [PluginController::class, 'bulkUpdate'])->name('plugins.bulk-update');
    Route::get('/plugins/comments', [PluginController::class, 'getComments'])->name('plugins.comments');
    Route::post('/plugins/comments/{comment}/resolve', [PluginController::class, 'resolveComment'])->name('plugins.comments.resolve');
    Route::post('/plugins/comments/{comment}/dismiss', [PluginController::class, 'dismissComment'])->name('plugins.comments.dismiss');

    // Server Manager
    Route::prefix('server')->name('server.')->group(function () {
        Route::get('/', [ServerManagerController::class, 'index'])->name('index');
        Route::get('/admin-settings', [ServerManagerController::class, 'adminSettings'])->name('admin-settings');
        Route::post('/admin-settings', [ServerManagerController::class, 'updateAdminSettings'])->name('update-admin-settings');
        Route::get('/httpd-config', [ServerManagerController::class, 'httpdConfig'])->name('httpd-config');
        Route::get('/dns-admin', [ServerManagerController::class, 'dnsAdmin'])->name('dns-admin');
        Route::get('/ip-management', [ServerManagerController::class, 'ipManagement'])->name('ip-management');
        Route::get('/multi-server', [ServerManagerController::class, 'multiServer'])->name('multi-server');
        Route::get('/php-config', [ServerManagerController::class, 'phpConfig'])->name('php-config');
        Route::get('/tls-certificate', [ServerManagerController::class, 'tlsCertificate'])->name('tls-certificate');
        Route::get('/security-txt', [ServerManagerController::class, 'securityTxt'])->name('security-txt');
        Route::get('/system-packages', [ServerManagerController::class, 'systemPackages'])->name('system-packages');
    });

    // Service Management (DirectAdmin-style)
    Route::prefix('services')->name('services.')->group(function () {
        Route::get('/', [ServiceController::class, 'index'])->name('index');
        Route::post('/start', [ServiceController::class, 'start'])->name('start');
        Route::post('/stop', [ServiceController::class, 'stop'])->name('stop');
        Route::post('/restart', [ServiceController::class, 'restart'])->name('restart');
        Route::get('/status', [ServiceController::class, 'status'])->name('status');
    });

    // IP Management (DirectAdmin-style)
    Route::prefix('ip-management')->name('ip.')->group(function () {
        Route::get('/', [IPManagementController::class, 'index'])->name('index');
        Route::post('/assign', [IPManagementController::class, 'assign'])->name('assign');
        Route::post('/unassign', [IPManagementController::class, 'unassign'])->name('unassign');
        Route::post('/add', [IPManagementController::class, 'add'])->name('add');
        Route::delete('/remove', [IPManagementController::class, 'remove'])->name('remove');
        Route::get('/stats', [IPManagementController::class, 'stats'])->name('stats');
        Route::get('/users', [IPManagementController::class, 'getUsers'])->name('users');
    });
});

// Reseller Management Routes
Route::middleware(['auth', 'verified'])->prefix('reseller')->name('reseller.')->group(function () {
    Route::get('/dashboard', [ResellerController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [ResellerController::class, 'myUsers'])->name('users');
    Route::get('/users/create', [ResellerController::class, 'createUser'])->name('users.create');
    Route::post('/users', [ResellerController::class, 'storeUser'])->name('users.store');
});

// Authentication routes
require __DIR__.'/auth.php';
