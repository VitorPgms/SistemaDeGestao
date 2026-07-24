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
        // Administrador tem acesso irrestrito a todas as abilities,
        // incluindo as que forem criadas por módulos futuros.
        Gate::before(fn (User $user, string $ability) => $user->hasRole('administrador') ?: null);
    }
}
