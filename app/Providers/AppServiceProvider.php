<?php

namespace App\Providers;

use App\Notifiers\ConfirmationNotifier;
use App\Notifiers\LogNotifier;
use App\Notifiers\WaLinkNotifier;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ConfirmationNotifier::class, fn () => match (config('janji.notifier')) {
            'log' => new LogNotifier,
            default => new WaLinkNotifier,
        });
    }

    public function boot(): void
    {
        RateLimiter::for('konfirmasi', fn (Request $r) => Limit::perMinute(config('janji.rate_per_minute'))->by($r->ip()));
    }
}
