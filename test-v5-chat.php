#!/usr/bin/env php
<?php
/**
 * Test V5 Chat via agents->chat()
 *
 * This tests the new V5 workflow integration in the SDK.
 */

require_once __DIR__ . '/autoload.php';

use IRIS\SDK\IRIS;

$iris = new IRIS([]);

echo "Testing V5 chat via \$iris->agents->chat()...\n\n";

$agentId = 11;  // Test agent
$bloqId = 32;   // Test bloq

try {
    $response = $iris->agents->chat($agentId, [
        ['role' => 'user', 'content' => 'What is 2+2?']
    ], [
        'bloq_id' => $bloqId,
        'on_progress' => function($status) {
            echo "  [Progress] Status: {$status['status']}\n";
        }
    ]);

    echo "\nResult:\n";
    echo "  Status: " . ($response->status ?? 'unknown') . "\n";
    echo "  Workflow ID: " . ($response->workflowId ?? 'N/A') . "\n";
    echo "  Content: " . substr($response->content ?? '', 0, 200) . "...\n";

    echo "\n✅ V5 chat successful!\n";

} catch (Exception $e) {
    echo "\n❌ Error: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";
}
