<?php

namespace ItmoSoft\Returns;

use Illuminate\Support\ServiceProvider;

class ReturnsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/returns.php', 'itmosoft-returns');
    }

    public function boot(): void
    {
        $this->publishes([__DIR__.'/../config/returns.php' => config_path('itmosoft-returns.php')], 'itmosoft-returns-config');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
