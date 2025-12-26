#!/usr/bin/env php
<?php
/**
 * Test script for Newsletter SDK
 *
 * Usage: IRIS_URL="http://localhost:8000" php test-sdk-newsletter.php
 */

require __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;

// Get environment variables
$apiKey = getenv('IRIS_API_KEY') ?: 'test-key';
$userId = (int) (getenv('IRIS_USER_ID') ?: '193');
$baseUrl = getenv('IRIS_URL') ?: 'http://localhost:8000';

echo "=== IRIS SDK Newsletter Test ===\n\n";
echo "Base URL: $baseUrl\n";
echo "User ID: $userId\n\n";

// Initialize SDK
$iris = new IRIS([
    'api_key' => $apiKey,
    'user_id' => $userId,
    'base_url' => $baseUrl,
]);

// TEST 1: Newsletter Research
echo "TEST 1: Newsletter Research\n";
echo str_repeat('-', 50) . "\n";

try {
    $researchResult = $iris->tools->newsletterResearch([
        'topic' => 'PHP 8.4 new features',
        'audience' => 'PHP developers',
        'tone' => 'educational',
        'newsletter_length' => 'brief',
    ]);

    echo "✅ Research completed!\n";
    echo "   Topic: {$researchResult->topic}\n";
    echo "   Themes found: " . count($researchResult->themes) . "\n";
    echo "   Outline options: " . count($researchResult->outlineOptions) . "\n";
    echo "   Awaiting input: " . ($researchResult->awaitingHumanInput ? 'Yes' : 'No') . "\n";
    echo "   Approval type: {$researchResult->approvalType}\n\n";

    // Show outline titles
    echo "   Outline options:\n";
    foreach ($researchResult->getOutlineTitles() as $i => $title) {
        echo "     " . ($i + 1) . ". $title\n";
    }
    echo "\n";

    // TEST 2: Prepare Write Params
    echo "TEST 2: Prepare Write Params (using prepareWriteParams helper)\n";
    echo str_repeat('-', 50) . "\n";

    $writeParams = $researchResult->prepareWriteParams(
        selectedOption: 1,
        customizationNotes: 'Focus on practical code examples',
        recipientEmail: 'developer@example.com',
        senderName: 'PHP Community'
    );

    echo "✅ Write params prepared!\n";
    echo "   Selected option: {$writeParams['selected_option']}\n";
    echo "   Outline options count: " . count($writeParams['outline_options']) . "\n";
    echo "   Context topic: " . ($writeParams['context']['topic'] ?? 'N/A') . "\n";
    echo "   Customization: " . ($writeParams['customization_notes'] ?? 'N/A') . "\n";
    echo "   Recipient: " . ($writeParams['recipient_email'] ?? 'N/A') . "\n\n";

    // TEST 3: Newsletter Write (optional - requires background job processing)
    echo "TEST 3: Newsletter Write (starts background job)\n";
    echo str_repeat('-', 50) . "\n";

    $writeResult = $iris->tools->newsletterWrite($writeParams);

    echo "✅ Newsletter write job started!\n";
    echo "   Result: " . json_encode($writeResult, JSON_PRETTY_PRINT) . "\n\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Trace:\n";
    foreach (array_slice($e->getTrace(), 0, 5) as $trace) {
        echo "     - " . ($trace['file'] ?? 'unknown') . ":" . ($trace['line'] ?? '?') . "\n";
    }

    // If it's an API exception, try to get more details
    if (method_exists($e, 'getResponse')) {
        echo "   Response: " . ($e->getResponse() ?? 'N/A') . "\n";
    }
}

echo "\n=== Test Complete ===\n";
