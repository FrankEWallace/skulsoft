<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/app';

    public const VERSION_PATH = '/v1';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api'.self::VERSION_PATH)->group(function () {
                Route::middleware(['api', 'user.config'])
                    // ->namespace($this->namespace)
                    ->group(base_path('routes/api.php'));

                Route::middleware(['api', 'user.config'])
                    // ->namespace($this->namespace)
                    ->group(base_path('routes/integration.php'));

                Route::middleware(['api', 'guest'])
                    // ->namespace($this->namespace)
                    ->group(base_path('routes/guest.php'));

                Route::prefix('auth')
                    ->middleware(['web', 'user.config'])
                    // ->namespace($this->namespace)
                    ->group(base_path('routes/auth.php'));

                Route::prefix('app')
                    ->middleware(['api', 'auth:sanctum', 'two.factor.security', 'screen.lock', 'under.maintenance', 'user.config'])
                    // ->namespace($this->namespace)
                    ->group(base_path('routes/app.php'));

                Route::prefix('app/chat')
                    ->middleware(['api', 'auth:sanctum', 'two.factor.security', 'screen.lock', 'under.maintenance', 'user.config', 'permission:chat:access'])
                    // ->namespace($this->namespace)
                    ->group(base_path('routes/chat.php'));

                $modules = glob(base_path('routes/modules/*.php'));
                foreach ($modules as $module) {
                    Route::prefix('app')
                        ->middleware(['api', 'auth:sanctum', 'two.factor.security', 'screen.lock', 'under.maintenance', 'user.config'])
                        // ->namespace($this->namespace)
                        ->group($module);
                }

                Route::prefix('app')
                    ->middleware(['api', 'auth:sanctum', 'two.factor.security', 'screen.lock', 'under.maintenance', 'user.config'])
                    // ->namespace($this->namespace)
                    ->group(base_path('routes/module.php'));
            });

            $modules = glob(base_path('routes/exports/*.php'));
            foreach ($modules as $module) {
                Route::prefix('app')
                    ->middleware(['web', 'auth:sanctum', 'two.factor.security', 'screen.lock', 'under.maintenance', 'user.config', 'export'])
                    // ->namespace($this->namespace)
                    ->group($module);
            }

            Route::prefix('app')
                ->middleware(['web', 'auth:sanctum', 'two.factor.security', 'screen.lock', 'under.maintenance', 'user.config', 'export'])
                // ->namespace($this->namespace)
                ->group(base_path('routes/export.php'));

            Route::middleware(['web'])
                // ->namespace($this->namespace)
                ->group(base_path('routes/gateway.php'));

            Route::middleware(['web', 'site.enabled'])
                // ->namespace($this->namespace)
                ->group(base_path('routes/site.php'));

            Route::middleware(['web', 'auth:sanctum', 'user.config', 'permission:access:reports'])
                // ->namespace($this->namespace)
                ->group(base_path('routes/report.php'));

            // Custom routes

            Route::middleware([])
                // ->namespace($this->namespace)
                ->group(base_path('routes/custom.php'));

            // Custom routes for site

            Route::middleware(['web', 'site.enabled'])
                // ->namespace($this->namespace)
                ->group(base_path('routes/site/custom.php'));

            Route::middleware(['web', 'user.config'])
                // ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));

            Route::middleware('web')
                // ->namespace($this->namespace)
                ->group(base_path('routes/asset.php'));

            Route::middleware(['web', 'user.config', 'role:admin'])
                ->prefix('cmd')
                // ->namespace($this->namespace)
                ->group(base_path('routes/command.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5);
        });

        RateLimiter::for('otp', function (Request $request) {
            return Limit::perMinute(3);
        });

        RateLimiter::for('timesheet', function (Request $request) {
            return Limit::perMinute(3)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('biometric', function (Request $request) {
            return Limit::perMinute(10);
        });
    }
}
