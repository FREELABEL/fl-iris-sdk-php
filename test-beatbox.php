<?php

require 'vendor/autoload.php';

use IRIS\SDK\IRIS;

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$iris = new IRIS([
    'api_url' => $_ENV['IRIS_API_URL'],
    'api_key' => $_ENV['IRIS_API_KEY'],
    'user_id' => (int)$_ENV['IRIS_USER_ID'],
]);

echo "Testing Beatbox Publish Integration\n";
echo "====================================\n\n";

echo "API URL: " . $_ENV['IRIS_API_URL'] . "\n";
echo "User ID: " . $_ENV['IRIS_USER_ID'] . "\n";
echo "API Key: " . substr($_ENV['IRIS_API_KEY'], 0, 20) . "...\n\n";

try {
    echo "Attempting to execute beatbox_publish...\n\n";
    
    $result = $iris->integrations->execute('beatbox-showcase', 'beatbox_publish', [
        'youtube_url' => 'https://www.youtube.com/watch?v=qPcP8HvX3cU',
        'start' => '0:10',
        'duration' => '90s',
    ]);
    
    echo "SUCCESS!\n\n";
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n\n";
    echo "Exception class: " . get_class($e) . "\n";
    
    if (method_exists($e, 'getResponse')) {
        echo "Response: " . $e->getResponse() . "\n";
    }
    
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}
