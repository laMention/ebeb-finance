<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Resources\DeclarationRevenuResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AdminUserService;
use App\Services\AlerteGenerator;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GestionUtilisateurController extends BaseController
{
    protected AdminUserService $adminUserService;
    protected NotificationService $notificationService;


    public function __construct(AdminUserService $adminUserService, NotificationService $notificationService)
    {
        $this->adminUserService = $adminUserService;
        $this->notificationService = $notificationService;
    }

    /**
     * Liste paginée avec filtres, recherche et compteurs par filtre rapide.
     * GET /administration/panel-admin/utilisateurs
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $params = $request->only([
                'recherche', 'statut', 'type_carte', 'sexe', 'ville', 'profession',
                'statut_kyc', 'date_debut', 'date_fin', 'page', 'per_page',
            ]);

            $paginated = $this->adminUserService->listerUtilisateurs($params);
            $compteurs = $this->adminUserService->compterParFiltre();

            $items = $paginated->map(function (User $user) {
                $statut_kyc  = $this->adminUserService->calculerStatutKYC($user);
                $portefeuille = $user->latestPortefeuille;

                return [
                    'uuid'                   => $user->id,
                    'reference'              => $user->reference,
                    'nom'                    => $user->nom,
                    'prenom'                 => $user->prenom,
                    'email'                  => $user->email,
                    'telephone'              => $user->telephone,
                    'profession'             => $user->profession,
                    'statut'                 => $user->statut,
                    'type_carte'             => $user->type_carte,
                    'statut_kyc'             => $statut_kyc,
                    'ville'                  => $user->ville,
                    'sexe'                   => $user->sexe,
                    'photo_profil'           => $user->photo_profil,
                    'total_epargne'          => $portefeuille?->total_epargne ?? 0,
                    'paiements_entrants_count' => $user->paiements_entrants_count,
                    'created_at'             => $user->created_at?->format('Y-m-d'),
                ];
            });

            // Filtre KYC post-traitement si demandé
            if (!empty($params['statut_kyc'])) {
                $items = $items->filter(fn($u) => $u['statut_kyc'] === $params['statut_kyc'])->values();
            }

            return $this->sendResponse([
                'users'     => $items,
                'meta'      => [
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                    'per_page'     => $paginated->perPage(),
                    'total'        => $paginated->total(),
                    'from'         => $paginated->firstItem(),
                    'to'           => $paginated->lastItem(),
                ],
                'compteurs' => $compteurs,
            ], 'Utilisateurs récupérés avec succès.');

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * Fiche complète d'un utilisateur avec toutes ses relations.
     * GET /administration/panel-admin/utilisateurs/{user}
     */
    public function show(User $user): JsonResponse
    {
        try {
            $userFull    = $this->adminUserService->obtenirUtilisateur($user);
            $statut_kyc  = $this->adminUserService->calculerStatutKYC($userFull);
            $portefeuille = $userFull->latestPortefeuille;

            $data = (new UserResource($userFull))->toArray(request());
            $data['statut_kyc']          = $statut_kyc;
            $data['total_epargne']       = $portefeuille?->total_epargne ?? 0;
            $data['photo_profil']        = $userFull->photo_profil;
            $data['reference']           = $userFull->reference;
            $data['derniere_connexion']  = $userFull->derniere_connexion?->format('Y-m-d H:i');

            return $this->sendResponse(['user' => $data], 'Utilisateur récupéré avec succès.');

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * Suspendre temporairement un compte.
     * PATCH /administration/panel-admin/utilisateurs/{user}/suspendre
     */
    public function suspendre(User $user, Request $request): JsonResponse
    {
        try {
            $motif    = $request->input('motif');
            $resultat = $this->adminUserService->suspendreCompte($user, $motif);

            if (!$resultat['success']) {
                return $this->sendError($resultat['message'], [], 400);
            }

            AuditLogger::log('USER.SUSPEND', $request->user(), 'utilisateurs', $user->id,
                ['statut' => $user->statut], ['statut' => 'SUSPENDU', 'motif' => $motif]);
            AlerteGenerator::utilisateur('AVERTISSEMENT',
                'Compte utilisateur suspendu',
                "Le compte de {$user->prenom} {$user->nom} ({$user->telephone}) a été suspendu." . ($motif ? " Motif : {$motif}" : ''),
                "/users/{$user->id}"
            );

            return $this->sendResponse(['user' => $resultat['user']], $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * Réactiver un compte suspendu.
     * PATCH /administration/panel-admin/utilisateurs/{user}/reactiver
     */
    public function reactiver(User $user): JsonResponse
    {
        try {
            $resultat = $this->adminUserService->reactiverCompte($user);

            if (!$resultat['success']) {
                return $this->sendError($resultat['message'], [], 400);
            }

            AuditLogger::log('USER.REACTIVATE', request()->user(), 'utilisateurs', $user->id,
                ['statut' => 'SUSPENDU'], ['statut' => 'ACTIF']);

            return $this->sendResponse(['user' => $resultat['user']], $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * Archiver (soft-delete) un compte utilisateur.
     * DELETE /administration/panel-admin/utilisateurs/{user}
     */
    public function archiver(User $user): JsonResponse
    {
        try {
            $resultat = $this->adminUserService->archiverCompte($user);

            if (!$resultat['success']) {
                return $this->sendError($resultat['message'], [], 400);
            }

            AuditLogger::log('USER.ARCHIVE', request()->user(), 'utilisateurs', $user->id,
                ['email' => $user->email], null);
            AlerteGenerator::utilisateur('CRITIQUE',
                'Compte utilisateur archivé',
                "Le compte de {$user->prenom} {$user->nom} ({$user->email}) a été archivé.",
                "/users/{$user->id}"
            );

            return $this->sendResponse([], $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * Mettre à jour les informations administratives (CNPS, CMU).
     * PATCH /administration/panel-admin/utilisateurs/{user}/infos-admin
     */
    public function mettreAjourInfosAdmin(User $user, Request $request): JsonResponse
    {
        try {
            $avant    = $user->only(['numero_cnps', 'numero_cmu']);
            $data     = $request->only(['numero_cnps', 'numero_cmu']);
            $resultat = $this->adminUserService->mettreAjourInfosAdmin($user, $data);

            if (!$resultat['success']) {
                return $this->sendError($resultat['message'], [], 400);
            }

            AuditLogger::log('USER.UPDATE_INFO', $request->user(), 'utilisateurs', $user->id, $avant, $data);

            // Envoyer une notification à l'utilisateur si les informations administratives ont été modifiées
            if ($avant['numero_cnps'] !== $data['numero_cnps'] || $avant['numero_cmu'] !== $data['numero_cmu']) {
                // Enregistrer une notification pour l'utilisateur
                $this->notificationService->envoyerNotification(
                    $user->id,
                    'in-app',
                    'MISE À JOUR DES INFORMATIONS ADMINISTRATIVES',
                    [
                        'titre'   => 'Mise à jour des informations administratives (CNPS/CMU)',
                        'message' => 'Vos informations administratives ont été mises à jour avec succès.',
                        'sujet'   => 'Mise à jour des informations administratives — ' . config('app.name'),
                    ],
                    true
                );

            }

            return $this->sendResponse(['user' => $resultat['user']], $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * Réinitialiser le code PIN d'un utilisateur.
     * PATCH /administration/panel-admin/utilisateurs/{user}/reinitialiser-pin
     */
    public function reinitialiserCodePin(User $user): JsonResponse
    {
        try {
            $resultat = $this->adminUserService->reinitialiserCodePin($user);

            if (!$resultat['success']) {
                return $this->sendError($resultat['message'], [], 400);
            }

            AuditLogger::log('USER.RESET_PIN', request()->user(), 'utilisateurs', $user->id);

            return $this->sendResponse([], $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * Met à jour la déclaration de revenu (source de vérité des objectifs de cotisation CNPS/AMU).
     * PUT /administration/panel-admin/utilisateurs/{user}/declaration-revenu
     */
    public function mettreAJourDeclarationRevenu(User $user, Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'montant_revenu'                            => 'required|numeric|min:0',
                'montant_cotisation_regime_base'            => 'required|numeric|min:0',
                'montant_cotisation_regime_complementaire'  => 'required|numeric|min:0',
                'montant_cotisation_mensuelle'               => 'required|numeric|min:0',
                'montant_cotisation_trimestrielle'           => 'required|numeric|min:0',
            ]);

            $avant    = $user->declarationRevenu?->only(array_keys($validated));
            $resultat = $this->adminUserService->mettreAJourDeclarationRevenu($user, $validated);

            AuditLogger::log('DECLARATION_REVENU.UPDATE', $request->user(), 'declaration_revenus',
                (string) $resultat['declaration']->id, $avant, $validated);

            return $this->sendResponse(
                ['declaration' => new DeclarationRevenuResource($resultat['declaration'])],
                $resultat['message']
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Données invalides.', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * Vue consolidée des cotisations pour une année donnée.
     * GET /administration/panel-admin/utilisateurs/{user}/cotisations
     */
    public function cotisations(User $user, Request $request): JsonResponse
    {
        try {
            $annee = (int) $request->input('annee', (int) date('Y'));
            $data  = $this->adminUserService->getCotisations($user, $annee);

            return $this->sendResponse($data, 'Cotisations récupérées avec succès.');

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }
}
