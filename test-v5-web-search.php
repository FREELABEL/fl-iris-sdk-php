#!/usr/bin/env php
<?php
/**
 * Test V5 Chat with Web Search
 *
 * This tests that webAccess/webSearch works correctly with V5 workflows.
 */

require_once __DIR__ . '/autoload.php';

use IRIS\SDK\IRIS;

$iris = new IRIS([]);

echo "Testing V5 chat with web search...\n\n";

$agentId = 11;  // Test agent - should have webAccess enabled
$bloqId = 32;   // Test bloq

// First, let's check/enable webAccess on the agent
echo "1. Checking agent webAccess setting...\n";
$agent = $iris->agents->get($agentId);
$settings = $agent->settings ?? [];
echo "   Current settings: " . json_encode($settings, JSON_PRETTY_PRINT) . "\n\n";

// Check if webAccess is enabled
$hasWebAccess = $settings['webAccess'] ?? false;
echo "   webAccess enabled: " . ($hasWebAccess ? 'YES' : 'NO') . "\n\n";

if (!$hasWebAccess) {
    echo "   Enabling webAccess on agent...\n";
    $settings['webAccess'] = true;
    $iris->agents->update($agentId, ['settings' => $settings]);
    echo "   Done!\n\n";
}

// Now test the chat with a web search query
echo "2. Sending web search query...\n";
try {
    $response = $iris->agents->chat($agentId, [
        ['role' => 'user', 'content' => 'What is the latest news about AI today? Search the web for recent headlines.']
    ], [
        'bloq_id' => $bloqId,
        'on_progress' => function($status) {
            $step = $status['current_step'] ?? 'unknown';
            echo "   [Progress] Status: {$status['status']}, Step: {$step}\n";
        }
    ]);

    echo "\n3. Result:\n";
    echo "   Status: {$response->status}\n";
    echo "   Workflow ID: {$response->workflowId}\n";
    echo "   Is V5 Workflow: " . ($response->isV5Workflow() ? 'YES' : 'NO') . "\n";
    echo "   Execution Results: " . count($response->executionResults) . " results\n";
    echo "\n   Content (first 500 chars):\n";
    echo "   " . str_replace("\n", "\n   ", substr($response->content, 0, 500)) . "...\n";

    echo "\n✅ V5 web search successful!\n";

} catch (Exception $e) {
    echo "\n❌ Error: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";
}
