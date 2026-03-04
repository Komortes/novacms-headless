<?php

namespace App\Providers;

use App\AI\AiProviderFactory;
use App\AI\Contracts\AiProviderInterface;
use App\Models\Content;
use App\Models\Prompt;
use App\Observers\ContentObserver;
use App\Observers\PromptObserver;
use App\Services\AiSettingsManager;
use App\Services\PromptRegistry;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PromptRegistry::class);
        $this->app->singleton(AiProviderFactory::class);
        $this->app->singleton(AiSettingsManager::class);
        $this->app->bind(AiProviderInterface::class, fn ($app) => $app->make(AiProviderFactory::class)->make());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        app(AiSettingsManager::class)->applyConfigOverrides();
        Content::observe(ContentObserver::class);
        Prompt::observe(PromptObserver::class);

        RateLimiter::for('ai-requests', function (): Limit {
            return Limit::perMinute((int) config('ai.jobs.rate_limit_per_minute', 60))
                ->by('novacms-ai-global');
        });
    }
}
