<?php

declare(strict_types=1);

namespace CraftAiObserver;

use CraftAiObserver\Listener\AgentEventListener;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

use function filter_var;

/**
 * Wires the observer into the host application.
 *
 * Laravel discovers it through the `extra.laravel.providers` entry in
 * composer.json, so Craft 6 needs no registration. See docs/rules/architecture.md.
 */
class ServiceProvider extends BaseServiceProvider
{
    /**
     * Register the package's services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(path: __DIR__ . '/../config/ai-observer.php', key: 'ai-observer');
    }

    /**
     * Bootstrap the package's services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes(paths: [
                __DIR__ . '/../config/ai-observer.php' => $this->app->configPath('ai-observer.php')
            ], groups: 'ai-observer-config');
        }

        if (!$this->enabled()) {
            return;
        }

        /** @var Dispatcher $events */
        $events = $this->app->make('events');
        $events->listen(AgentEventListener::EVENTS, AgentEventListener::class);
    }

    /**
     * Whether the package should listen at all.
     */
    protected function enabled(): bool
    {
        /** @var Repository $config */
        $config = $this->app->make('config');

        return filter_var($config->get(key: 'ai-observer.enabled', default: true), FILTER_VALIDATE_BOOL);
    }
}
