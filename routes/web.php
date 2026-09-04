<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Toutes les routes web grand public sont soumises au kill switch de la plateforme
// et au contrôle indépendant d'activation de la surface "Site Web".
Route::middleware(['plateforme.actif', 'plateforme.surface:SITE_WEB'])->group(function () {
    Auth::routes();

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::inertia('dashboard', 'dashboard')->name('dashboard');
    });

    Route::get('/', [App\Http\Controllers\IndexController::class, 'index'])->name('index');
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

    require __DIR__.'/settings.php';
});

