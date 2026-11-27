<?php

namespace App\Providers;

use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(Registered::class, SendEmailVerificationNotification::class);

        // Render and other HTTPS hosts: never emit http:// asset URLs (mixed content breaks CSS).
        $appUrl = (string) config('app.url');
        if ($this->app->environment('production') || str_starts_with($appUrl, 'https://')) {
            URL::forceScheme('https');
        }
        if ($appUrl !== '') {
            URL::forceRootUrl(rtrim($appUrl, '/'));
        }

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        RateLimiter::for('otp-verify', function (Request $request) {
            return Limit::perMinute(8)->by((string) ($request->user()?->id ?: $request->ip()));
        });

        RateLimiter::for('ai-analyze', function (Request $request) {
            return Limit::perMinute(3)->by((string) ($request->user()?->id ?: $request->ip()));
        });

        Route::bind('inventory', function (string $value) {
            return InventoryItem::query()->findOrFail($value);
        });

        View::composer('layouts.app', function ($view) {
            $alertCount = 0;
            $pendingUsers = 0;

            if (Auth::check() && Auth::user()->canManageInventory()) {
                $lowStock = InventoryItem::query()
                    ->where('is_active', true)
                    ->whereColumn('quantity_on_hand', '<=', 'reorder_level')
                    ->count();

                $expiredOrExpiring = InventoryItem::query()
                    ->where('is_active', true)
                    ->whereNotNull('expiration_date')
                    ->whereDate('expiration_date', '<=', now()->addDays(30))
                    ->count();

                $alertCount = $lowStock + $expiredOrExpiring;
            }

            if (Auth::check() && Auth::user()->canManageUsers()) {
                $pendingUsers = User::query()->where('status', User::STATUS_PENDING)->count();
            }

            $view->with([
                'navAlertCount' => $alertCount,
                'navPendingUsers' => $pendingUsers,
            ]);
        });
    }
}
