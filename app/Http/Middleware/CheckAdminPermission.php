<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vérifie qu'un administrateur authentifié possède au moins l'une des
 * permissions spécifiées (logique OR). Le super-admin est toujours autorisé.
 *
 * Usage dans les routes :
 *   ->middleware('admin.perm:utilisateurs.view')
 *   ->middleware('admin.perm:roles.view,roles.assign')   ← l'un ou l'autre suffit
 */
class CheckAdminPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $admin = $request->user('sanctum') ?? $request->user();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié.',
            ], 401);
        }

        // Super-admin bypass — toutes les permissions sont implicites
        if (method_exists($admin, 'isSuperAdmin') && $admin->isSuperAdmin()) {
            return $next($request);
        }

        // Vérifier au moins une permission (OR)
        foreach ($permissions as $perm) {
            if ($admin->hasPermissionTo(trim($perm), 'admin')) {
                return $next($request);
            }
        }

        return response()->json([
            'success'              => false,
            'message'              => "Accès refusé. Permission manquante : " . implode(' ou ', $permissions) . '.',
            'required_permissions' => $permissions,
        ], 403);
    }
}
