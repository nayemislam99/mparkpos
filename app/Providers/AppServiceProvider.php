<?php

namespace App\Providers;

use App\Models\CompanyRolePermission;
use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
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
                    if ($user->type === 'super_admin') {
                        return true;
                    }

                    static $customPermissionsCache = [];
                    $cacheKey = "{$user->company_id}_{$user->branch_id}_{$user->type}";

                    if (! array_key_exists($cacheKey, $customPermissionsCache)) {
                        $customPermissions = null;
                        try {
                            if (Schema::hasTable('company_role_permissions')) {
                                $custom = CompanyRolePermission::query()
                                    ->where('company_id', $user->company_id)
                                    ->where(function ($query) use ($user): void {
                                        $query->where('branch_id', $user->branch_id)
                                            ->orWhereNull('branch_id');
                                    })
                                    ->where('role', $user->type)
                                    ->orderByDesc('branch_id')
                                    ->first();

                                $customPermissions = $custom?->permissions;
                            }
                        } catch (\Throwable) {
                            $customPermissions = null;
                        }

                        $customPermissionsCache[$cacheKey] = $customPermissions;
                    }

                    $grants = $customPermissionsCache[$cacheKey]
                        ?? config("access.roles.{$user->type}.permissions", []);

                    return in_array('*', $grants, true) || in_array($permission, $grants, true);
                });
            }
        }

        Gate::define('pos.view', static fn (User $user): bool => $user->can('sales.create'));
    }
}
