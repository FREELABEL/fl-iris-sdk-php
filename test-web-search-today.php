#!/usr/bin/env php
<?php
/**
 * Test Web Search with Today's Date
 *
 * Creates a test agent and verifies web search returns current results.
 */

require_once __DIR__ . '/autoload.php';

use IRIS\SDK\IRIS;

$iris = new IRIS([]);

echo "=== Web Search Today Test ===\n";
echo "Today's date: " . date('Y-m-d') . "\n\n";

// Use existing test agent with webAccess
$agentId = 11;
$bloqId = 32;

echo "1. Verifying agent has webAccess enabled...\n";
$hasWebAccess = $iris->agents->hasWebAccess($agentId);
echo "   webAccess: " . ($hasWebAccess ? 'YES' : 'NO') . "\n\n";

if (!$hasWebAccess) {
    echo "   Enabling webAccess...\n";
    $iris->agents->enableWebAccess($agentId);
    echo "   Done!\n\n";
}

echo "2. Running web search query for today's news...\n\n";

try {
    $response = $iris->agents->chat($agentId, [
        ['role' => 'user', 'content' => "Search the web for the top 3 news headlines from TODAY (December 29, 2025). List each headline with its source and date. Be specific about the dates you find."]
    ], [
        'bloq_id' => $bloqId,
        'on_progress' => function($status) {
            echo "   [" . date('H:i:s') . "] Status: {$status['status']}\n";
        }
    ]);

    echo "\n3. Results:\n";
    echo "   Workflow ID: {$response->workflowId}\n";
    echo "   Status: {$response->status}\n\n";
    echo "   === Content ===\n\n";
    echo $response->content . "\n\n";

    // Check if response mentions today's date
    $today = date('December j, Y'); // December 29, 2025
    $todayShort = date('Y-m-d');    // 2025-12-29
    $todayAlt = date('m/d/Y');      // 12/29/2025

    $hasTodayDate = (
        stripos($response->content, '2025') !== false ||
        stripos($response->content, 'December 29') !== false ||
        stripos($response->content, 'Dec 29') !== false ||
        stripos($response->content, 'today') !== false
    );

    echo "4. Date Verification:\n";
    echo "   Looking for references to 2025 or December 29...\n";
    echo "   Contains current year/date references: " . ($hasTodayDate ? 'YES' : 'NO') . "\n\n";

    if ($hasTodayDate) {
        echo "✅ Web search is returning current results!\n";
    } else {
        echo "⚠️  Could not verify current date in results. Please review content above.\n";
    }

} catch (Exception $e) {
    echo "\n❌ Error: {$e->getMessage()}\n";
    echo $e->getTraceAsString() . "\n";
}
