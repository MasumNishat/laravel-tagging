<?php

namespace Masum\Tagging;

use Illuminate\Support\ServiceProvider;

class TaggingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/tagging.php',
            'tagging'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/config/tagging.php' => config_path('tagging.php'),
        ], 'tagging-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/database/migrations/create_tags_table.php' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_tags_table.php'),
            __DIR__.'/database/migrations/create_tag_configs_table.php' => database_path('migrations/'.date('Y_m_d_His', time() + 1).'_create_tag_configs_table.php'),
        ], 'tagging-migrations');

        // Load views
        $this->loadViewsFrom(__DIR__.'/resources/views', 'tagging');

        // Publish views
        $this->publishes([
            __DIR__.'/resources/views' => resource_path('views/vendor/tagging'),
        ], 'tagging-views');

        // Register routes if enabled
        if (config('tagging.routes.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/routes/api.php');
        }
    }
}