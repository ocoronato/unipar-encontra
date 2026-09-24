<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        // Acesso à área administrativa. Usado nas rotas (middleware "can:access-admin")
        // e nas views (@can('access-admin')).
        Gate::define('access-admin', fn (User $user) => $user->isAdmin());
    }
}
