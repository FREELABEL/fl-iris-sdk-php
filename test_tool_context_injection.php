#!/usr/bin/env php
<?php
/**
 * Test Script: Agent Tool Context Injection Bug
 * 
 * This script tests whether disabled tools are being injected into agent context.
 * 
 * Usage:
 *   php test_tool_context_injection.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;

// Colors for output
define('RED', "\033[31m");
define('GREEN', "\033[32m");
define('YELLOW', "\033[33m");
define('BLUE', "\033[34m");
define('RESET', "\033[0m");

function status($emoji, $color, $text) {
    echo $color . $emoji . " " . $text . RESET . "\n";
}

$config = [
    'api_key' => getenv('IRIS_API_KEY'),
    'user_id' => (int) getenv('IRIS_USER_ID'),
];

$iris = new IRIS($config);

echo BLUE . "╔════════════════════════════════════════════════════════════╗\n";
echo "║  TOOL CONTEXT INJECTION BUG TEST                           ║\n";
echo "║  Testing: Agent #349 (deepResearch: false)                ║\n";
echo "╚════════════════════════════════════════════════════════════╝" . RESET . "\n\n";

// Test configuration
$agentId = 349;
$bloqId = 203;

echo YELLOW . "📋 Test Setup\n" . RESET;
echo str_repeat('-', 60) . "\n";

// Step 1: Verify agent settings
status('🔍', BLUE, "Step 1: Verifying agent settings...");
$agent = $iris->agents->get($agentId);

echo "  Agent ID: {$agentId}\n";
echo "  Name: " . (is_array($agent) ? ($agent['name'] ?? 'Unknown') : $agent->name) . "\n";
echo "  Bloq ID: {$bloqId}\n";

$settings = is_array($agent) ? ($agent['settings'] ?? []) : $agent->settings;
$enabledFunctions = $settings['enabledFunctions'] ?? [];
$useKnowledgeBase = $settings['useKnowledgeBase'] ?? false;

echo "  useKnowledgeBase: " . ($useKnowledgeBase ? GREEN . 'true' : RED . 'false') . RESET . "\n";
echo "  deepResearch: " . ($enabledFunctions['deepResearch'] ?? false ? GREEN . 'true' : RED . 'false') . RESET . "\n";

if ($enabledFunctions['deepResearch'] ?? false) {
    status('⚠️', YELLOW, "WARNING: deepResearch is ENABLED - should be disabled for this test");
    echo "\n  To disable it, run:\n";
    echo "  ./bin/iris sdk:call agents.patch {$agentId} '{\"settings\":{\"enabledFunctions\":{\"deepResearch\":false}}}'\n\n";
    exit(1);
}

status('✅', GREEN, "deepResearch is disabled (correct)");
echo "\n";

// Step 2: Verify file attachments
status('🔍', BLUE, "Step 2: Verifying file attachments...");
$attachments = $iris->agents->getFileAttachments($agentId);
echo "  Files attached: " . count($attachments) . "\n";

$docxFile = null;
foreach ($attachments as $file) {
    $fileName = is_array($file) ? $file['name'] : $file->name;
    $status = is_array($file) ? $file['processingStatus'] : $file->processingStatus;
    
    echo "    - {$fileName} ({$status})\n";
    
    if (stripos($fileName, 'Trainer') !== false) {
        $docxFile = $fileName;
    }
}

if ($docxFile) {
    status('✅', GREEN, "Found Trainer Profiles document: {$docxFile}");
} else {
    status('⚠️', YELLOW, "WARNING: Trainer Profiles document not found");
}
echo "\n";

// Step 3: Test queries that should use RAG
status('🧪', BLUE, "Step 3: Testing RAG vs Tool Selection...");
echo str_repeat('-', 60) . "\n\n";

$tests = [
    [
        'name' => 'DOCX File Query (Should use RAG)',
        'query' => 'What trainers are mentioned in the Trainer Profiles document? List their names.',
        'should_use_rag' => true,
        'should_not_mention' => ['DeepResearchTool', 'Tool is not available', 'not available for this agent'],
        'timeout' => 15,
    ],
    [
        'name' => 'PDF File Query (Should use RAG)',
        'query' => 'What is Dr. John Ayala\'s email address from his CV?',
        'should_use_rag' => true,
        'should_not_mention' => ['DeepResearchTool', 'Tool is not available'],
        'timeout' => 15,
    ],
    [
        'name' => 'General Knowledge (Should use base knowledge)',
        'query' => 'What are the key principles of strategic planning?',
        'should_use_rag' => false,
        'should_not_mention' => ['DeepResearchTool', 'Tool is not available'],
        'timeout' => 15,
    ],
];

$results = [];
$bugDetected = false;

foreach ($tests as $test) {
    echo YELLOW . "Test: {$test['name']}\n" . RESET;
    echo "Query: \"{$test['query']}\"\n";
    echo "Timeout: {$test['timeout']}s\n";
    
    $startTime = microtime(true);
    $timedOut = false;
    $response = '';
    $error = null;
    
    try {
        // Set a custom timeout using stream context
        $response = $iris->agents->chat($agentId, [
            ['role' => 'user', 'content' => $test['query']]
        ], [
            'bloq_id' => $bloqId,
            'use_rag' => $test['should_use_rag'],
        ]);
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        $content = $response->content ?? $response->message ?? '';
        
        // Check for timeout (heuristic: > timeout + 5s)
        if ($duration > ($test['timeout'] + 5)) {
            $timedOut = true;
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $content = '';
    }
    
    // Analyze response
    $foundProblems = [];
    
    if ($timedOut) {
        $foundProblems[] = "Request timed out ({$duration}s)";
    }
    
    if ($error) {
        $foundProblems[] = "Error: {$error}";
    }
    
    if (empty($content) && !$error) {
        $foundProblems[] = "Empty response";
    }
    
    foreach ($test['should_not_mention'] as $forbidden) {
        if (stripos($content, $forbidden) !== false) {
            $foundProblems[] = "Mentioned forbidden term: '{$forbidden}'";
            $bugDetected = true;
        }
    }
    
    // Print result
    if (empty($foundProblems)) {
        status('✅', GREEN, "PASS");
        echo "  Duration: " . round($duration, 2) . "s\n";
        echo "  Response length: " . strlen($content) . " chars\n";
        echo "  Preview: " . substr(str_replace("\n", " ", $content), 0, 80) . "...\n";
        $results[] = ['test' => $test['name'], 'passed' => true];
    } else {
        status('❌', RED, "FAIL");
        foreach ($foundProblems as $problem) {
            echo RED . "  ⚠ {$problem}\n" . RESET;
        }
        echo "  Response: " . substr($content, 0, 200) . "\n";
        $results[] = ['test' => $test['name'], 'passed' => false, 'problems' => $foundProblems];
    }
    
    echo "\n";
}

// Summary
echo BLUE . "╔════════════════════════════════════════════════════════════╗\n";
echo "║  TEST SUMMARY                                              ║\n";
echo "╚════════════════════════════════════════════════════════════╝" . RESET . "\n\n";

$passed = count(array_filter($results, fn($r) => $r['passed']));
$total = count($results);
$passRate = round(($passed / $total) * 100);

echo "Tests passed: {$passed}/{$total} ({$passRate}%)\n";

if ($bugDetected) {
    status('🐛', RED, "BUG DETECTED: Agent is attempting to use disabled tools!");
    echo "\n";
    echo RED . "Evidence:\n" . RESET;
    echo "  - Agent settings show deepResearch: false\n";
    echo "  - Agent response mentions 'DeepResearchTool' or 'not available'\n";
    echo "  - This means the tool is being injected into context despite being disabled\n\n";
    
    echo YELLOW . "Recommended Actions:\n" . RESET;
    echo "  1. Check fl-iris-api/app/Services/AI/PromptBuilder.php\n";
    echo "  2. Verify getToolsForAgent() filters by enabledFunctions\n";
    echo "  3. Ensure OpenAI 'tools' parameter only includes enabled tools\n";
    echo "  4. See BUG_REPORT_TOOL_CONTEXT_INJECTION.md for full details\n\n";
    
    exit(1);
} else {
    status('✅', GREEN, "All tests passed! Tool context injection appears to be working correctly.");
    echo "\n";
    exit(0);
}
