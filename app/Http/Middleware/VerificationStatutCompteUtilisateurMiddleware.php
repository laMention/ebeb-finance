<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificationStatutCompteUtilisateurMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if ($user->statut !== 'ACTIF') {
            \Log::warning('compte-inactif-tentative-acces', [
                'user_id' => $user->id,
                'statut'  => $user->statut,
                'route'   => $request->path(),
                'method'  => $request->method(),
                'ip'      => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Votre compte doit être actif pour effectuer cette action.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
