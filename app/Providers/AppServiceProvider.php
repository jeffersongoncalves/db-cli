<?php

namespace App\Providers;

use App\Services\ConnectionService;
use App\Services\DatabaseService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ConnectionService::class);
        $this->app->singleton(DatabaseService::class);
    }

    public function boot(): void
    {
        //
    }
}
