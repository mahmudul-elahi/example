<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
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

        Gate::define('viewApiDocs', fn (?User $user): bool => $user?->isAdmin() === true);

        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(
            (int) config('auth.rate_limits.login'),
        )->by($request->input('email').'|'.$request->ip()));

        RateLimiter::for('register', fn (Request $request): Limit => Limit::perMinute(
            (int) config('auth.rate_limits.register'),
        )->by($request->ip()));

        RateLimiter::for('password-reset', fn (Request $request): Limit => Limit::perMinute(
            (int) config('auth.rate_limits.password_reset'),
        )->by($request->input('email').'|'.$request->ip()));

        RateLimiter::for('email-verification', fn (Request $request): Limit => Limit::perMinute(
            (int) config('auth.rate_limits.email_verification'),
        )->by($request->user()?->getAuthIdentifier().'|'.$request->ip()));
    }
}
