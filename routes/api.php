<?php

// use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// Dossier Inscription
// Route::post('login',[\App\Http\Controllers\Auth\AuthController::class,'login']);
Route::prefix('auth')->group(function () {
    Route::post('inscription',[\App\Http\Controllers\Apiv1\AuthController::class,'inscription']);
    Route::post('configurer-code-pin',[\App\Http\Controllers\Apiv1\AuthController::class,'definirCodePIN']);
    Route::post('se-connecter',[\App\Http\Controllers\Apiv1\AuthController::class,'connexion']);
    Route::post('valider-connexion',[\App\Http\Controllers\Apiv1\AuthController::class,'confirmerConnexion']);

    // Routes OTP
    Route::prefix('otp')->group(function () {
        Route::post('verifier', [\App\Http\Controllers\Apiv1\AuthController::class, 'verificationOtp']);
        Route::post('renvoyer', [\App\Http\Controllers\Apiv1\AuthController::class, 'renvoyerCodeOtp']);
    });
});





