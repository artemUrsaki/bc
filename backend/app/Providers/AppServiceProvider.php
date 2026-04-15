<?php

namespace App\Providers;

use App\Services\Runners\HttpProtocolRunner;
use App\Services\Runners\MqttProtocolRunner;
use App\Services\Runners\ProtocolRunnerManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('benchmark-control', function (Request $request): Limit {
            $key = optional($request->user())->id ?: $request->ip();

            return Limit::perMinute(10)->by($key);
        });
    }
}
