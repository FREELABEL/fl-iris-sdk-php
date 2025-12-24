<?php

require_once __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;
use IRIS\SDK\Resources\Agents\AgentConfig;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Initialize SDK
$iris = new IRIS([
    'api_key' => $_ENV['IRIS_API_KEY'],
    'user_id' => (int) $_ENV['IRIS_USER_ID'],
]);

echo "Creating ATX Beauty Lab Receptionist Agent...\n";

try {
    // Create the agent
    $agent = $iris->agents->create(new AgentConfig(
        name: 'ATX Beauty Lab Receptionist',
        prompt: 'You are the friendly and professional AI receptionist for ATX Beauty Lab. Your goal is to assist clients with booking appointments, answering questions about beauty services (facials, lashes, extensive skin care), and providing information about the lab\'s location and hours. You should be warm, welcoming, and efficient. Always maintain a polite and helpful tone.',
        model: 'gpt-4o',
    ));
    
    echo "✅ Success! Agent Created:\n";
    echo "ID: " . $agent->id . "\n";
    echo "Name: " . $agent->name . "\n";
    echo "Model: " . $agent->model . "\n";
    
    // Get the simple URL
    $url = $iris->agents->getUrl($agent->id);
    echo "🔗 Shareable URL: " . $url . "\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
