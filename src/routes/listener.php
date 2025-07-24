<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Bus\Dispatcher;

Route::get('/__queue/status', function () {
    return response()
    ->json(
        [
            'message'      => 'Ready for consume.',
            'default_queue'        => config('queue.connections.windmill.queue', 'default'),
            'prefix'       => config('queue.connections.windmill.prefix', gethostbyname(gethostname()) . '-' . gethostname()),
            'push_url'     => config('queue.connections.windmill.push_url', ''),
            'pop_url'      => config('queue.connections.windmill.pop_url', ''),
            'mysql_driver' => config('queue.connections.windmill.mysql_driver', ''),
        ]
    )
    ->header('Content-Type', 'application/json');
});

Route::get('/__queue/execute', function(Request $request){
    $queue = $request->queue ?? config('queue.connections.windmill.queue','default');
    $mysql_driver = config('queue.connections.windmill.mysql_driver', '');
    $prefix = config('queue.connections.windmill.prefix', gethostbyname(gethostname()) . '-' . gethostname());
    
    $db = DB::connection($mysql_driver);
    $db->beginTransaction();
    $db->statement("SET innodb_lock_wait_timeout = 10");
    
    $jobRow = $db
        ->table('queue_pending')
        ->where('source', $prefix)
        ->where('queue', $queue)
        ->when($request->job_id, function($qry) use ($request){
            $qry->where('id',$request->job_id);
        })
        ->where('attempt', '<=', 3)
        ->lock('for update skip locked')
        ->first();
    
    if (!$jobRow) {
        $db->rollBack();
            return response()->json(
                [
                    'message'           => 'No queue found.',
                    'queue_pending_id'  => null,
                    'queue_complete_id' => null
                ], 204
            )
            ->header('Content-Type', 'application/json');
    } else {
        $payload = json_decode($jobRow->payload, true);
        $serializedJob = $payload['data']['command'] ?? null;

        if ($serializedJob) {
            $jobInstance = unserialize($serializedJob);
            $db
            ->table('queue_pending')
            ->where('id', $jobRow->id)
            ->update(['updated_at' => now(),'attempt' => $jobRow->attempt + 1]);

            if (method_exists($jobInstance, 'handle')) {
                $jobInstance->handle();
            }
            $db
            ->table('queue_pending')
            ->where('id', $jobRow->id)
            ->delete();
            $complete_id = DB::connection($mysql_driver)->table('queue_completed')->insertGetId([
                'source' => $jobRow->source,
                'queue' => $jobRow->queue,
                'payload' => $jobRow->payload,
                'attempt' => $jobRow->attempt + 1,
                'message' => 'Queue processed successfully.',
                'pop_url' => $jobRow->pop_url,
                'pending_job_id' => $jobRow->id,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $db->commit();
            return response()->json(
                [
                    'message'           => 'Well executed.',
                    'queue_pending_id'  => $jobRow->id,
                    'queue_complete_id' => $complete_id
                ]
            )
            ->header('Content-Type', 'application/json');
        } else {
            
            $db
            ->table('queue_pending')
            ->where('id', $jobRow->id)
            ->update(['updated_at' => now(),'attempt' => $jobRow->attempt + 1, 'message' => 'Class not found.']);
            $db->commit();
            return response()->json(
                [
                    'message'           => 'Some issue found.',
                    'queue_pending_id'  => $jobRow->id,
                    'queue_complete_id' => null
                ],
                422
            )
            ->header('Content-Type', 'application/json');
        }
    }


});