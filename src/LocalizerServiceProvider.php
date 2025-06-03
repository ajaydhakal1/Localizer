<?php

namespace MrAjay\Localizer;

use Illuminate\Support\ServiceProvider;

class LocalizerServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/localizer.php', 'localizer');
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/localizer.php' => config_path('localizer.php'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\LocalizeAll::class,
                Commands\Localize::class,
            ]);
        }
    }
}
