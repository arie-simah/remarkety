<?php

/**
 * This script receives POST requests which contain JSON encoded events
 * Each event is stored in a specific file and an event counter is stored in a Redis database.
 * TODO: - save Redis database in memory at least each day.
 *       - when the server starts up, retrieve past counter values from memory or by counting log file lines (for the current day)
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Get the POST data and check it
$content_type = $_SERVER["CONTENT_TYPE"];
if (strtolower($content_type) === 'application/json') {
    $json = file_get_contents("php://input");
    $trace = preg_replace('/[\r\n\t\f\v]/', '', $json);
} else {
    header("HTTP/1.0 400 Bad Request");
    exit();
}

$data = json_decode($json);
if (is_null($data) || empty($data->event_id) || empty($data->event_type)) {
    header("HTTP/1.0 400 Bad Request");
    exit();
}

// Get current date and requested variables
$date = date('Ymd');
$year = substr($date, 0, 4);
$month = substr($date, 4, 2);
$day = substr($date, 6, 2);

$event_type = $data->event_type;

// Store event into file system
$filepath = __DIR__ . "/files/$year/$month/$day/";
if (!file_exists($filepath)) {
    mkdir($filepath, 0777, true);
}

$filepath .= "$event_type.log";

file_put_contents($filepath, json_encode($data, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);

// Increment events counters
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

$redis->sAdd('types', $event_type);

$redisDailyKey = $date . $event_type;
$redisGlobalKey = $event_type;

$redis->incr($redisDailyKey);
$redis->incr($redisGlobalKey);

// Just to test 
echo "Incremented key $redisDailyKey. New value: " . $redis->get($redisDailyKey) . PHP_EOL;
echo "Incremented key $redisGlobalKey. New value: " . $redis->get($redisGlobalKey) . PHP_EOL;
