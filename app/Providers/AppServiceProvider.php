<?php
namespace App\Providers;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Super Admin bypasses ALL gates
        Gate::before(function (User $user, string $ability) {
            if ($user->isSuperAdmin()) return true;
        });

        // Dynamically define gates from DB
        // Load all permissions once and cache
        $this->app->booted(function () {
            try {
                $permissions = Permission::all();
                foreach ($permissions as $permission) {
                    Gate::define($permission->slug, function (User $user) use ($permission) {
                        return $user->hasPermission($permission->slug);
                    });
                }
            } catch (\Exception $e) {
                // Silently fail during migrations
            }
        });
    }
}
