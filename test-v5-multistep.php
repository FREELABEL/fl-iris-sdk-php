#!/usr/bin/env php
<?php
/**
 * Test V5 multiStep workflow with web search
 */

require_once __DIR__ . '/autoload.php';

use IRIS\SDK\IRIS;

$iris = new IRIS([]);

$agentId = 20; // News Content Bot with webAccess: true

echo "🚀 Testing V5 multiStep with Web Search\n";
echo str_repeat('=', 50) . "\n\n";

$query = "Search for the latest AI news headlines from this week. Give me 3 specific items.";

echo "Query: {$query}\n\n";
echo "Starting workflow...\n";

try {
    $workflow = $iris->agents->multiStep($agentId, $query, [
        'bloq_id' => 40,
    ]);

    echo "Workflow ID: {$workflow->id}\n";
    echo "Status: {$workflow->status}\n\n";

    echo "Polling for completion...\n";

    // Use the generator to track progress
    foreach ($workflow->steps() as $step) {
        echo "  [{$step->progress}%] {$step->status}: {$step->description}\n";
    }

    echo "\n✅ Workflow completed!\n\n";

    // Get final result
    $result = $workflow->result();

    echo "Result content (" . strlen($result->content ?? '') . " chars):\n";
    echo str_repeat('-', 40) . "\n";
    echo substr($result->content ?? 'No content', 0, 500) . "\n";
    if (strlen($result->content ?? '') > 500) {
        echo "...[truncated]\n";
    }
    echo str_repeat('-', 40) . "\n";

} catch (Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
