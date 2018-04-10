<?php

namespace Datlv\Tag;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

/**
 * Class ServiceProvider
 *
 * @package Datlv\Tag
 */
class ServiceProvider extends BaseServiceProvider
{
    /**
     * Perform post-registration booting of services.
     */
    public function boot()
    {
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'tag');
        $this->loadViewsFrom(__DIR__ . '/../views', 'tag');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->publishes(
            [
                __DIR__ . '/../views' => base_path('resources/views/vendor/tag'),
                __DIR__ . '/../lang' => base_path('resources/lang/vendor/tag'),
                __DIR__ . '/../config/tag.php' => config_path('tag.php'),
            ]
        );
        if ($this->app->has('layout')) {
            app('layout')->registerWidgetTypes(config('tag.widgets'));
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/tag.php', 'tag');
    }
}
