<?php
namespace Windmill\Queue;

use Illuminate\Queue\Connectors\ConnectorInterface;

class WindmillConnector implements ConnectorInterface
{
    public function connect(array $config)
    {
        $config = [
            'queue' => $config['queue'] ?? 'default',
            'prefix' => $config['prefix'] ?? (gethostbyname(gethostname()) . '-' . gethostname()),
            'token' => $config['token'] ?? "",
            'push_url' => $config['push_url'] ?? "",
            'pop_url' => $config['pop_url'] ?? "",
            'mysql_driver' => $config['mysql_driver'] ?? ""
        ];
        return new WindmillQueue($config);
    }
}
