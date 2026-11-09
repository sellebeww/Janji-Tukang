<?php

namespace App\Providers;

use App\Notifiers\ConfirmationNotifier;
use App\Drafting\AgreementDrafter;
use App\Drafting\LlmDrafter;
use App\Drafting\ManualDrafter;
use App\Notifiers\LogNotifier;
use App\Notifiers\QueuedNotifier;
use App\Transcription\FakeTranscriber;
use App\Transcription\Transcriber;
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
            'sms', 'whatsapp_api' => new QueuedNotifier(config('janji.notifier')),
            default => new WaLinkNotifier,
        });

        $this->app->bind(AgreementDrafter::class, fn () => config('janji.drafter') === 'llm' && config('janji.llm.api_key')
            ? new LlmDrafter : new ManualDrafter);
        $this->app->bind(Transcriber::class, FakeTranscriber::class);
    }

    public function boot(): void
    {
        RateLimiter::for('konfirmasi', fn (Request $r) => Limit::perMinute(config('janji.rate_per_minute'))->by($r->ip()));
    }
}
