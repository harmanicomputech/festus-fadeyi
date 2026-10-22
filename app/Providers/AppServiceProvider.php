<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        TrustProxies::at(config('app.trusted_proxies'));

        // Index-safe string length for older MySQL/MariaDB on shared hosting.
        Schema::defaultStringLength(191);

        Model::preventLazyLoading(! $this->app->isProduction());

        Password::defaults(fn () => Password::min(12)->mixedCase()->numbers());

        RateLimiter::for('forms', fn (Request $r) => [
            Limit::perMinute(6)->by('forms:'.$r->ip()),
            Limit::perHour(40)->by('forms-h:'.$r->ip()),
        ]);
        RateLimiter::for('lookup', fn (Request $r) => Limit::perMinute(30)->by('lookup:'.$r->ip()));
        RateLimiter::for('login', fn (Request $r) => [
            Limit::perMinute(5)->by('login:'.$r->ip()),
            Limit::perHour(20)->by('login-e:'.strtolower((string) $r->input('email'))),
        ]);
        RateLimiter::for('checkin', fn (Request $r) => Limit::perMinute(120)->by('checkin:'.($r->user()?->id ?? $r->ip())));
    }
}
