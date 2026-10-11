<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;
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
        JsonResource::withoutWrapping();

        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(
            (int) config('auth.rate_limits.login'),
        )->by($request->input('email').'|'.$request->ip()));

        RateLimiter::for('register', fn (Request $request): Limit => Limit::perMinute(
            (int) config('auth.rate_limits.register'),
        )->by($request->ip()));
    }
}
