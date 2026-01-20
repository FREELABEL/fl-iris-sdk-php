<?php

require __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;
use IRIS\SDK\Config;
use IRIS\SDK\Auth\AuthManager;

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Force production
$_ENV['IRIS_ENV'] = 'production';
putenv('IRIS_ENV=production');

echo "=== Authentication Debug Test ===\n\n";

$apiKey = $_ENV['IRIS_API_KEY'] ?? '';
echo "API Key (first 30 chars): " . substr($apiKey, 0, 30) . "...\n";
echo "Environment: " . ($_ENV['IRIS_ENV'] ?? 'not set') . "\n";
echo "FL API URL: " . ($_ENV['FL_API_URL'] ?? 'not set') . "\n\n";

// Create config
$config = new Config([
    'api_key' => $apiKey,
    'user_id' => 193,
    'environment' => 'production',
    'debug' => true
]);

echo "Config Base URL: " . $config->baseUrl . "\n";
echo "Config FL API URL: " . $config->flApiUrl . "\n\n";

// Create AuthManager
$authManager = new AuthManager($config);

// Test different endpoints
$testEndpoints = [
    '/api/v1/leads',
    '/api/v1/leads/aggregation',
    '/api/v1/leads/123',
    '/api/v1/leads?search=test',
    '/api/health',
];

echo "=== Testing Auth Strategy for Endpoints ===\n\n";
foreach ($testEndpoints as $endpoint) {
    $strategy = $authManager->determineAuthStrategy($endpoint);
    $token = $authManager->getTokenForEndpoint($endpoint);
    $hasToken = !empty($token);
    
    echo "Endpoint: $endpoint\n";
    echo "  Strategy: $strategy\n";
    echo "  Has Token: " . ($hasToken ? 'YES' : 'NO') . "\n";
    echo "  Token (first 20): " . ($hasToken ? substr($token, 0, 20) . '...' : 'EMPTY') . "\n\n";
}

echo "=== Testing Actual API Call ===\n\n";

// Create IRIS instance
$iris = new IRIS([
    'api_key' => $apiKey,
    'user_id' => 193,
    'environment' => 'production',
    'debug' => true
]);

// Test direct curl first
echo "1. Testing with direct curl:\n";
$ch = curl_init('https://apiv2.heyiris.io/api/v1/leads/aggregation?search=Ayala');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Bearer ' . $apiKey,
]);
$response = curl_exec($ch);
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   Status Code: $statusCode\n";
echo "   Response: " . substr($response, 0, 200) . "...\n\n";

// Test SDK call
echo "2. Testing with SDK:\n";
try {
    $result = $iris->leads->search(['search' => 'Ayala']);
    echo "   Success! Found " . count($result) . " leads\n";
    if (!empty($result)) {
        foreach ($result as $idx => $lead) {
            echo "   - Lead $idx: " . $lead['name'] . " (" . $lead['email'] . ")\n";
            if ($idx >= 2) break; // Only show first 3
        }
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
    echo "   Class: " . get_class($e) . "\n";
    echo "   Code: " . $e->getCode() . "\n";
}

echo "\n=== Test Complete ===\n";
