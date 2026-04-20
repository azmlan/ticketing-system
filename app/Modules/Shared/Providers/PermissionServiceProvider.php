<?php

namespace App\Modules\Shared\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class PermissionServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // SPEC.md §6.9 — SuperUser bypasses all permission checks
        Gate::before(function ($user) {
            if ($user->is_super_user) {
                return true;
            }
        });

        foreach (array_keys(config('permissions', [])) as $key) {
            Gate::define($key, fn ($user) => $user->hasPermission($key));
        }

        Blade::directive('permission', function (string $expression) {
            return "<?php if(auth()->user()?->can({$expression})): ?>";
        });

        Blade::directive('endpermission', function () {
            return '<?php endif; ?>';
        });

        Blade::directive('unlesspermission', function (string $expression) {
            return "<?php if(!auth()->user()?->can({$expression})): ?>";
        });

        Blade::directive('endunlesspermission', function () {
            return '<?php endif; ?>';
        });
    }
}
