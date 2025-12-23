<?php

require __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;
use IRIS\SDK\Auth\CredentialStore;

// Load credentials
$store = new CredentialStore();
$config = $store->toConfigArray();

echo "=== Testing bloqId Parameter Passing ===\n\n";

// Debug what we're sending
echo "Configuration:\n";
echo "- API Key: " . substr($config['api_key'], 0, 20) . "...\n";
echo "- User ID: " . $config['user_id'] . "\n";
echo "- IRIS URL: " . ($config['iris_url'] ?? 'default') . "\n\n";

// Initialize SDK
$iris = new IRIS($config);

// Enable debug mode
$iris->getConfig()->debug = true;

// Test parameters
$agentId = 349;
$bloqId = 203;
$query = "What specific information do you have about Ayala Engineering's projects or capabilities?";

echo "Chat parameters:\n";
echo "- Agent ID: $agentId\n";
echo "- Bloq ID: $bloqId\n";
echo "- Query: $query\n\n";

// Test 1: Start workflow and inspect the raw request
echo "=== Test 1: Start Workflow (inspect request) ===\n";

try {
    // Enable debug mode to see the request
    $iris->getConfig()->debug = true;
    
    $response = $iris->chat->start([
        'query' => $query,
        'agentId' => $agentId,
        'bloqId' => $bloqId,
    ]);
    
    echo "✓ Workflow started successfully\n";
    echo "Workflow ID: " . $response['workflow_id'] . "\n\n";
    
    // Get status to see what was sent
    $workflowId = $response['workflow_id'];
    
    echo "=== Test 2: Check Workflow Status ===\n";
    $status = $iris->chat->getStatus($workflowId);
    
    echo "Status: " . $status['status'] . "\n";
    echo "User Input: " . ($status['user_input'] ?? 'N/A') . "\n";
    
    if (isset($status['metrics'])) {
        echo "Metrics: " . json_encode($status['metrics'], JSON_PRETTY_PRINT) . "\n";
    }
    
    // Poll for completion
    echo "\n=== Test 3: Wait for Completion ===\n";
    $maxWait = 30; // 30 seconds
    $elapsed = 0;
    
    while ($elapsed < $maxWait) {
        $status = $iris->chat->getStatus($workflowId);
        echo "Status: {$status['status']} (${elapsed}s)\n";
        
        if ($status['status'] === 'completed') {
            echo "\n✓ Completed!\n";
            echo "Summary: " . ($status['summary'] ?? 'N/A') . "\n";
            break;
        } elseif ($status['status'] === 'failed') {
            echo "\n✗ Failed!\n";
            if (isset($status['error'])) {
                echo "Error: " . $status['error'] . "\n";
            }
            break;
        }
        
        sleep(2);
        $elapsed += 2;
    }
    
    if ($elapsed >= $maxWait) {
        echo "\n⚠ Timeout after ${maxWait}s\n";
    }
    
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test 4: Compare with Working cURL Request ===\n";
echo "Expected payload structure:\n";
$expectedPayload = [
    'query' => $query,
    'conversationHistory' => [
        ['role' => 'user', 'content' => $query]
    ],
    'agentId' => $agentId,
    'bloqId' => (string)$bloqId,  // Must be string!
    'userId' => $config['user_id'],
    'uploadedFiles' => [],
];
echo json_encode($expectedPayload, JSON_PRETTY_PRINT) . "\n";

echo "\n=== Test 5: Manual cURL Test ===\n";
$curlPayload = json_encode($expectedPayload);
$url = ($config['iris_url'] ?? 'https://iris.freelabel.net') . '/api/chat/start';

$curlCommand = sprintf(
    "curl -X POST '%s' \\\n  -H 'Authorization: Bearer %s' \\\n  -H 'Content-Type: application/json' \\\n  --data '%s' \\\n  --insecure",
    $url,
    substr($config['api_key'], 0, 30) . '...',
    str_replace("'", "'\\''", $curlPayload)
);

echo "Run this to test manually:\n";
echo $curlCommand . "\n";
