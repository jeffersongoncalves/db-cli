<?php

namespace App\Providers;

use App\Services\ConnectionService;
use App\Services\DatabaseService;
use Illuminate\Support\ServiceProvider;
use JeffersonGoncalves\LaravelZero\SelfUpdate\PharUpdater;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ConnectionService::class);
        $this->app->singleton(DatabaseService::class);

        $this->app->singleton(PharUpdater::class, fn () => new PharUpdater(
            githubRepo: 'jeffersongoncalves/db-cli',
            assetName: 'db.phar',
            tempPrefix: 'db_',
            currentVersion: (string) config('app.version', 'unreleased'),
        ));
    }

    public function boot(): void
    {
        //
    }
}
