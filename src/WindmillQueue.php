<?php
namespace Windmill\Queue;

use Illuminate\Queue\Queue;
use Illuminate\Contracts\Queue\Queue as QueueContract;

class WindmillQueue extends Queue implements QueueContract
{
    protected $prefix;
    protected $queue;
    protected $connection;

    public function __construct($prefix, $queue, $connection)
    {
        $this->prefix = $prefix;
        $this->queue = $queue;
        $this->connection = $connection;
    }

    public function size($queue = null)
    {
        return 0; // Implement MySQL count logic
    }

    public function push($job, $data = '', $queue = null)
    {
        // Save job to MySQL queue table
    }

    public function pop($queue = null)
    {
        // Windmill will POST here and trigger this
    }
}
