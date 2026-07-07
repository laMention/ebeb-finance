<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Toutes les routes web grand public sont soumises au kill switch de la plateforme.
Route::middleware('plateforme.actif')->group(function () {
    Auth::routes();

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::inertia('dashboard', 'dashboard')->name('dashboard');
    });

    Route::get('/', [App\Http\Controllers\IndexController::class, 'index'])->name('index');
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

    require __DIR__.'/settings.php';
});

