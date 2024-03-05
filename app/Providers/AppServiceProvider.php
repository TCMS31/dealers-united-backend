<?php

namespace App\Providers;

use App\Support\Masking\NoteMasker;
use App\Support\Masking\PrefixNoteMasker;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(NoteMasker::class, function ($app) {
            $masker = $app['config']->get('capsules.masker', PrefixNoteMasker::class);

            if ($masker === PrefixNoteMasker::class) {
                return new PrefixNoteMasker(
                    previewLength: (int) $app['config']->get('capsules.preview_length', 4),
                );
            }

            return $app->make($masker);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
