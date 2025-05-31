<?php

namespace Techigh\CreditMessaging\Providers;

use Illuminate\Support\ServiceProvider;
use Techigh\CreditMessaging\Services\CreditManagerService;
use Techigh\CreditMessaging\Services\MessageServiceAdapter;
use Techigh\CreditMessaging\Services\MessageRoutingService;

class CreditMessagingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/credit-messaging.php', 'credit-messaging');

        // Register core services
        $this->app->singleton(CreditManagerService::class, function () {
            return new CreditManagerService();
        });

        $this->app->singleton(MessageServiceAdapter::class, function () {
            return new MessageServiceAdapter(app(CreditManagerService::class));
        });

        $this->app->singleton(MessageRoutingService::class, function () {
            return new MessageRoutingService(
                app(MessageServiceAdapter::class),
                app(CreditManagerService::class)
            );
        });

        // Register facades
        $this->app->bind('credit-manager', function () {
            return app(CreditManagerService::class);
        });

        $this->app->bind('message-router', function () {
            return app(MessageRoutingService::class);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/credit-messaging.php' => config_path('credit-messaging.php'),
        ], 'credit-messaging-config');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Register routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Techigh\CreditMessaging\Commands\SeedCreditMessagingData::class,
            ]);
        }

        // Publish seeders
        $this->publishes([
            __DIR__ . '/../../database/seeders/' => database_path('seeders/'),
        ], 'credit-messaging-seeders');
    }
}
