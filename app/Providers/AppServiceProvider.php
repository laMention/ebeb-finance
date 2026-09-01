<?php

namespace App\Providers;

use App\Services\EmailBrandingService;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiting();

        // Injecte automatiquement $branding dans tous les templates emails.*
        View::composer('emails.*', fn (\Illuminate\View\View $view) =>
            $view->with('branding', EmailBrandingService::get())
        );

        Schema::defaultStringLength(191);
        
        // Forcer les migrations à utiliser des UUID au lieu d'incréments entiers
        Schema::defaultMorphKeyType('uuid');
    }

    /**
     * Limiteur de débit global de l'API — filet de sécurité par défaut sur
     * toute route sous `routes/api.php` (groupe de middleware `api`),
     * indépendant des `throttle:X,Y` déjà posés explicitement sur certaines
     * routes sensibles (OTP, connexion admin), qui restent plus stricts et
     * continuent de s'appliquer par-dessus celui-ci.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
