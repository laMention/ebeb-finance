<?php

namespace App\Http\Controllers\Apiv1;

use App\Http\Controllers\BaseController;
// use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends BaseController
{
    //
    protected $userService;

    // Injection de dépendance du Service
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function infosUtilisateurConnecte(Request $request): UserResource
    {
         // Récupération des données via le Service
        $user = $this->userService->obtenirInfosUtilisateur($request->user()->id);

        // Retour via la ressource HTTP
        return new UserResource($user);
 
    }
}
