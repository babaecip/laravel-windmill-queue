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

    public function push($job, $data = '', $queue = null, $delay = 2)
    {   
        if($delay < 2){
            $delay = 2;
        }
        $prefixAndQueue = $this->config['prefix'].':'.($queue ?? $this->config['queue']);
        $queue = ($queue ?? $this->config['queue']);
        $pop_url="";
        $jobId = DB::connection($this->config['mysql_driver'])->table('queue_pending')->insertGetId([
            'source' => $this->config['prefix'],
            'queue' => $queue,
            'payload' => $this->createPayload($job, $queue, $data),
            'attempt' => 0,
            'pop_url' => $pop_url,
            'created_at' => date('Y-m-d H:i:s'),
            'reserved_at' => date('Y-m-d H:i:s', strtotime("+{$delay} seconds"))
        ]);
        $pop_url = $this->config['pop_url'].'?queue='.$queue.'&prefix='.$this->config['prefix'].'&job_id='.$jobId;
        DB::connection($this->config['mysql_driver'])->table('queue_pending')->where('id', $jobId)->update([
            'pop_url' => $pop_url
        ]);
        $result = $this->postHttp('push',$this->config['push_url'], $this->config['prefix'], $queue, $this->createPayload($job, $queue, $data), $pop_url, $jobId, $delay);
    }

    public function pop($queue = null)
    {
        return 0;
    }

    public function pushRaw($payload, $queue = null, array $options = []){
        $this->push($payload, $payload, $queue);
    }

    public function later($delay, $job, $data = '', $queue = null){
        $this->push($job, $data, $queue, $delay);
    }

    public function postHttp($purpose, $url, $prefix, $queue, $payload, $pop_url, $job_id, $delay)
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
                    'job_id' => $job_id,
                    'delay' => $delay
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
