<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
// use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ConnexionRequest;
use App\Services\AdminService;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
// use Illuminate\Http\Request;

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
            // return $this->sendError($e->getMessage(), [], 500);
        }
    }
}
