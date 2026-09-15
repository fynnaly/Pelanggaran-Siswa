<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\DisciplineCase;
use App\Policies\DisciplineCasePolicy;
use Illuminate\Support\Facades\Gate;

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
        Gate::policy(DisciplineCase::class, DisciplineCasePolicy::class);
    }
}
