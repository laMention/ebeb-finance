<?php

namespace App\Http\Controllers\Apiv1\ControlPanel;

use App\Http\Controllers\BaseController;
use App\Models\Administrateur;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Authentification dédiée du panel central (kill switch).
 * Restreinte aux administrateurs Super-Admin ; les jetons émis ici portent
 * l'ability "control-panel" et ne peuvent pas être réutilisés sur l'API
 * du panel d'administration classique (et inversement).
 */
class PlatformControlAuthController extends BaseController
{
    public function connexion(Request $request)
    {
        try {
            $data = $request->validate([
                'email'    => 'required|email',
                'password' => 'required|string',
            ]);

            $admin = Administrateur::where('email', $data['email'])->first();

            if (!$admin || !Hash::check($data['password'], $admin->password)) {
                return $this->sendError('Identifiants invalides.', [], 401);
            }

            if (!$admin->isSuperAdmin()) {
                return $this->sendError("Accès réservé au Super Administrateur principal.", [], 403);
            }

            if ($admin->statut_compte !== 'ACTIF') {
                return $this->sendError('Votre compte est inactif.', [], 403);
            }

            $token = $admin->createToken('control-panel-token', ['control-panel'])->plainTextToken;

            AuditLogger::log('PLATEFORME.CONTROL_PANEL_CONNEXION', $admin, 'administrateurs', $admin->id);

            return $this->sendResponse([
                'admin' => $admin->only(['id', 'nom', 'prenom', 'email']),
                'token' => 'Bearer ' . $token,
            ], 'Connexion réussie.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Données invalides.', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function deconnexion(Request $request)
    {
        try {
            $admin = $request->user();

            AuditLogger::log('PLATEFORME.CONTROL_PANEL_DECONNEXION', $admin, 'administrateurs', $admin->id);

            $admin->currentAccessToken()->delete();

            return $this->sendResponse([], 'Déconnexion réussie.');
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }
}
