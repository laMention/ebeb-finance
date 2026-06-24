<?php

// use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


//============================================ API FRONT =========================================

Route::prefix('auth')->middleware('throttle:10,1')->group(function () {
    Route::post('inscription',[\App\Http\Controllers\Apiv1\AuthController::class,'inscription']);
    Route::post('configurer-code-pin',[\App\Http\Controllers\Apiv1\AuthController::class,'definirCodePIN']);
    Route::post('se-connecter',[\App\Http\Controllers\Apiv1\AuthController::class,'connexion']);
    Route::post('valider-connexion',[\App\Http\Controllers\Apiv1\AuthController::class,'confirmerConnexion']);

    Route::post('connexion',[\App\Http\Controllers\Apiv1\AuthController::class,'connexion']);

    // Routes OTP
    Route::prefix('otp')->group(function () {
        Route::post('verifier', [\App\Http\Controllers\Apiv1\AuthController::class, 'verificationOtp']);
        Route::post('renvoyer', [\App\Http\Controllers\Apiv1\AuthController::class, 'renvoyerCodeOtp'])->middleware('throttle:3,1');
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
    // Public — branding plateforme (sans authentification)
    Route::get('public/infos-plateforme', [\App\Http\Controllers\Apiv1\Admin\ParametreGeneralController::class, 'infosPubliques'])
        ->middleware('throttle:60,1');

    Route::prefix('auth')->middleware('throttle:5,1')->group(function () {
        Route::post('se-connecter',[\App\Http\Controllers\Apiv1\Admin\AuthController::class,'connexion']);
    });
    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('panel-admin')->group(function () {
            Route::post('se-deconnecter',[\App\Http\Controllers\Apiv1\Admin\AuthController::class,'deconnexion']);
            Route::get('recuperation-info-profil',[\App\Http\Controllers\Apiv1\Admin\AuthController::class,'recupererInfoProfil']);
            Route::patch('profil',              [\App\Http\Controllers\Apiv1\Admin\AuthController::class, 'mettreAjourProfil']);
            Route::patch('profil/mot-de-passe', [\App\Http\Controllers\Apiv1\Admin\AuthController::class, 'changerMotDePasse']);
            Route::post('profil/photo',         [\App\Http\Controllers\Apiv1\Admin\AuthController::class, 'changerPhoto']);
            Route::post('verifier-document',[\App\Http\Controllers\Apiv1\Admin\UserController::class,'verificationDocument'])
                ->middleware('admin.perm:kyc.update');
            Route::post('mise-a-jour-document/{documentKYC}',[\App\Http\Controllers\Apiv1\Admin\UserController::class,'mettreAjourDocument'])
                ->middleware('admin.perm:kyc.update');

            // Validation KYC
            Route::prefix('kyc')->middleware('admin.perm:kyc.view')->group(function () {
                Route::get('/', [\App\Http\Controllers\Apiv1\Admin\KycController::class, 'index']);
                Route::patch('/{documentKYC}/approuver', [\App\Http\Controllers\Apiv1\Admin\KycController::class, 'approuver'])
                    ->middleware('admin.perm:kyc.validate');
                Route::patch('/{documentKYC}/rejeter', [\App\Http\Controllers\Apiv1\Admin\KycController::class, 'rejeter'])
                    ->middleware('admin.perm:kyc.reject');
            });

            // Gestion des utilisateurs (travailleurs indépendants)
            Route::prefix('utilisateurs')->middleware('admin.perm:utilisateurs.view')->group(function () {
                Route::get('/', [\App\Http\Controllers\Apiv1\Admin\GestionUtilisateurController::class, 'index']);
                Route::get('/{user}', [\App\Http\Controllers\Apiv1\Admin\GestionUtilisateurController::class, 'show']);
                Route::patch('/{user}/suspendre', [\App\Http\Controllers\Apiv1\Admin\GestionUtilisateurController::class, 'suspendre'])
                    ->middleware('admin.perm:utilisateurs.update');
                Route::patch('/{user}/reactiver', [\App\Http\Controllers\Apiv1\Admin\GestionUtilisateurController::class, 'reactiver'])
                    ->middleware('admin.perm:utilisateurs.update');
                Route::patch('/{user}/infos-admin', [\App\Http\Controllers\Apiv1\Admin\GestionUtilisateurController::class, 'mettreAjourInfosAdmin'])
                    ->middleware('admin.perm:utilisateurs.update');
                Route::patch('/{user}/reinitialiser-pin', [\App\Http\Controllers\Apiv1\Admin\GestionUtilisateurController::class, 'reinitialiserCodePin'])
                    ->middleware('admin.perm:utilisateurs.update');
                Route::get('/{user}/cotisations', [\App\Http\Controllers\Apiv1\Admin\GestionUtilisateurController::class, 'cotisations']);
                Route::delete('/{user}', [\App\Http\Controllers\Apiv1\Admin\GestionUtilisateurController::class, 'archiver'])
                    ->middleware('admin.perm:utilisateurs.delete');
            });

            // Configurations & Paramétrages métier
            Route::prefix('configurations')->group(function () {
                Route::prefix('partenaires-financiers')->middleware('admin.perm:partenaires-financiers.view')->group(function () {
                    Route::get('/', [\App\Http\Controllers\Apiv1\Admin\PartenaireFinancierController::class, 'index']);
                    Route::post('/', [\App\Http\Controllers\Apiv1\Admin\PartenaireFinancierController::class, 'store'])
                        ->middleware('admin.perm:partenaires-financiers.create');
                    Route::get('/{partenaireFinancier}', [\App\Http\Controllers\Apiv1\Admin\PartenaireFinancierController::class, 'show']);
                    Route::put('/{partenaireFinancier}', [\App\Http\Controllers\Apiv1\Admin\PartenaireFinancierController::class, 'update'])
                        ->middleware('admin.perm:partenaires-financiers.update');
                    Route::delete('/{partenaireFinancier}', [\App\Http\Controllers\Apiv1\Admin\PartenaireFinancierController::class, 'destroy'])
                        ->middleware('admin.perm:partenaires-financiers.delete');
                });
            });

            // Épargne (objectifs utilisateurs)
            Route::prefix('epargne')->middleware('admin.perm:epargne.view')->group(function () {
                Route::get('/kpis', [\App\Http\Controllers\Apiv1\Admin\EpargneAdminController::class, 'kpis']);
                Route::get('/',     [\App\Http\Controllers\Apiv1\Admin\EpargneAdminController::class, 'index']);
                Route::get('/{objectifEpargne}', [\App\Http\Controllers\Apiv1\Admin\EpargneAdminController::class, 'show']);
            });

            // Cotisations sociales (stats admin)
            Route::prefix('cotisations')->middleware('admin.perm:cotisations.view')->group(function () {
                Route::get('/kpis',      [\App\Http\Controllers\Apiv1\Admin\CotisationAdminController::class, 'kpis']);
                Route::get('/evolution', [\App\Http\Controllers\Apiv1\Admin\CotisationAdminController::class, 'evolutionMensuelle']);
                Route::get('/par-type',  [\App\Http\Controllers\Apiv1\Admin\CotisationAdminController::class, 'parType']);
                Route::get('/',          [\App\Http\Controllers\Apiv1\Admin\CotisationAdminController::class, 'index']);
            });

            // Transactions / Opérations financières
            Route::prefix('transactions')->middleware('admin.perm:transactions.view')->group(function () {
                Route::get('/', [\App\Http\Controllers\Apiv1\Admin\TransactionController::class, 'index']);
                Route::get('/{operation}', [\App\Http\Controllers\Apiv1\Admin\TransactionController::class, 'show']);
            });

            // Types de cotisation
            Route::prefix('types-cotisation')->middleware('admin.perm:types-cotisation.view')->group(function () {
                Route::get('/', [\App\Http\Controllers\Apiv1\Admin\TypeCotisationController::class, 'index']);
                Route::post('/', [\App\Http\Controllers\Apiv1\Admin\TypeCotisationController::class, 'store'])
                    ->middleware('admin.perm:types-cotisation.create');
                Route::get('/{typeCotisation}', [\App\Http\Controllers\Apiv1\Admin\TypeCotisationController::class, 'show']);
                Route::put('/{typeCotisation}', [\App\Http\Controllers\Apiv1\Admin\TypeCotisationController::class, 'update'])
                    ->middleware('admin.perm:types-cotisation.update');
                Route::delete('/{typeCotisation}', [\App\Http\Controllers\Apiv1\Admin\TypeCotisationController::class, 'destroy'])
                    ->middleware('admin.perm:types-cotisation.delete');
                Route::patch('/{typeCotisation}/statut', [\App\Http\Controllers\Apiv1\Admin\TypeCotisationController::class, 'basculerStatut'])
                    ->middleware('admin.perm:types-cotisation.update');
            });

            // Moyens de paiement
            Route::prefix('moyens-paiement')->middleware('admin.perm:moyens-paiement.view')->group(function () {
                Route::get('/', [\App\Http\Controllers\Apiv1\Admin\MoyenPaiementController::class, 'index']);
                Route::post('/', [\App\Http\Controllers\Apiv1\Admin\MoyenPaiementController::class, 'store'])
                    ->middleware('admin.perm:moyens-paiement.create');
                Route::get('/{moyenPaiement}', [\App\Http\Controllers\Apiv1\Admin\MoyenPaiementController::class, 'show']);
                Route::match(['PUT', 'POST'],'/{moyenPaiement}', [\App\Http\Controllers\Apiv1\Admin\MoyenPaiementController::class, 'update'])
                    ->middleware('admin.perm:moyens-paiement.update');
                Route::delete('/{moyenPaiement}', [\App\Http\Controllers\Apiv1\Admin\MoyenPaiementController::class, 'destroy'])
                    ->middleware('admin.perm:moyens-paiement.delete');
                Route::patch('/{moyenPaiement}/statut', [\App\Http\Controllers\Apiv1\Admin\MoyenPaiementController::class, 'basculerStatut'])
                    ->middleware('admin.perm:moyens-paiement.update');
                Route::patch('/{moyenPaiement}/par-defaut', [\App\Http\Controllers\Apiv1\Admin\MoyenPaiementController::class, 'definirParDefaut'])
                    ->middleware('admin.perm:moyens-paiement.update');
            });

            // Configurations APIs des opérateurs de paiement
            Route::prefix('configurations-api')->middleware('admin.perm:configurations-api.view')->group(function () {
                Route::get('/',    [\App\Http\Controllers\Apiv1\Admin\ConfigurationApiController::class, 'index']);
                Route::post('/',   [\App\Http\Controllers\Apiv1\Admin\ConfigurationApiController::class, 'store'])
                    ->middleware('admin.perm:configurations-api.create');
                Route::get('/{configurationApiOperateur}',    [\App\Http\Controllers\Apiv1\Admin\ConfigurationApiController::class, 'show']);
                Route::put('/{configurationApiOperateur}',    [\App\Http\Controllers\Apiv1\Admin\ConfigurationApiController::class, 'update'])
                    ->middleware('admin.perm:configurations-api.update');
                Route::delete('/{configurationApiOperateur}', [\App\Http\Controllers\Apiv1\Admin\ConfigurationApiController::class, 'destroy'])
                    ->middleware('admin.perm:configurations-api.delete');
                Route::patch('/{configurationApiOperateur}/statut',           [\App\Http\Controllers\Apiv1\Admin\ConfigurationApiController::class, 'basculerStatut'])
                    ->middleware('admin.perm:configurations-api.configure');
                Route::post('/{configurationApiOperateur}/tester-connexion',  [\App\Http\Controllers\Apiv1\Admin\ConfigurationApiController::class, 'testerConnexion'])
                    ->middleware('admin.perm:configurations-api.configure');
                Route::post('/{configurationApiOperateur}/tester-webhook',    [\App\Http\Controllers\Apiv1\Admin\ConfigurationApiController::class, 'testerWebhook'])
                    ->middleware('admin.perm:configurations-api.configure');
            });

            // Seuils de prélèvement
            Route::prefix('seuil-prelevements')->middleware('admin.perm:seuils-prelevement.view')->group(function () {
                Route::get('/',  [\App\Http\Controllers\Apiv1\Admin\SeuilPrelevementController::class, 'show']);
                Route::put('/',  [\App\Http\Controllers\Apiv1\Admin\SeuilPrelevementController::class, 'update'])
                    ->middleware('admin.perm:seuils-prelevement.update');
            });

            // Reversements
            Route::prefix('reversements')->middleware('admin.perm:reversements.view')->group(function () {
                Route::get('/dashboard',                      [\App\Http\Controllers\Apiv1\Admin\ReversementAdminController::class, 'dashboard']);
                Route::get('/calculer-disponible',            [\App\Http\Controllers\Apiv1\Admin\ReversementAdminController::class, 'calculerDisponible']);
                Route::get('/',                               [\App\Http\Controllers\Apiv1\Admin\ReversementAdminController::class, 'index']);
                Route::post('/',                              [\App\Http\Controllers\Apiv1\Admin\ReversementAdminController::class, 'store'])
                    ->middleware('admin.perm:reversements.create');
                Route::get('/{reversement}',                  [\App\Http\Controllers\Apiv1\Admin\ReversementAdminController::class, 'show']);
                Route::patch('/{reversement}/annuler',        [\App\Http\Controllers\Apiv1\Admin\ReversementAdminController::class, 'annuler'])
                    ->middleware('admin.perm:reversements.cancel');
            });

            // Répartitions & Splits
            Route::prefix('repartitions')->middleware('admin.perm:repartitions.view')->group(function () {
                Route::get('/dashboard', [\App\Http\Controllers\Apiv1\Admin\RepartitionAdminController::class, 'dashboard']);
                Route::get('/regles',    [\App\Http\Controllers\Apiv1\Admin\RepartitionAdminController::class, 'regles']);
                Route::get('/',          [\App\Http\Controllers\Apiv1\Admin\RepartitionAdminController::class, 'index']);
                Route::get('/{operation}',[\App\Http\Controllers\Apiv1\Admin\RepartitionAdminController::class, 'show']);
            });

            // Mobile Money (admin)
            Route::prefix('mobile-money')->middleware('admin.perm:mobile-money.view')->group(function () {
                Route::get('/dashboard',                   [\App\Http\Controllers\Apiv1\Admin\MobileMoneyAdminController::class, 'dashboard']);
                Route::get('/',                            [\App\Http\Controllers\Apiv1\Admin\MobileMoneyAdminController::class, 'index']);
                Route::get('/{compteMobileMoney}',         [\App\Http\Controllers\Apiv1\Admin\MobileMoneyAdminController::class, 'show']);
                Route::patch('/{compteMobileMoney}/statut',[\App\Http\Controllers\Apiv1\Admin\MobileMoneyAdminController::class, 'basculerStatut'])
                    ->middleware('admin.perm:mobile-money.validate');
            });

            // Gestion des administrateurs (CRUD)
            Route::prefix('gestion-admins')->middleware('admin.perm:gestion-admins.view')->group(function () {
                Route::get('/dashboard',                          [\App\Http\Controllers\Apiv1\Admin\AdminGestionController::class, 'dashboard']);
                Route::get('/',                                   [\App\Http\Controllers\Apiv1\Admin\AdminGestionController::class, 'index']);
                Route::post('/',                                  [\App\Http\Controllers\Apiv1\Admin\AdminGestionController::class, 'store'])
                    ->middleware('admin.perm:gestion-admins.create');
                Route::get('/{admin}',                            [\App\Http\Controllers\Apiv1\Admin\AdminGestionController::class, 'show']);
                Route::put('/{admin}',                            [\App\Http\Controllers\Apiv1\Admin\AdminGestionController::class, 'update'])
                    ->middleware('admin.perm:gestion-admins.update');
                Route::patch('/{admin}/statut',                   [\App\Http\Controllers\Apiv1\Admin\AdminGestionController::class, 'changerStatut'])
                    ->middleware('admin.perm:gestion-admins.update');
                Route::post('/{admin}/renvoyer-invitation',       [\App\Http\Controllers\Apiv1\Admin\AdminGestionController::class, 'renvoyerInvitation'])
                    ->middleware('admin.perm:gestion-admins.update');
                Route::delete('/{admin}',                         [\App\Http\Controllers\Apiv1\Admin\AdminGestionController::class, 'archive'])
                    ->middleware('admin.perm:gestion-admins.archive');
                Route::patch('/{adminId}/restaurer',              [\App\Http\Controllers\Apiv1\Admin\AdminGestionController::class, 'restore'])
                    ->middleware('admin.perm:gestion-admins.restore');
            });

            // Rôles & Permissions (RBAC)
            Route::prefix('roles')->middleware('admin.perm:roles.view')->group(function () {
                Route::get('/all',                         [\App\Http\Controllers\Apiv1\Admin\RoleController::class, 'all']);
                Route::get('/',                            [\App\Http\Controllers\Apiv1\Admin\RoleController::class, 'index']);
                Route::post('/',                           [\App\Http\Controllers\Apiv1\Admin\RoleController::class, 'store'])
                    ->middleware('admin.perm:roles.create');
                Route::get('/{role}',                      [\App\Http\Controllers\Apiv1\Admin\RoleController::class, 'show']);
                Route::put('/{role}',                      [\App\Http\Controllers\Apiv1\Admin\RoleController::class, 'update'])
                    ->middleware('admin.perm:roles.update');
                Route::patch('/{role}/archiver',           [\App\Http\Controllers\Apiv1\Admin\RoleController::class, 'archive'])
                    ->middleware('admin.perm:roles.archive');
                Route::patch('/{role}/restaurer',          [\App\Http\Controllers\Apiv1\Admin\RoleController::class, 'restore'])
                    ->middleware('admin.perm:roles.restore');
                Route::put('/{role}/sync-permissions',     [\App\Http\Controllers\Apiv1\Admin\RoleController::class, 'syncPermissions'])
                    ->middleware('admin.perm:roles.assign');
            });

            Route::prefix('permissions')->middleware('admin.perm:permissions.view')->group(function () {
                Route::get('/par-module',                  [\App\Http\Controllers\Apiv1\Admin\PermissionController::class, 'parModule']);
                Route::get('/modules',                     [\App\Http\Controllers\Apiv1\Admin\PermissionController::class, 'modules']);
                Route::get('/',                            [\App\Http\Controllers\Apiv1\Admin\PermissionController::class, 'index']);
                Route::post('/',                           [\App\Http\Controllers\Apiv1\Admin\PermissionController::class, 'store'])
                    ->middleware('admin.perm:permissions.create');
                Route::put('/{permission}',                [\App\Http\Controllers\Apiv1\Admin\PermissionController::class, 'update'])
                    ->middleware('admin.perm:permissions.update');
            });

            Route::prefix('admins-rbac')->middleware('admin.perm:gestion-admins.view')->group(function () {
                Route::get('/',                            [\App\Http\Controllers\Apiv1\Admin\AdminRoleController::class, 'index']);
                Route::get('/{admin}',                     [\App\Http\Controllers\Apiv1\Admin\AdminRoleController::class, 'show']);
                Route::patch('/{admin}/assigner-role',     [\App\Http\Controllers\Apiv1\Admin\AdminRoleController::class, 'assignerRole'])
                    ->middleware('admin.perm:gestion-admins.assign');
                Route::patch('/{admin}/retirer-role',      [\App\Http\Controllers\Apiv1\Admin\AdminRoleController::class, 'retirerRole'])
                    ->middleware('admin.perm:gestion-admins.assign');
                Route::patch('/{admin}/assigner-permissions', [\App\Http\Controllers\Apiv1\Admin\AdminRoleController::class, 'assignerPermissions'])
                    ->middleware('admin.perm:gestion-admins.assign');
                Route::patch('/{admin}/retirer-permissions',  [\App\Http\Controllers\Apiv1\Admin\AdminRoleController::class, 'retirerPermissions'])
                    ->middleware('admin.perm:gestion-admins.assign');
            });

            // Logs & Audit
            Route::prefix('logs-audit')->middleware('admin.perm:logs-audit.view')->group(function () {
                Route::get('/modules',          [\App\Http\Controllers\Apiv1\Admin\LogAuditController::class, 'modules']);
                Route::get('/actions',          [\App\Http\Controllers\Apiv1\Admin\LogAuditController::class, 'actions']);
                Route::get('/export',           [\App\Http\Controllers\Apiv1\Admin\LogAuditController::class, 'export'])
                    ->middleware('admin.perm:logs-audit.export');
                Route::get('/',                 [\App\Http\Controllers\Apiv1\Admin\LogAuditController::class, 'index']);
                Route::get('/{logAudit}',       [\App\Http\Controllers\Apiv1\Admin\LogAuditController::class, 'show']);
                Route::delete('/{logAudit}',    [\App\Http\Controllers\Apiv1\Admin\LogAuditController::class, 'archive'])
                    ->middleware('admin.perm:logs-audit.delete');
                Route::patch('/{logId}/restaurer', [\App\Http\Controllers\Apiv1\Admin\LogAuditController::class, 'restore'])
                    ->middleware('admin.perm:logs-audit.delete');
            });

            // Alertes système
            Route::prefix('alertes')->middleware('admin.perm:alertes.view')->group(function () {
                Route::get('/compteurs',              [\App\Http\Controllers\Apiv1\Admin\AlerteController::class, 'compteurs']);
                Route::get('/',                       [\App\Http\Controllers\Apiv1\Admin\AlerteController::class, 'index']);
                Route::get('/{alerte}',               [\App\Http\Controllers\Apiv1\Admin\AlerteController::class, 'show']);
                Route::patch('/{alerteId}/lire',      [\App\Http\Controllers\Apiv1\Admin\AlerteController::class, 'marquerLu'])
                    ->middleware('admin.perm:alertes.update');
                Route::post('/lire-tout',             [\App\Http\Controllers\Apiv1\Admin\AlerteController::class, 'marquerTousLus'])
                    ->middleware('admin.perm:alertes.update');
                Route::delete('/{alerte}',            [\App\Http\Controllers\Apiv1\Admin\AlerteController::class, 'archive'])
                    ->middleware('admin.perm:alertes.delete');
                Route::patch('/{alerteId}/restaurer', [\App\Http\Controllers\Apiv1\Admin\AlerteController::class, 'restore'])
                    ->middleware('admin.perm:alertes.delete');
            });

            Route::prefix('parametres-globaux')->middleware('admin.perm:parametres-globaux.view')->group(function () {
                Route::get('/config',  [\App\Http\Controllers\Apiv1\Admin\ParametreGlobalController::class, 'config']);
                Route::put('/config',  [\App\Http\Controllers\Apiv1\Admin\ParametreGlobalController::class, 'saveConfig'])
                    ->middleware('admin.perm:parametres-globaux.update');
                Route::get('/', [\App\Http\Controllers\Apiv1\Admin\ParametreGlobalController::class, 'index']);
                Route::post('/', [\App\Http\Controllers\Apiv1\Admin\ParametreGlobalController::class, 'store'])
                    ->middleware('admin.perm:parametres-globaux.create');
                Route::get('/{parametreGlobal}', [\App\Http\Controllers\Apiv1\Admin\ParametreGlobalController::class, 'show']);
                Route::put('/{parametreGlobal}', [\App\Http\Controllers\Apiv1\Admin\ParametreGlobalController::class, 'update'])
                    ->middleware('admin.perm:parametres-globaux.update');
                Route::delete('/{parametreGlobal}', [\App\Http\Controllers\Apiv1\Admin\ParametreGlobalController::class, 'destroy'])
                    ->middleware('admin.perm:parametres-globaux.delete');
            });

            // Paramètres généraux de la plateforme (singleton)
            Route::prefix('parametre-general')->middleware('admin.perm:parametres-generaux.view')->group(function () {
                Route::get('/',                          [\App\Http\Controllers\Apiv1\Admin\ParametreGeneralController::class, 'show']);
                Route::post('/',                         [\App\Http\Controllers\Apiv1\Admin\ParametreGeneralController::class, 'save'])
                    ->middleware('admin.perm:parametres-generaux.update');
                Route::delete('/fichier/{champ}',        [\App\Http\Controllers\Apiv1\Admin\ParametreGeneralController::class, 'supprimerFichier'])
                    ->where('champ', '[a-z_]+')
                    ->middleware('admin.perm:parametres-generaux.update');
            });

            // Gestion des notifications (canaux + historique)
            Route::prefix('notification-config')->middleware('admin.perm:notifications-config.view')->group(function () {
                Route::get('/',                          [\App\Http\Controllers\Apiv1\Admin\NotificationConfigController::class, 'index']);
                Route::get('/logs',                      [\App\Http\Controllers\Apiv1\Admin\NotificationConfigController::class, 'logs']);
                Route::get('/logs/compteurs',            [\App\Http\Controllers\Apiv1\Admin\NotificationConfigController::class, 'logsCompteurs']);
                Route::post('/logs/{id}/reessayer',      [\App\Http\Controllers\Apiv1\Admin\NotificationConfigController::class, 'reessayer'])
                    ->middleware('admin.perm:notifications-config.update');
                Route::get('/{canal}',                   [\App\Http\Controllers\Apiv1\Admin\NotificationConfigController::class, 'show']);
                Route::put('/{canal}',                   [\App\Http\Controllers\Apiv1\Admin\NotificationConfigController::class, 'update'])
                    ->middleware('admin.perm:notifications-config.update');
                Route::patch('/{canal}/statut',          [\App\Http\Controllers\Apiv1\Admin\NotificationConfigController::class, 'basculerStatut'])
                    ->middleware('admin.perm:notifications-config.update');
                Route::post('/{canal}/tester',           [\App\Http\Controllers\Apiv1\Admin\NotificationConfigController::class, 'testerEnvoi'])
                    ->middleware('admin.perm:notifications-config.update');
            });

            // Tableau de bord
            Route::get('/dashboard', [\App\Http\Controllers\Apiv1\Admin\DashboardController::class, 'index'])
                ->middleware('admin.perm:dashboard.view');

            // Gestion des pages personnalisées (CMS)
            Route::prefix('pages')->middleware('admin.perm:pages.view')->group(function () {
                Route::get('/types',                [\App\Http\Controllers\Apiv1\Admin\PageController::class, 'types']);
                Route::get('/',                     [\App\Http\Controllers\Apiv1\Admin\PageController::class, 'index']);
                Route::post('/',                    [\App\Http\Controllers\Apiv1\Admin\PageController::class, 'store'])
                    ->middleware('admin.perm:pages.create');
                Route::get('/{id}',                 [\App\Http\Controllers\Apiv1\Admin\PageController::class, 'show']);
                Route::put('/{id}',                 [\App\Http\Controllers\Apiv1\Admin\PageController::class, 'update'])
                    ->middleware('admin.perm:pages.update');
                Route::delete('/{id}',              [\App\Http\Controllers\Apiv1\Admin\PageController::class, 'destroy'])
                    ->middleware('admin.perm:pages.delete');
                Route::patch('/{id}/publier',       [\App\Http\Controllers\Apiv1\Admin\PageController::class, 'publier'])
                    ->middleware('admin.perm:pages.update');
                Route::patch('/{id}/depublier',     [\App\Http\Controllers\Apiv1\Admin\PageController::class, 'depublier'])
                    ->middleware('admin.perm:pages.update');
                Route::patch('/{id}/restaurer',     [\App\Http\Controllers\Apiv1\Admin\PageController::class, 'restaurer'])
                    ->middleware('admin.perm:pages.restore');
            });

            // Export de données (PDF, Excel, CSV)
            Route::get('/export/{module}', [\App\Http\Controllers\Apiv1\Admin\ExportController::class, 'export'])
                ->where('module', '[a-z\-]+');

            // Audit de sécurité
            Route::prefix('audit-securite')->middleware('admin.perm:systeme.view')->group(function () {
                Route::get('/dashboard',                                    [\App\Http\Controllers\Apiv1\Admin\SecurityAuditController::class, 'dashboard']);
                Route::get('/statut',                                       [\App\Http\Controllers\Apiv1\Admin\SecurityAuditController::class, 'statut']);
                Route::get('/vulnerabilites',                               [\App\Http\Controllers\Apiv1\Admin\SecurityAuditController::class, 'vulnerabilites']);
                Route::get('/historique',                                   [\App\Http\Controllers\Apiv1\Admin\SecurityAuditController::class, 'historique']);
                Route::patch('/vulnerabilites/{vulnerabilite}/corrige',     [\App\Http\Controllers\Apiv1\Admin\SecurityAuditController::class, 'marquerCorrige']);
                Route::patch('/vulnerabilites/{vulnerabilite}/statut',      [\App\Http\Controllers\Apiv1\Admin\SecurityAuditController::class, 'changerStatut']);
                Route::get('/export/pdf',                                   [\App\Http\Controllers\Apiv1\Admin\SecurityAuditController::class, 'exportPdf']);
                Route::get('/export/excel',                                 [\App\Http\Controllers\Apiv1\Admin\SecurityAuditController::class, 'exportExcel']);
                // Actions (Super Admin uniquement)
                Route::post('/lancer',                                      [\App\Http\Controllers\Apiv1\Admin\SecurityAuditController::class, 'lancer']);
                Route::post('/corrections/appliquer',                       [\App\Http\Controllers\Apiv1\Admin\SecurityAuditController::class, 'appliquerCorrections']);
            });

            // Système & Backups (Super Admin uniquement)
            Route::prefix('systeme')->middleware('admin.perm:systeme.view')->group(function () {
                // Logs Laravel
                Route::get('/logs/info',        [\App\Http\Controllers\Apiv1\Admin\SystemeController::class, 'logsInfo']);
                Route::get('/logs/telecharger',  [\App\Http\Controllers\Apiv1\Admin\SystemeController::class, 'logsDownload']);
                Route::get('/logs',              [\App\Http\Controllers\Apiv1\Admin\SystemeController::class, 'logsIndex']);
                Route::delete('/logs',           [\App\Http\Controllers\Apiv1\Admin\SystemeController::class, 'logsClear'])
                    ->middleware('admin.perm:systeme.delete');

                // Sauvegardes BDD
                Route::get('/backups',                              [\App\Http\Controllers\Apiv1\Admin\SystemeController::class, 'backupsIndex']);
                Route::post('/backups',                             [\App\Http\Controllers\Apiv1\Admin\SystemeController::class, 'backupCreate'])
                    ->middleware('admin.perm:systeme.backup');
                Route::get('/backups/{filename}/telecharger',       [\App\Http\Controllers\Apiv1\Admin\SystemeController::class, 'backupDownload'])
                    ->where('filename', '.+');
                Route::delete('/backups/{filename}',                [\App\Http\Controllers\Apiv1\Admin\SystemeController::class, 'backupDelete'])
                    ->where('filename', '.+');
            });
        });
    });
});





