<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\PurchaseRequest;
use App\Observers\PurchaseRequestObserver;

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
        // Register observers
        PurchaseRequest::observe(PurchaseRequestObserver::class);

        // Treat System Admin and Executive Officer as super-admins
        Gate::before(function ($user, $ability) {
            if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['System Admin', 'Executive Officer'])) {
                return true;
            }
            return null;
        });
    }
}
