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
     * Version prefix for the API. Fortify is configured with the same value in
     * `config/fortify.php`, so `/api/v1/login` and `/api/v1/users/...` sit
     * behind one prefix.
     */
    public const API_PREFIX = 'api/v1';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $this->routes(function () {
            // The capsule API is stateless: bearer tokens, no session cookie,
            // and the `api` rate limiter defined above. It was previously
            // loaded into the `web` group, which meant the limiter registered
            // here was never applied to anything.
            Route::middleware('api')
                ->prefix(self::API_PREFIX)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
