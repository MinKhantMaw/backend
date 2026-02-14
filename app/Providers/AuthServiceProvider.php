<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\PermissionPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Role::class => RolePolicy::class,
        Permission::class => PermissionPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Passport::ignoreMigrations();
        // Passport::routes();
        Passport::tokensExpireIn(CarbonImmutable::now()->addHours(2));
        Passport::refreshTokensExpireIn(CarbonImmutable::now()->addDays(30));
        Passport::personalAccessTokensExpireIn(CarbonImmutable::now()->addDays(30));

        Gate::before(function (User $user) {
            return $user->hasRole('Super Admin') ? true : null;
        });
    }
}
