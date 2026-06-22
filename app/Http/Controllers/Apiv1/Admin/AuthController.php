<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\ConnexionRequest;
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

    // Methode pour se connecter à l'administration
    public function connexion(ConnexionRequest $request)
    {
        try {
            $validated = $request->validated();
            $result = $this->adminService->connexion($validated);

            if (!$result['success']) {
                return $this->sendError($result['message'],[],400);
            }

            $admin = $result['admin'] ?? null;
            AuditLogger::log('ADMIN.CONNEXION', $admin instanceof \App\Models\Administrateur ? $admin : null,
                'administrateurs', $admin->id ?? null, null, ['login' => $validated['email_telephone'] ?? null]);
            
            $data = [
                'success' => $result['success'],
                'admin' => $result['admin'],
                'token' => $result['token'],
            ];

            return $this->sendResponse($data, $result['message'] ?? 'Connexion réussie');
        } catch (\Exception $e) {
            //throw $th;
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

            $data = $this->adminService->infoProfil($request->user());

            return $this->sendResponse($data, 'Profil utilisateur' );
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
