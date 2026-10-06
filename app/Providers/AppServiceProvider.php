<?php

namespace App\Providers;

use App\Modules\Shared\Services\TenantManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(
            TenantManager::class,
            fn() => new TenantManager()
        );

        $this->app->bind(
            \App\Modules\Shared\Contracts\TenantResolverInterface::class,
            \App\Modules\Shared\Services\DomainTenantResolver::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(base_path('app/Modules/Shared/Database/migrations'));
    }
}
