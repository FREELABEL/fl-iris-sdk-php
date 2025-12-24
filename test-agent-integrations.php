<?php
require __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$iris = new IRIS([
    'api_key' => $_ENV['IRIS_API_KEY'],
    'user_id' => (int) $_ENV['IRIS_USER_ID'],
]);

try {
    // Get the Fashion Designer agent
    $agent = $iris->agents->get(371);
    
    echo "Agent #371 - {$agent->name}\n";
    echo "Model: {$agent->model}\n";
    echo "Integrations: " . json_encode($agent->integrations ?? []) . "\n";
    echo "\nFull agent data:\n";
    print_r($agent);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
