<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
// use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ConnexionRequest;
use App\Services\AdminService;
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


            $result = $this->adminService->deconnexion($admin);

            if (!$result['success']) {
                return $this->sendError($result['message'],[],400);
            }
            return $this->sendResponse([], $result['message'] ?? 'Déconnexion réussie');

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
