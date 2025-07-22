<?php

namespace Windmill\Queue;

use Illuminate\Support\ServiceProvider;

class WindmillQueueServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Merge default config, but allow override from project config
        $this->mergeConfigFrom(__DIR__.'/../config/windmill.php', 'windmill');
    }

    public function boot()
    {
        // Register the queue connector
        $this->app['queue']->addConnector('windmill', function () {
            return new \Windmill\Queue\Connectors\WindmillConnector();
        });

        // Merge into queue.connections only if not already defined
        $existing = config('queue.connections.windmill');

        if (empty($existing)) {
            config([
                'queue.connections.windmill' => config('windmill.connection')
            ]);
        }

        // Allow user to publish your default config
        $this->publishes([
            __DIR__.'/../config/windmill.php' => config_path('windmill.php'),
        ], 'windmill-config');
    }
}
