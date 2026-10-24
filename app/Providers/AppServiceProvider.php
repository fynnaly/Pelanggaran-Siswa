<?php
namespace App\Providers;

use App\Models\DisciplineCase;
use App\Models\Setting;
use App\Policies\DisciplineCasePolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
        Paginator::defaultView('vendor.pagination.custom');
        Gate::policy(DisciplineCase::class, DisciplineCasePolicy::class);

        // Nama aplikasi dari pengaturan admin (settings.app_name) — fallback ke
        // config/app.php bila tabel belum ada (migrasi belum jalan).
        try {
            if (Schema::hasTable('settings')) {
                config(['app.name' => Setting::get('app_name') ?: config('app.name')]);
            }
        } catch (\Throwable) {
            // DB belum siap — biarkan config bawaan.
        }

        $this->registerRateLimiters();
    }

    /**
     * Rate limiter API (Android).
     *
     * api-login: 5 percobaan/menit per kombinasi email|ip — membatasi brute
     * force password per akun dan per perangkat sekaligus.
     * api: 60 request/menit per user (fallback IP bila belum login).
     */
    private function registerRateLimiters(): void
    {
        RateLimiter::for('api-login', function (Request $request) {
            $email = strtolower((string) $request->input('email', ''));

            return Limit::perMinute(5)->by($email . '|' . $request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
