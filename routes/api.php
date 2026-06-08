<?php

// use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


//============================================ API FRONT =========================================

Route::prefix('auth')->group(function () {
    Route::post('inscription',[\App\Http\Controllers\Apiv1\AuthController::class,'inscription']);
    Route::post('configurer-code-pin',[\App\Http\Controllers\Apiv1\AuthController::class,'definirCodePIN']);
    Route::post('se-connecter',[\App\Http\Controllers\Apiv1\AuthController::class,'connexion']);
    Route::post('valider-connexion',[\App\Http\Controllers\Apiv1\AuthController::class,'confirmerConnexion']);

    Route::post('connexion',[\App\Http\Controllers\Apiv1\AuthController::class,'connexion']);
    
    
    // Routes OTP
    Route::prefix('otp')->group(function () {
        Route::post('verifier', [\App\Http\Controllers\Apiv1\AuthController::class, 'verificationOtp']);
        Route::post('renvoyer', [\App\Http\Controllers\Apiv1\AuthController::class, 'renvoyerCodeOtp']);
        Route::post('confirmerConnexion',[\App\Http\Controllers\Apiv1\AuthController::class,'confirmerConnexion']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('espace-utilisateur')->group(function () {
        Route::get('details',[\App\Http\Controllers\Apiv1\UserController::class,'infosUtilisateurConnecte']);
    });
});
//============================================ /FIN API FRONT =========================================



//============================================ API PANEL ADMINISTRATION =========================================
Route::prefix('administration')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('se-connecter',[\App\Http\Controllers\Apiv1\Admin\AuthController::class,'connexion']);        
    });
    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('panel-admin')->group(function () {
            Route::post('se-deconnecter',[\App\Http\Controllers\Apiv1\Admin\AuthController::class,'deconnexion']);
            Route::get('recuperation-info-profil',[\App\Http\Controllers\Apiv1\Admin\AuthController::class,'recupererInfoProfil']);
        });
    });
});





