<?php

namespace Techigh\CreditMessaging\Providers;

use App\Settings\Configs\SiteConfigHandler;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Techigh\CreditMessaging\Services\CreditManager;
use Techigh\CreditMessaging\Services\MessageSendService;

class CreditMessagingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        if (config('smpp-provider.use_credit_messaging', false) === true) {
            // Merge configuration
            $this->mergeConfigFrom(__DIR__ . '/../../config/credit-messaging.php', 'credit-messaging');

            // Load translations
            $this->loadJsonTranslationsFrom(__DIR__ . '/../../resources/lang');

            // Register core services as singletons
            $this->registerCoreServices();
        }
    }

    /**
     * Register core services as singletons
     */
    protected function registerCoreServices(): void
    {
        // Register MessageSendService as singleton
        $this->app->singleton('message-send', function ($app) {
            return new MessageSendService();
        });

        $this->app->singleton('credit-handler', function ($app) {
            return new CreditManager();
        });
    }


    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if (config('smpp-provider.use_credit_messaging', false) === true) {

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

            // register Site configs
            $this->registerSiteConfigs();
        }
    }

    /**
     * Register artisan commands
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Techigh\CreditMessaging\Console\Commands\GenerateWebhookSecretCommand::class,
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
    }

    /**
     * Load package resources
     */
    protected function loadPackageResources(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
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

    protected function registerSiteConfigs(): void
    {
        SiteConfigHandler::registerConfigGroup('credit-messaging', 99999, [
            'icon' => 'bs.gear-wide-connected',
            'title' => __('Credit Messaging'),
            'description' => __('Credit Messaging'),
        ]);
        SiteConfigHandler::registerConfigSection('credit-messaging', 'configs', [
            'title' => __('Credit Messaging Credits'),
            'description' => __('Credit Messaging Credits')
        ]);
        SiteConfigHandler::registerConfigItem('credit-messaging', 'site_cost_per_credit', 'text', config('credit-message.default_credit_costs.cost_per_credit', '8'), 'configs', [
            'title' => __('Cost Per Credit'),
            'description' => __('Cost Per Credit'),
        ]);
        SiteConfigHandler::registerConfigItem('credit-messaging', 'site_alimtalk_credits_cost', 'text', config('credit-message.default_credit_costs.alimtalk', '1'), 'configs', [
            'title' => __('Site Alimtalk Credits Cost'),
            'description' => __('Site Alimtalk Credits Cost'),
        ]);
        SiteConfigHandler::registerConfigItem('credit-messaging', 'site_sms_credits_cost', 'text', config('credit-message.default_credit_costs.sms', '1.5'), 'configs', [
            'title' => __('Site SMS Credits Cost'),
            'description' => __('Site SMS Credits Cost'),
        ]);
        SiteConfigHandler::registerConfigItem('credit-messaging', 'site_lms_credits_cost', 'text', config('credit-message.default_credit_costs.lms', '4.5'), 'configs', [
            'title' => __('Site LMS Credits Cost'),
            'description' => __('Site LMS Credits Cost'),
        ]);
        SiteConfigHandler::registerConfigItem('credit-messaging', 'site_mms_credits_cost', 'text', config('credit-message.default_credit_costs.mms', '12'), 'configs', [
            'title' => __('Site MMS Credits Cost'),
            'description' => __('Site MMS Credits Cost'),
        ]);
    }
}
