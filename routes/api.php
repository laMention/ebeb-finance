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

// Webhook paiements (public — validé par X-Webhook-Secret)
Route::prefix('paiements')->group(function () {
    Route::post('webhook', [\App\Http\Controllers\Apiv1\PaiementEntrantController::class, 'webhook']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('espace-utilisateur')->group(function () {
        Route::get('details',[\App\Http\Controllers\Apiv1\UserController::class,'infosUtilisateurConnecte']);
        Route::patch('profil', [\App\Http\Controllers\Apiv1\UserController::class, 'mettreAjourProfil']);
        Route::patch('code-pin', [\App\Http\Controllers\Apiv1\UserController::class, 'mettreAjourCodePin']);
        Route::post('se-deconnecter',[\App\Http\Controllers\Apiv1\UserController::class,'deconnexion']);
        
        // Paiements reçus
        Route::prefix('paiements')->group(function () {
            Route::get('/', [\App\Http\Controllers\Apiv1\PaiementEntrantController::class, 'index']);
            Route::get('/{paiementId}', [\App\Http\Controllers\Apiv1\PaiementEntrantController::class, 'show']);
        });

        // Opérations / historique des transactions
        Route::prefix('operations')->group(function () {
            Route::get('/', [\App\Http\Controllers\Apiv1\OperationController::class, 'index']);
            Route::get('/{operation}', [\App\Http\Controllers\Apiv1\OperationController::class, 'show']);
        });
    
        // Règles de prélèvement
        Route::prefix('regle-prelevements')->group(function () {
            Route::get('/', [\App\Http\Controllers\Apiv1\ReglePrelevementController::class, 'index']);
            Route::get('/types', [\App\Http\Controllers\Apiv1\ReglePrelevementController::class, 'types']);
            Route::post('/configurer-regle-prelevement', [\App\Http\Controllers\Apiv1\ReglePrelevementController::class, 'configurerRegleTypeCotisation']);
            Route::post('/configurer', [\App\Http\Controllers\Apiv1\ReglePrelevementController::class, 'configurer']);
            Route::post('/reordonner', [\App\Http\Controllers\Apiv1\ReglePrelevementController::class, 'reordonner']);
            Route::get('/{reglePrelevement}', [\App\Http\Controllers\Apiv1\ReglePrelevementController::class, 'show']);
            Route::delete('/{reglePrelevement}', [\App\Http\Controllers\Apiv1\ReglePrelevementController::class, 'destroy']);
            Route::patch('/{reglePrelevement}/statut', [\App\Http\Controllers\Apiv1\ReglePrelevementController::class, 'basculerStatut']);
        });

        // Comptes Mobile Money utilisateur
        Route::prefix('comptes-mobile-money')->group(function () {
            Route::get('/', [\App\Http\Controllers\Apiv1\CompteMobileMoneyController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Apiv1\CompteMobileMoneyController::class, 'store']);
            Route::patch('/{compteMobileMoney}/principal', [\App\Http\Controllers\Apiv1\CompteMobileMoneyController::class, 'definirPrincipal']);
        });

        // Objectif d'épargne
        Route::prefix('objectif-epargne')->group(function () {
            Route::get('/', [\App\Http\Controllers\Apiv1\ObjectifEpargneController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Apiv1\ObjectifEpargneController::class, 'store']);
            Route::patch('/{objectifEpargne}', [\App\Http\Controllers\Apiv1\ObjectifEpargneController::class, 'update']);
            Route::delete('/{objectifEpargne}', [\App\Http\Controllers\Apiv1\ObjectifEpargneController::class, 'destroy']);
        });

        // Types de cotisations personnalisés
        Route::prefix('types-cotisation-personnalises')->group(function () {
            Route::get('/', [\App\Http\Controllers\Apiv1\TypeCotisationPersonnaliseeController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Apiv1\TypeCotisationPersonnaliseeController::class, 'store']);
            Route::get('/{typeCotisation}', [\App\Http\Controllers\Apiv1\TypeCotisationPersonnaliseeController::class, 'show']);
            Route::put('/{typeCotisation}', [\App\Http\Controllers\Apiv1\TypeCotisationPersonnaliseeController::class, 'update']);
            Route::delete('/{typeCotisation}', [\App\Http\Controllers\Apiv1\TypeCotisationPersonnaliseeController::class, 'destroy']);
        });

        // Notifications
        Route::prefix('notifications')->group(function () {
            Route::get('/', [\App\Http\Controllers\Apiv1\NotificationController::class, 'index']);
            Route::get('/non-lues', [\App\Http\Controllers\Apiv1\NotificationController::class, 'nombreNonLues']);
            Route::patch('/marquer-toutes-lues', [\App\Http\Controllers\Apiv1\NotificationController::class, 'marquerToutesLues']);
            Route::patch('/{notification}/lue', [\App\Http\Controllers\Apiv1\NotificationController::class, 'marquerLue']);
        });

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
            Route::post('verifier-document',[\App\Http\Controllers\Apiv1\Admin\UserController::class,'verificationDocument']);
            Route::post('mise-a-jour-document/{documentKYC}',[\App\Http\Controllers\Apiv1\Admin\UserController::class,'mettreAjourDocument']);

            // Validation KYC
            Route::prefix('kyc')->group(function () {
                Route::get('/', [\App\Http\Controllers\Apiv1\Admin\KycController::class, 'index']);
                Route::patch('/{documentKYC}/approuver', [\App\Http\Controllers\Apiv1\Admin\KycController::class, 'approuver']);
                Route::patch('/{documentKYC}/rejeter', [\App\Http\Controllers\Apiv1\Admin\KycController::class, 'rejeter']);
            });

            // Gestion des utilisateurs (travailleurs indépendants)
            Route::prefix('utilisateurs')->group(function () {
                Route::get('/', [\App\Http\Controllers\Apiv1\Admin\GestionUtilisateurController::class, 'index']);
                Route::get('/{user}', [\App\Http\Controllers\Apiv1\Admin\GestionUtilisateurController::class, 'show']);
                Route::patch('/{user}/suspendre', [\App\Http\Controllers\Apiv1\Admin\GestionUtilisateurController::class, 'suspendre']);
                Route::patch('/{user}/reactiver', [\App\Http\Controllers\Apiv1\Admin\GestionUtilisateurController::class, 'reactiver']);
                Route::patch('/{user}/infos-admin', [\App\Http\Controllers\Apiv1\Admin\GestionUtilisateurController::class, 'mettreAjourInfosAdmin']);
                Route::patch('/{user}/reinitialiser-pin', [\App\Http\Controllers\Apiv1\Admin\GestionUtilisateurController::class, 'reinitialiserCodePin']);
                Route::delete('/{user}', [\App\Http\Controllers\Apiv1\Admin\GestionUtilisateurController::class, 'archiver']);
            });

            // Types de cotisation
            Route::prefix('types-cotisation')->group(function () {
                Route::get('/', [\App\Http\Controllers\Apiv1\Admin\TypeCotisationController::class, 'index']);
                Route::post('/', [\App\Http\Controllers\Apiv1\Admin\TypeCotisationController::class, 'store']);
                Route::get('/{typeCotisation}', [\App\Http\Controllers\Apiv1\Admin\TypeCotisationController::class, 'show']);
                Route::put('/{typeCotisation}', [\App\Http\Controllers\Apiv1\Admin\TypeCotisationController::class, 'update']);
                Route::delete('/{typeCotisation}', [\App\Http\Controllers\Apiv1\Admin\TypeCotisationController::class, 'destroy']);
                Route::patch('/{typeCotisation}/statut', [\App\Http\Controllers\Apiv1\Admin\TypeCotisationController::class, 'basculerStatut']);
            });

            // Moyens de paiement
            Route::prefix('moyens-paiement')->group(function () {
                Route::get('/', [\App\Http\Controllers\Apiv1\Admin\MoyenPaiementController::class, 'index']);
                Route::post('/', [\App\Http\Controllers\Apiv1\Admin\MoyenPaiementController::class, 'store']);
                Route::get('/{moyenPaiement}', [\App\Http\Controllers\Apiv1\Admin\MoyenPaiementController::class, 'show']);
                Route::match(['PUT', 'POST'],'/{moyenPaiement}', [\App\Http\Controllers\Apiv1\Admin\MoyenPaiementController::class, 'update']);
                Route::delete('/{moyenPaiement}', [\App\Http\Controllers\Apiv1\Admin\MoyenPaiementController::class, 'destroy']);
                Route::patch('/{moyenPaiement}/statut', [\App\Http\Controllers\Apiv1\Admin\MoyenPaiementController::class, 'basculerStatut']);
                Route::patch('/{moyenPaiement}/par-defaut', [\App\Http\Controllers\Apiv1\Admin\MoyenPaiementController::class, 'definirParDefaut']);
            });

            Route::prefix('parametres-globaux')->group(function () {
                Route::get('/', [\App\Http\Controllers\Apiv1\Admin\ParametreGlobalController::class, 'index']);
                Route::post('/', [\App\Http\Controllers\Apiv1\Admin\ParametreGlobalController::class, 'store']);
                Route::get('/{parametreGlobal}', [\App\Http\Controllers\Apiv1\Admin\ParametreGlobalController::class, 'show']);
                Route::put('/{parametreGlobal}', [\App\Http\Controllers\Apiv1\Admin\ParametreGlobalController::class, 'update']);
                Route::delete('/{parametreGlobal}', [\App\Http\Controllers\Apiv1\Admin\ParametreGlobalController::class, 'destroy']);
            });
        });
    });
});





