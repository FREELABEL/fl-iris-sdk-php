#!/usr/bin/env php
<?php
/**
 * Test Single RAG Query with Full Debug
 */

require __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;
use IRIS\SDK\Auth\CredentialStore;

$store = new CredentialStore();
$config = $store->toConfigArray();
$iris = new IRIS($config);

echo "=== Single RAG Test with Debug ===\n\n";

$agentId = 387;
$question = "What medications does Dorothy take in the morning?";

echo "Agent ID: {$agentId}\n";
echo "Question: {$question}\n\n";

// Get agent to show state
$agent = $iris->agents->get($agentId);
echo "Agent has file_attachments: " . (count($agent->fileAttachments) > 0 ? 'YES (' . count($agent->fileAttachments) . ')' : 'NO') . "\n";
echo "Agent bloq_id: " . ($agent->bloqId ?? 'null') . "\n\n";

echo "--- Starting Chat ---\n";
try {
    $response = $iris->chat->start([
        'query' => $question,
        'agentId' => $agentId,
    ]);
    
    echo "Workflow Started:\n";
    echo "  Workflow ID: {$response['workflow_id']}\n";
    echo "  Message: {$response['message']}\n\n";
    
    $workflowId = $response['workflow_id'];
    
    // Poll for result
    echo "--- Polling for Result ---\n";
    $attempts = 0;
    $maxAttempts = 60; // 30 seconds
    
    while ($attempts < $maxAttempts) {
        sleep(1);
        $attempts++;
        
        $status = $iris->chat->getStatus($workflowId);
        echo "  [{$attempts}] Status: {$status['status']}\n";
        
        if ($status['status'] === 'completed') {
            echo "\n--- Response ---\n";
            echo $status['summary'] . "\n\n";
            
            // Check for expected terms
            $content = $status['summary'];
            $expectedTerms = ['Lisinopril', 'Metformin', 'Aspirin'];
            $foundTerms = [];
            foreach ($expectedTerms as $term) {
                if (stripos($content, $term) !== false) {
                    $foundTerms[] = $term;
                }
            }
            
            echo "--- Analysis ---\n";
            echo "Expected medications: " . implode(', ', $expectedTerms) . "\n";
            echo "Found in response: " . (count($foundTerms) ? implode(', ', $foundTerms) : 'NONE') . "\n\n";
            
            if (count($foundTerms) >= 2) {
                echo "✅ RAG IS WORKING!\n";
            } else {
                echo "❌ RAG NOT WORKING - Agent doesn't know the medications\n";
                echo "\nThis suggests the file_attachments are not being used for RAG retrieval.\n";
                echo "Check production logs for:\n";
                echo "  - '🔧 AUTO-ENABLING RAG: Agent has file attachments'\n";
                echo "  - 'SummaryNode: Using BloqRAG agent'\n";
                echo "  - Filter values in RAG query\n";
            }
            
            break;
        }
        
        if ($status['status'] === 'failed') {
            echo "\n❌ Workflow failed:\n";
            echo json_encode($status, JSON_PRETTY_PRINT) . "\n";
            break;
        }
    }
    
    if ($attempts >= $maxAttempts) {
        echo "\n⏱️  Timeout after {$maxAttempts} seconds\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
