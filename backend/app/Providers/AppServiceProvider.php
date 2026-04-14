<?php

namespace App\Providers;

use App\Services\Runners\HttpProtocolRunner;
use App\Services\Runners\MqttProtocolRunner;
use App\Services\Runners\ProtocolRunnerManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HttpProtocolRunner::class);
        $this->app->singleton(MqttProtocolRunner::class);

        $this->app->singleton(ProtocolRunnerManager::class, function ($app): ProtocolRunnerManager {
            return new ProtocolRunnerManager([
                $app->make(HttpProtocolRunner::class),
                $app->make(MqttProtocolRunner::class),
            ]);
        });
    }

    public function boot(): void
    {
    }
}
