<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// ── Auth Routes (Breeze) ──────────────────────────────────
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('login');
});

require __DIR__ . '/auth.php';

// ── Admin Routes ──────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [Admin\ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [Admin\ProfileController::class, 'update'])->name('profile.update');

    // ── User Management (admin + super_admin) ─────────────
    Route::middleware('company_admin')->group(function () {

        // Users
        Route::resource('users', Admin\UserController::class);
        Route::patch('users/{user}/toggle-status', [Admin\UserController::class, 'toggleStatus'])->name('users.toggle-status');

        // Roles
        Route::resource('roles', Admin\RoleController::class);

        // Permissions (super_admin only)
        Route::middleware('super_admin')->group(function () {
            Route::resource('permissions', Admin\PermissionController::class)->only(['index','create','store','destroy']);

            // Companies
            Route::resource('companies', Admin\CompanyController::class);
        });

        // Audit Logs
        Route::get('audit-logs', [Admin\AuditLogController::class, 'index'])->name('audit-logs.index');
    });
});

// Old Breeze profile (keep for compatibility)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
