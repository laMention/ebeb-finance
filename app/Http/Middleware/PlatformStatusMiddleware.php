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

        if (in_array($etat['statut'], ['MAINTENANCE', 'DESACTIVEE'], true)) {
            $code = $etat['statut'] === 'DESACTIVEE' ? 'PLATFORM_DISABLED' : 'PLATFORM_MAINTENANCE';
            $message = $etat['message'] ?? ($etat['statut'] === 'DESACTIVEE'
                ? 'Vous ne pouvez pas accéder à la plateforme pour le moment. La plateforme est temporairement indisponible.'
                : 'La plateforme est actuellement en maintenance. Veuillez réessayer ultérieurement.');

            if ($request->expectsJson() || $request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'code'    => $code,
                    'message' => $message,
                ], Response::HTTP_SERVICE_UNAVAILABLE);
            }

            return response()->view(
                'maintenance.status',
                ['statut' => $etat['statut'], 'message' => $message],
                Response::HTTP_SERVICE_UNAVAILABLE,
            );
        }

        return $next($request);
    }
}
