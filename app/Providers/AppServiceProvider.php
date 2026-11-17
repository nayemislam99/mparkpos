<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate;
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
        // Custom redirect for unauthenticated users
        Authenticate::redirectUsing(function () {
            // Redirect to the custom login route
            return route('auth.login');
        });

        foreach (array_keys(config('access.permission_groups', [])) as $group) {
            foreach (array_keys(config("access.permission_groups.{$group}.permissions", [])) as $permission) {
                Gate::define($permission, static function (User $user) use ($permission): bool {
                    $grants = config("access.roles.{$user->type}.permissions", []);

                    return in_array('*', $grants, true) || in_array($permission, $grants, true);
                });
            }
        }

        Gate::define('pos.view', static fn (User $user): bool => $user->can('sales.create'));
    }
}
