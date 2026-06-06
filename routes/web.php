<?php

use App\Http\Controllers\Admin\AdminPanelController;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController as AdminAuthenticatedSessionController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

// Route::inertia('/', 'welcome', [
//     'canRegister' => Features::enabled(Features::registration()),
// ])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';

Auth::routes();

Route::get('/', [App\Http\Controllers\IndexController::class, 'index'])->name('index');
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::prefix('admin')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('login', [AdminAuthenticatedSessionController::class, 'create'])->name('admin.login');
        Route::post('login', [AdminAuthenticatedSessionController::class, 'store'])->name('admin.login.store');
    });

    Route::middleware(['auth:admin', 'role_or_permission:super-admin|admin.access'])->group(function () {
        Route::post('logout', [AdminAuthenticatedSessionController::class, 'destroy'])->name('admin.logout');

        Route::get('dashboard', [AdminPanelController::class, 'dashboard'])
            ->name('admin.dashboard');

        Route::get('kyc', [AdminPanelController::class, 'kyc'])
            ->middleware('permission:kyc.review')
            ->name('admin.kyc.index');

        Route::get('operations', [AdminPanelController::class, 'operations'])
            ->middleware('permission:operations.view')
            ->name('admin.operations.index');

        Route::get('audit', [AdminPanelController::class, 'audit'])
            ->middleware('permission:audit.view')
            ->name('admin.audit.index');

        Route::get('settings', [AdminPanelController::class, 'settings'])
            ->middleware('permission:settings.manage')
            ->name('admin.settings.index');

        Route::get('roles', [AdminPanelController::class, 'roles'])
            ->middleware('permission:roles.manage')
            ->name('admin.roles.index');

        Route::get('permissions', [AdminPanelController::class, 'permissions'])
            ->middleware('permission:permissions.manage')
            ->name('admin.permissions.index');
    });
});
