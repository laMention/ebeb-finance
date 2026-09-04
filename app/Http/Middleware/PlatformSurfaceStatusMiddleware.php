<?php

namespace App\Http\Middleware;

use App\Services\PlateformeSurfaceStateService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloque une surface précise (SITE_WEB ou PANEL_ADMIN) lorsqu'elle est
 * désactivée indépendamment, en complément du kill switch global
 * (`plateforme.actif`) qui reste appliqué en amont sur ces mêmes groupes.
 */
class PlatformSurfaceStatusMiddleware
{
    private const TITRES = [
        'SITE_WEB'    => 'Site indisponible',
        'PANEL_ADMIN' => "Panel d'administration indisponible",
    ];

    private const MESSAGES_DEFAUT = [
        'SITE_WEB'    => 'Le site est temporairement indisponible. Veuillez réessayer plus tard.',
        'PANEL_ADMIN' => "Le panel d'administration est temporairement indisponible. Veuillez réessayer plus tard.",
    ];

    public function __construct(private readonly PlateformeSurfaceStateService $service)
    {
    }

    public function handle(Request $request, Closure $next, string $surface): Response
    {
        $etat = $this->service->getStatut($surface);

        if ($etat['statut'] === 'DESACTIVE') {
            $message = $etat['message'] ?? self::MESSAGES_DEFAUT[$surface] ?? 'Service temporairement indisponible.';

            if ($request->expectsJson() || $request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'code'    => 'PLATFORM_DISABLED',
                    'message' => $message,
                ], Response::HTTP_SERVICE_UNAVAILABLE);
            }

            return response()->view(
                'maintenance.status',
                [
                    'statut'  => 'DESACTIVEE',
                    'message' => $message,
                    'title'   => self::TITRES[$surface] ?? 'Service indisponible',
                ],
                Response::HTTP_SERVICE_UNAVAILABLE,
            );
        }

        return $next($request);
    }
}
