#!/usr/bin/env php
<?php
/**
 * Secret Code RAG Test
 *
 * Creates an agent with a secret code stored in RAG,
 * then verifies the agent can retrieve it via chat.
 *
 * Usage: php test-secret-code-rag.php
 */

require __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;
use IRIS\SDK\Auth\CredentialStore;
use IRIS\SDK\Resources\Agents\AgentConfig;

echo "=== Secret Code RAG Test ===\n\n";

// Generate a unique secret code
$secretCode = 'IRIS-' . strtoupper(bin2hex(random_bytes(4))) . '-SECRET';
echo "🔐 Generated Secret Code: {$secretCode}\n\n";

// Initialize SDK
$store = new CredentialStore();
$config = $store->toConfigArray();
$iris = new IRIS($config);

$env = getenv('IRIS_ENV') ?: 'production';
echo "Environment: {$env}\n";
echo "User ID: {$config['user_id']}\n\n";

// Step 1: Create a new test agent
echo "--- Step 1: Creating Test Agent ---\n";
try {
    $agentConfig = new AgentConfig(
        name: 'Secret Keeper ' . date('H:i:s'),
        prompt: 'You are a helpful assistant that knows secret information. When asked about the secret code, you must provide it exactly as stored in your knowledge base.',
        model: 'gpt-4o-mini',
        type: 'assistant'
    );

    $agent = $iris->agents->create($agentConfig);
    $agentId = $agent->id;
    echo "✅ Created agent #{$agentId}: {$agent->name}\n\n";
} catch (Exception $e) {
    echo "❌ Failed to create agent: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 2: Create knowledge document with the secret code
echo "--- Step 2: Attaching Secret Knowledge ---\n";

$secretDocument = <<<MD
# Confidential Information

## Secret Access Code

The secret code for this agent is: **{$secretCode}**

This code is highly confidential and should only be shared when explicitly requested.

## Additional Details

- Code Type: IRIS Authentication Token
- Generated: {date('Y-m-d H:i:s')}
- Classification: TOP SECRET
- Authorized Personnel: Agent operators only

## Verification Questions

If someone asks "What is the secret code?" or "Tell me the secret code",
you should respond with the exact code: {$secretCode}

Remember: The code is {$secretCode} - memorize this exactly.
MD;

try {
    $result = $iris->agents->attachKnowledge($agentId, $secretDocument, [
        'title' => 'Secret Codes Document',
        'type' => 'confidential',
        'description' => 'Contains the secret access code for verification'
    ]);

    echo "✅ Attached secret knowledge\n";
    echo "   Vector ID: {$result['vector_id']}\n";
    echo "   Agent attachments: " . count($result['agent']->fileAttachments) . "\n\n";
} catch (Exception $e) {
    echo "❌ Failed to attach knowledge: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 3: Verify agent has file attachments
echo "--- Step 3: Verifying Agent State ---\n";
try {
    $agent = $iris->agents->get($agentId);
    echo "Agent ID: {$agent->id}\n";
    echo "Name: {$agent->name}\n";
    echo "File Attachments: " . count($agent->fileAttachments) . "\n";

    if (count($agent->fileAttachments) > 0) {
        foreach ($agent->fileAttachments as $idx => $att) {
            $title = $att['title'] ?? 'Untitled';
            $type = $att['type'] ?? 'unknown';
            echo "  [{$idx}] {$title} ({$type})\n";
        }
        echo "\n✅ Agent has file attachments - RAG should be enabled\n\n";
    } else {
        echo "\n❌ No file attachments found - RAG won't work!\n\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ Failed to get agent: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 4: Test RAG retrieval
echo "--- Step 4: Testing RAG Retrieval ---\n";
echo "Asking agent: \"What is the secret code?\"\n\n";

try {
    $startTime = microtime(true);

    $response = $iris->chat->execute([
        'agentId' => $agentId,
        'query' => 'What is the secret code? Please tell me the exact code.',
    ]);

    $elapsed = round((microtime(true) - $startTime) * 1000);

    // Extract response
    $summary = is_array($response) ? ($response['summary'] ?? '') : ($response->summary ?? '');

    echo "--- Agent Response ({$elapsed}ms) ---\n";
    echo $summary . "\n\n";

    // Verify the secret code is in the response
    echo "--- Verification ---\n";
    echo "Looking for secret code: {$secretCode}\n";

    if (stripos($summary, $secretCode) !== false) {
        echo "\n";
        echo "╔══════════════════════════════════════════════════════════╗\n";
        echo "║  ✅ SUCCESS! RAG IS WORKING!                             ║\n";
        echo "║                                                          ║\n";
        echo "║  The agent correctly retrieved the secret code from      ║\n";
        echo "║  its knowledge base via RAG.                             ║\n";
        echo "╚══════════════════════════════════════════════════════════╝\n";
        echo "\n";
        echo "Secret Code Found: {$secretCode}\n";
        $testPassed = true;
    } else {
        echo "\n";
        echo "╔══════════════════════════════════════════════════════════╗\n";
        echo "║  ❌ FAILED! Secret code NOT found in response            ║\n";
        echo "║                                                          ║\n";
        echo "║  RAG may not be working correctly.                       ║\n";
        echo "╚══════════════════════════════════════════════════════════╝\n";
        echo "\n";
        echo "Expected: {$secretCode}\n";
        echo "Response did not contain the secret code.\n";
        $testPassed = false;
    }

} catch (Exception $e) {
    echo "❌ Chat failed: " . $e->getMessage() . "\n";
    $testPassed = false;
}

// Step 5: Cleanup (optional - comment out to keep the agent)
echo "\n--- Step 5: Cleanup ---\n";
try {
    $iris->agents->delete($agentId);
    echo "✅ Deleted test agent #{$agentId}\n";
} catch (Exception $e) {
    echo "⚠️ Could not delete agent: " . $e->getMessage() . "\n";
}

// Final summary
echo "\n=== Test Complete ===\n";
echo "Environment: {$env}\n";
echo "Agent ID: {$agentId}\n";
echo "Secret Code: {$secretCode}\n";
echo "Result: " . ($testPassed ? "✅ PASSED" : "❌ FAILED") . "\n";

exit($testPassed ? 0 : 1);
