<?php
namespace Windmill\Queue;

use Illuminate\Queue\Connectors\ConnectorInterface;

class WindmillConnector implements ConnectorInterface
{
    public function connect(array $config)
    {
        $queue = $config['queue'] ?? 'default';
        $connection = $config['connection'] ?? 'default';
        $prefix = 'windmill:' . gethostname(); // bisa juga IP

        return new WindmillQueue($prefix, $queue, $connection);
    }
}
