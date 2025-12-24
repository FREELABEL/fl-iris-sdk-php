#!/usr/bin/env php
<?php
/**
 * Test Agent RAG Functionality
 *
 * Usage: IRIS_ENV=local php test-agent-rag.php [agent_id]
 */

require __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;
use IRIS\SDK\Auth\CredentialStore;

$agentId = $argv[1] ?? 12;

echo "=== Agent RAG Debug Test ===\n\n";

// 1. Initialize SDK
$store = new CredentialStore();
$config = $store->toConfigArray();
$iris = new IRIS($config);

echo "Environment: " . (getenv('IRIS_ENV') ?: 'production') . "\n";
echo "User ID: {$config['user_id']}\n\n";

// 2. Get agent details
echo "--- Agent Details ---\n";
try {
    $agent = $iris->agents->get($agentId);
    echo "Agent ID: {$agent->id}\n";
    echo "Name: {$agent->name}\n";
    echo "Bloq ID: " . ($agent->bloqId ?? 'null') . "\n";
    echo "Use RAG: " . (isset($agent->settings['use_rag']) ? ($agent->settings['use_rag'] ? 'true' : 'false') : 'not set') . "\n";

    $attachments = $agent->fileAttachments ?? [];
    echo "File Attachments: " . count($attachments) . "\n";
    foreach ($attachments as $att) {
        echo "  - [{$att['cloud_file_id']}] {$att['name']} ({$att['type']})\n";
    }
} catch (Exception $e) {
    echo "Error getting agent: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Test question that requires RAG
$testQuestion = "What is the CLEAR communication framework? List each letter and its meaning.";

echo "\n--- RAG Test ---\n";
echo "Question: $testQuestion\n\n";

// 4. Send chat request with debug
echo "Sending request...\n";
$startTime = microtime(true);

try {
    $response = $iris->chat->execute([
        'agentId' => $agentId,
        'bloqId' => $agent->bloqId, // Required for RAG context
        'query' => $testQuestion,
    ]);
    $elapsed = round(microtime(true) - $startTime, 2);

    echo "\n--- Response ({$elapsed}s) ---\n";
    echo $response->content ?? $response->message ?? json_encode($response);
    echo "\n";

    // Check if response contains expected content from our file
    // Response is array with 'summary' key
    $content = is_array($response) ? ($response['summary'] ?? '') : ($response->summary ?? $response->content ?? $response->message ?? '');
    // Strip markdown bold formatting (**text**) for matching
    $contentClean = preg_replace('/\*\*([^*]+)\*\*/', '$1', $content);
    $expectedTerms = ['Concise', 'Listening', 'Empathetic', 'Actionable', 'Responsive'];
    $foundTerms = [];
    foreach ($expectedTerms as $term) {
        if (stripos($contentClean, $term) !== false) {
            $foundTerms[] = $term;
        }
    }

    echo "\n--- RAG Verification ---\n";
    echo "Expected terms from file: " . implode(', ', $expectedTerms) . "\n";
    echo "Found in response: " . (count($foundTerms) ? implode(', ', $foundTerms) : 'NONE') . "\n";

    if (count($foundTerms) >= 3) {
        echo "\n✅ RAG appears to be working - response contains file content\n";
    } else {
        echo "\n❌ RAG may NOT be working - response doesn't match file content\n";
        echo "   The file defines CLEAR as: Concise, Listening, Empathetic, Actionable, Responsive\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
