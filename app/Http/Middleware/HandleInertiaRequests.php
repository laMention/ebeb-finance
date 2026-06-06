<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $webUser = $request->user();
        $adminUser = $request->user('admin');
        $currentUser = $webUser ?? $adminUser;

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $currentUser,
                'admin' => $adminUser,
                'guard' => $adminUser ? 'admin' : 'web',
                'roles' => $currentUser ? $currentUser->getRoleNames()->values() : [],
                'permissions' => $currentUser ? $currentUser->getAllPermissions()->pluck('name')->values() : [],
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
