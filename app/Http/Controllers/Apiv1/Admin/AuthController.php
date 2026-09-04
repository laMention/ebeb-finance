<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\ConnexionRequest;
use App\Http\Requests\Admin\RenvoyerCode2FARequest;
use App\Http\Requests\Admin\VerifierCode2FARequest;
use App\Services\AdminService;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class AuthController extends BaseController
{
    protected $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    /**
     * Étape 1 : identifiants. Ne connecte jamais directement — déclenche la
     * 2FA et retourne un `challenge_id` à présenter à `verifierCode()`.
     */
    public function connexion(ConnexionRequest $request)
    {
        try {
            $validated = $request->validated();
            $result = $this->adminService->connexion($validated);

            if (!$result['success']) {
                return $this->sendError($result['message'],[],400);
            }

            AuditLogger::log('ADMIN.2FA_CODE_ENVOYE', null,
                'administrateurs', null, null, ['login' => $validated['email_telephone'] ?? null]);

            $data = [
                'two_factor_required' => true,
                'challenge_id'        => $result['challenge_id'],
            ];

            return $this->sendResponse($data, $result['message'] ?? 'Code de vérification envoyé.');
        } catch (\Exception $e) {
            //throw $th;
           return $this->throw($e);
        }

    }

    /**
     * Étape 2 : code de vérification. Authentifie réellement l'administrateur
     * (jeton Sanctum, rôles, permissions) — seulement si le code est valide.
     */
    public function verifierCode(VerifierCode2FARequest $request)
    {
        try {
            $validated = $request->validated();
            $result = $this->adminService->verifierCode2FA($validated['challenge_id'], $validated['code']);

            if (!$result['success']) {
                AuditLogger::log('ADMIN.2FA_ECHEC', null, 'administrateurs', null, null, [
                    'challenge_id' => $validated['challenge_id'],
                    'raison'       => $result['message'],
                ]);
                return $this->sendError($result['message'], [], 400);
            }

            $admin = $result['admin'] ?? null;
            AuditLogger::log('ADMIN.2FA_VERIFIE', $admin instanceof \App\Models\Administrateur ? $admin : null,
                'administrateurs', $admin->id ?? null);
            AuditLogger::log('ADMIN.CONNEXION', $admin instanceof \App\Models\Administrateur ? $admin : null,
                'administrateurs', $admin->id ?? null);

            $data = [
                'success'             => $result['success'],
                'admin'               => $result['admin'],
                'token'               => $result['token'],
                'permissions'         => $result['permissions'] ?? [],
                'has_all_permissions' => $result['has_all_permissions'] ?? false,
            ];

            return $this->sendResponse($data, $result['message'] ?? 'Connexion réussie');
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * Renvoie un nouveau code sur un challenge 2FA existant.
     */
    public function renvoyerCode(RenvoyerCode2FARequest $request)
    {
        try {
            $validated = $request->validated();
            $result = $this->adminService->renvoyerCode2FA($validated['challenge_id']);

            if (!$result['success']) {
                return $this->sendError($result['message'], [], 400);
            }

            AuditLogger::log('ADMIN.2FA_CODE_RENVOYE', null, 'administrateurs', null);

            return $this->sendResponse([
                'two_factor_required' => true,
                'challenge_id'        => $result['challenge_id'],
            ], $result['message']);
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    // Methode pour se deconnecter
    public function deconnexion(Request $request){
        try {

            $admin = $request->user();

            AuditLogger::log('ADMIN.DECONNEXION', $admin instanceof \App\Models\Administrateur ? $admin : null,
                'administrateurs', $admin->id ?? null);

            $resultat = $this->adminService->deconnexion($admin);

            if (!$resultat['success']) {
                return $this->sendError($resultat['message'],[],400);
            }
            return $this->sendResponse([], $resultat['message'] ?? 'Déconnexion réussie');

        } catch (\Exception $e) {
            return $this->throw($e);
            // return $this->sendError($e->getMessage(), [], 500);
        }
    }

    // Methode pour recuperer les infos de l'admin
    public function recupererInfoProfil(Request $request){
        try {
            $result = $this->adminService->infoProfil($request->user());

            return $this->sendResponse([
                'admin'               => $result['admin'],
                'permissions'         => $result['permissions'],
                'has_all_permissions' => $result['has_all_permissions'],
            ], 'Profil utilisateur');
        }catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    // Mise à jour des informations personnelles
    public function mettreAjourProfil(Request $request): JsonResponse
    {
        try {
            $admin = $request->user();
            $avant = $admin->only(['nom', 'prenom', 'email', 'telephone', 'ville', 'adresse']);

            $data = $request->validate([
                'nom'       => 'sometimes|string|max:100',
                'prenom'    => 'sometimes|string|max:100',
                'email'     => ['sometimes', 'email', \Illuminate\Validation\Rule::unique('administrateurs', 'email')->ignore($admin->id)],
                'telephone' => 'sometimes|nullable|string|max:25',
                'ville'     => 'sometimes|nullable|string|max:100',
                'adresse'   => 'sometimes|nullable|string|max:255',
            ]);

            $result = $this->adminService->mettreAjourProfil($admin, $data);

            AuditLogger::log('ADMIN.UPDATE_PROFIL', $admin, 'administrateurs', $admin->id, $avant, $data);

            return $this->sendResponse(['admin' => $result['admin']], 'Profil mis à jour avec succès.');
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    // Changement de mot de passe
    public function changerMotDePasse(Request $request): JsonResponse
    {
        try {
            $admin = $request->user();

            $data = $request->validate([
                'current_password' => 'required|string',
                'password'         => ['required', 'string', 'confirmed', Password::min(8)],
            ]);

            $result = $this->adminService->changerMotDePasse($admin, $data);

            if (!$result['success']) {
                return $this->sendError($result['message'], [], 400);
            }

            AuditLogger::log('ADMIN.CHANGE_PASSWORD', $admin, 'administrateurs', $admin->id);

            return $this->sendResponse([], $result['message']);
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    // Changement de photo de profil
    public function changerPhoto(Request $request): JsonResponse
    {
        try {
            $admin = $request->user();

            $request->validate([
                'photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
            ]);

            $result = $this->adminService->changerPhoto($admin, $request->file('photo'));

            AuditLogger::log('ADMIN.UPDATE_PHOTO', $admin, 'administrateurs', $admin->id);

            return $this->sendResponse(['admin' => $result['admin']], 'Photo de profil mise à jour.');
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }
}
