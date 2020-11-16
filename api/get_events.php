<?php

/**
 * Returns counters of each events types
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Initialize Redis database and connect
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

// Get all registered event types
$types = $redis->sMembers('types');

// Counters array
$counters = [];

foreach ($types as $type)
    $counters[] = (object)[
        'type' => $type,
        'counter' => $redis->get($type)
    ];

echo json_encode($counters);
