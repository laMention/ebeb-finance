<?php

namespace App\Http\Middleware;

use App\Services\PlateformeStateService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloque toute requête métier (API mobile + panel d'administration) lorsque
 * la plateforme est en MAINTENANCE ou DESACTIVEE. Le panel central (kill
 * switch) n'est jamais soumis à ce middleware afin de rester accessible
 * quel que soit l'état de la plateforme.
 */
class PlatformStatusMiddleware
{
    public function __construct(private readonly PlateformeStateService $service)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $etat = $this->service->getStatutEffectif();

        if ($etat['statut'] === 'MAINTENANCE') {
            return response()->json([
                'success' => false,
                'code'    => 'PLATFORM_MAINTENANCE',
                'message' => $etat['message'] ?? 'La plateforme est actuellement en maintenance. Veuillez réessayer ultérieurement.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        if ($etat['statut'] === 'DESACTIVEE') {
            return response()->json([
                'success' => false,
                'code'    => 'PLATFORM_DISABLED',
                'message' => $etat['message'] ?? 'Vous ne pouvez pas effectuer cette action pour le moment. La plateforme est temporairement indisponible.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return $next($request);
    }
}
