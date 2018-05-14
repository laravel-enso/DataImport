<?php

namespace LaravelEnso\DataImport;

use Illuminate\Support\ServiceProvider;
use LaravelEnso\DataImport\app\Models\DataImport;
use LaravelEnso\DataImport\app\Observers\Observer;
use LaravelEnso\DataImport\app\Models\ImportTemplate;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadDependencies();
        $this->publishesAll();
    }

    private function publishesAll()
    {
        $this->publishes([
            __DIR__.'/config' => config_path('enso'),
        ], 'dataimport-config');

        $this->publishes([
            __DIR__.'/config' => config_path('enso'),
        ], 'enso-config');

        $this->publishes([
            __DIR__.'/resources/classes' => app_path(),
        ], 'dataimport-classes');

        $this->publishes([
            __DIR__.'/resources/assets/js' => resource_path('assets/js'),
        ], 'import-assets');

        $this->publishes([
            __DIR__.'/resources/assets/js' => resource_path('assets/js'),
        ], 'enso-assets');

        DataImport::observe(Observer::class);

        ImportTemplate::observe(Observer::class);
    }

    private function loadDependencies()
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->mergeConfigFrom(__DIR__.'/config/imports.php', 'imports');
        $this->loadRoutesFrom(__DIR__.'/routes/api.php');
    }

    public function register()
    {
        //
    }
}
