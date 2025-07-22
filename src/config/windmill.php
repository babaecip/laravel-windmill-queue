<?php

return [
    'connection' => [
        'driver' => 'windmill',
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 90,
    ],
];