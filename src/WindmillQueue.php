<?php
namespace Windmill\Queue;

use Illuminate\Queue\Queue;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Throwable, DB;

class WindmillQueue extends Queue implements QueueContract
{
    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function size($queue = null)
    {
        return 0;
    }

    public function push($job, $data = '', $queue = null)
    {   
        $prefixAndQueue = $this->config['prefix'].':'.($queue ?? $this->config['queue']);
        $jobId = DB::connection($this->config['mysql_driver'])->table('queue_pending')->insertGetId([
            'source' => $this->config['prefix'],
            'queue' => $this->config['queue'],
            'payload' => $this->createPayload($job, $queue, $data),
            'attempt' => 0,
            'pop_url' => $this->config['pop_url'],
            'created_at' => date('Y-m-d H:i:s'),
            'reserved_at' => date('Y-m-d H:i:s')
        ]);
        $result = $this->postHttp('push',$this->config['push_url'], $this->config['prefix'], $this->config['queue'], $this->createPayload($job, $queue, $data), $this->config['pop_url'], $jobId);
    }

    public function pop($queue = null)
    {
        return 0;
    }

    public function pushRaw($payload, $queue = null, array $options = []){

    }

    public function later($delay, $job, $data = '', $queue = null){

    }

    public function postHttp($purpose, $url, $prefix, $queue, $payload, $pop_url, $job_id)
    {
        try{
            $response = (new Client())->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->config['token'],
                    'Accept' => 'application/json',
                ],
                'json' => [
                    'source' => $prefix,
                    'queue' => $queue,
                    'payload' => $payload,
                    'pop_url' => $pop_url,
                    'job_id' => $job_id
                ]
            ]);
            return [true,'success'];
        } catch (Throwable $e) {
            Log::error('Vendor [windmill/laravel-windmill-queue] '.$purpose.' failed', [
                'url' => @$url,
                'queue' => @$queue,
                'payload' => @$payload,
                'error' => @$e->getMessage(),
                'trace' => @$e->getTraceAsString()
            ]);
            return [false,@$e->getMessage()];
        }
    }
}
