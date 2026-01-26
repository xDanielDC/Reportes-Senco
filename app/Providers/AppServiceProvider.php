<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registrar Repository
        $this->app->bind(
            \App\Repositories\ListaPreciosRepository::class,
            function ($app) {
                return new \App\Repositories\ListaPreciosRepository(
                    new \App\Models\ListaPrecio()
                );
            }
        );

        // Registrar Service
        $this->app->bind(
            \App\Services\ListaPreciosService::class,
            function ($app) {
                return new \App\Services\ListaPreciosService(
                    $app->make(\App\Repositories\ListaPreciosRepository::class)
                );
            }
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}