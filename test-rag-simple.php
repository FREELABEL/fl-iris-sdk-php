<?php

require __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;
use IRIS\SDK\Auth\CredentialStore;

// Load credentials
$store = new CredentialStore();
$config = $store->toConfigArray();
$config['debug'] = true;  // Force debug mode

echo "=== RAG Integration Test ===\n\n";

// Initialize SDK
$iris = new IRIS($config);

// Parameters
$agentId = 349;
$bloqId = 203;
$query = "What is in the Ayala Engineering project proposal document?";

echo "Testing bloqId parameter:\n";
echo "Agent ID: $agentId\n";
echo "Bloq ID: $bloqId (type: " . gettype($bloqId) . ")\n";
echo "Query: $query\n\n";

// Manually construct the payload to see what we're sending
$userId = $config['user_id'];
$payload = [
    'query' => $query,
    'agentId' => $agentId,
    'userId' => $userId,
    'conversationHistory' => [
        ['role' => 'user', 'content' => $query]
    ],
    'bloqId' => (string) $bloqId,  // Cast to string
    'uploadedFiles' => [],
    'contextPayload' => [
        'source' => 'sdk-test',
    ],
];

echo "Payload being sent:\n";
echo json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";

// Test the chat
try {
    echo "Starting chat workflow...\n";
    $response = $iris->chat->start([
        'query' => $query,
        'agentId' => $agentId,
        'bloqId' => $bloqId,
    ]);
    
    echo "✓ Workflow started: " . $response['workflow_id'] . "\n\n";
    
    // Wait for completion
    $workflowId = $response['workflow_id'];
    $maxAttempts = 30;
    $attempt = 0;
    
    while ($attempt < $maxAttempts) {
        sleep(2);
        $attempt++;
        
        $status = $iris->chat->getStatus($workflowId);
        echo "[$attempt] Status: {$status['status']}\n";
        
        if ($status['status'] === 'completed') {
            echo "\n✓ Completed!\n\n";
            echo "Response:\n";
            echo "─────────────────────────────────────────\n";
            echo $status['summary'] . "\n";
            echo "─────────────────────────────────────────\n\n";
            
            if (isset($status['metrics'])) {
                echo "Metrics:\n";
                echo "  Model: " . ($status['metrics']['model'] ?? 'N/A') . "\n";
                echo "  Tokens: " . ($status['metrics']['total_tokens'] ?? 'N/A') . "\n";
                echo "  Time: " . ($status['metrics']['execution_time_ms'] ?? 'N/A') . "ms\n";
            }
            
            // Check if response contains RAG-retrieved information
            $summary = strtolower($status['summary']);
            if (strpos($summary, 'don\'t have access') !== false || 
                strpos($summary, 'cannot provide') !== false ||
                strpos($summary, 'i don\'t have') !== false) {
                echo "\n⚠️ WARNING: Agent says it doesn't have access to documents!\n";
                echo "This suggests RAG is NOT working or bloqId is not being used.\n";
            } else {
                echo "\n✓ Agent appears to have accessed document context.\n";
            }
            
            break;
        } elseif ($status['status'] === 'failed') {
            echo "\n✗ Workflow failed!\n";
            if (isset($status['error'])) {
                echo "Error: " . $status['error'] . "\n";
            }
            break;
        }
    }
    
    if ($attempt >= $maxAttempts) {
        echo "\n⚠️ Timeout after $maxAttempts attempts\n";
    }
    
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
