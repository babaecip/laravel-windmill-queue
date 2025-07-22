<?php 

namespace Windmill\Queue;

use Illuminate\Support\ServiceProvider;
use Illuminate\Queue\QueueManager;

class WindmillQueueServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app['queue']->addConnector('windmill', function () {
            return new WindmillConnector;
        });
    }
}
