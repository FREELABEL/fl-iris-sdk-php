#!/usr/bin/env php
<?php
/**
 * Final V5 SDK Verification Test
 *
 * Tests all the V5 changes:
 * 1. agents->chat() uses V5 workflows internally
 * 2. webAccess setting management
 * 3. ChatResponse includes V5 workflow fields
 */

require_once __DIR__ . '/autoload.php';

use IRIS\SDK\IRIS;

echo "=== V5 SDK Verification Test ===\n\n";

$iris = new IRIS([]);
$agentId = 11;

// Test 1: Check webAccess methods
echo "1. Testing webAccess methods...\n";
$hasWebAccess = $iris->agents->hasWebAccess($agentId);
echo "   Agent {$agentId} has webAccess: " . ($hasWebAccess ? 'YES' : 'NO') . "\n\n";

// Test 2: V5 Chat (simple query)
echo "2. Testing V5 chat (simple query)...\n";
try {
    $response = $iris->agents->chat($agentId, [
        ['role' => 'user', 'content' => 'Say "V5 is working!" and nothing else.']
    ], ['bloq_id' => 32]);

    echo "   Status: {$response->status}\n";
    echo "   Workflow ID: " . ($response->workflowId ?? 'N/A') . "\n";
    echo "   Is V5 Workflow: " . ($response->isV5Workflow() ? 'YES' : 'NO') . "\n";
    echo "   Content: {$response->content}\n\n";

    if ($response->isV5Workflow() && $response->isCompleted()) {
        echo "   PASS - V5 chat working correctly\n\n";
    } else {
        echo "   FAIL - V5 chat not working as expected\n\n";
    }
} catch (Exception $e) {
    echo "   ERROR: {$e->getMessage()}\n\n";
}

// Test 3: ChatResponse methods
echo "3. Testing ChatResponse methods...\n";
echo "   response->isCompleted(): " . ($response->isCompleted() ? 'true' : 'false') . "\n";
echo "   response->isFailed(): " . ($response->isFailed() ? 'true' : 'false') . "\n";
echo "   response->isPaused(): " . ($response->isPaused() ? 'true' : 'false') . "\n";
echo "   response->toArray(): " . count($response->toArray()) . " keys\n\n";

// Test 4: AgentSettings class
echo "4. Testing AgentSettings class...\n";
$settings = \IRIS\SDK\Resources\Agents\AgentSettings::fromArray([
    'webAccess' => true,
    'enabledBuiltInFunctions' => ['executeWebSearch' => true],
]);
echo "   Created settings with webAccess: " . ($settings->hasWebAccess() ? 'YES' : 'NO') . "\n";
echo "   Has executeWebSearch: " . ($settings->hasBuiltInFunction('executeWebSearch') ? 'YES' : 'NO') . "\n\n";

echo "=== All V5 SDK Tests Complete ===\n";
