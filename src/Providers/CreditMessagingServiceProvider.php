<?php

namespace Techigh\CreditMessaging\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
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
        // Merge configuration
        $this->mergeConfigFrom(__DIR__ . '/../../config/credit-messaging.php', 'credit-messaging');

        // Load translations
        $this->loadJsonTranslationsFrom(__DIR__ . '/../../resources/lang');

        // Register core services as singletons
        $this->registerCoreServices();
    }

    /**
     * Register core services as singletons
     */
    protected function registerCoreServices(): void
    {
        // 1️⃣ CreditManagerService 싱글톤 등록 (클래스명으로)
        $this->app->singleton(CreditManagerService::class, function ($app) {
            return new CreditManagerService();
        });

        // 2️⃣ MessageServiceAdapter 싱글톤 등록 (클래스명으로)
        $this->app->singleton(MessageServiceAdapter::class, function ($app) {
            return new MessageServiceAdapter(
                $app->make(CreditManagerService::class)
            );
        });

        // 3️⃣ MessageRoutingService 싱글톤 등록 (클래스명으로)
        $this->app->singleton(MessageRoutingService::class, function ($app) {
            return new MessageRoutingService(
                $app->make(CreditManagerService::class),
                $app->make(MessageServiceAdapter::class)
            );
        });

        // 4️⃣ 파사드용 문자열 키 바인딩 (기존 싱글톤을 참조)
        $this->app->singleton('credit-manager', function ($app) {
            return $app->make(CreditManagerService::class);
        });

        $this->app->singleton('message-router', function ($app) {
            return $app->make(MessageRoutingService::class);
        });

        // 5️⃣ 별칭(Alias) 등록으로 추가 보장
        $this->app->alias(CreditManagerService::class, 'credit-manager');
        $this->app->alias(MessageRoutingService::class, 'message-router');
    }


    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register commands first (before other boot operations)
        $this->registerCommands();

        // Publish configuration files
        $this->publishConfigurations();

        // Publish database files  
        $this->publishDatabaseFiles();

        // Load package resources
        $this->loadPackageResources();

        // Register API routes
        $this->registerRoutes();
    }

    /**
     * Register artisan commands
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Techigh\CreditMessaging\Commands\SeedCreditMessagingData::class,
            ]);
        }
    }

    /**
     * Publish configuration files
     */
    protected function publishConfigurations(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/credit-messaging.php' => config_path('credit-messaging.php'),
        ], 'credit-messaging-config');
    }

    /**
     * Publish database files
     */
    protected function publishDatabaseFiles(): void
    {
        // Publish migrations
        $this->publishes([
            __DIR__ . '/../../database/migrations/' => database_path('migrations/'),
        ], 'credit-messaging-migrations');

        // Publish seeders
        $this->publishes([
            __DIR__ . '/../../database/seeders/' => database_path('seeders/'),
        ], 'credit-messaging-seeders');
    }

    /**
     * Load package resources
     */
    protected function loadPackageResources(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Load views if they exist
        if (is_dir(__DIR__ . '/../../resources/views')) {
            $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'credit-messaging');
        }
    }

    /**
     * Register API routes
     */
    protected function registerRoutes(): void
    {
        Route::group([
            'middleware' => ['api'],
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        });
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            CreditManagerService::class,
            MessageServiceAdapter::class,
            MessageRoutingService::class,
            'credit-manager',
            'message-router',
        ];
    }
}
