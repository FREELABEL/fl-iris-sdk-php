#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/autoload.php';

use IRIS\SDK\IRIS;
use IRIS\SDK\Evaluation\AgentEvaluator;
use IRIS\SDK\Evaluation\EvaluationTest;

/**
 * CLI Agent Evaluation Tool
 * Usage: php test-agent-cli-eval.php [agent-id] [test-type]
 * test-types: core, custom, comparison
 */

$agentId = (int) ($argv[1] ?? 387);
$testType = $argv[2] ?? 'core';

echo "🔬 CLI Agent Evaluation Tool\n";
echo "Agent ID: {$agentId}\n";
echo "Test Type: {$testType}\n\n";

// Load environment
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            [$key, $value] = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

$iris = new IRIS([
    'api_key' => getenv('IRIS_API_KEY') ?: (getenv('FL_RAICHU_API_TOKEN') ?: 'your-api-key-here'),
    'user_id' => (int) (getenv('IRIS_USER_ID') ?: 193),
]);

$evaluator = new AgentEvaluator($iris);

switch ($testType) {
    case 'core':
        echo "🎯 Running Core Functionality Tests...\n\n";
        $results = $evaluator->runCoreTests($agentId);
        break;

    case 'custom':
        echo "🔬 Running Custom Tests...\n\n";

        $customTests = [
            new EvaluationTest(
                'web_search',
                'Search for latest AI news and developments',
                [
                    'requires_web_search' => true,
                    'keywords' => ['AI', 'news', 'recent'],
                    'min_response_length' => 100
                ]
            ),
            new EvaluationTest(
                'personalization',
                'Remember I like technology. Give me a personalized tech update.',
                [
                    'should_personalize' => true,
                    'should_reference_interests' => true,
                    'min_response_length' => 100
                ]
            ),
            new EvaluationTest(
                'complex_planning',
                'Help me plan a 5-day vacation to Hawaii with budget considerations',
                [
                    'should_break_down_complex' => true,
                    'should_be_structured' => true,
                    'min_response_length' => 200
                ]
            ),
        ];

        $results = [];
        foreach ($customTests as $test) {
            $results[$test->name] = $evaluator->runTest($agentId, $test);
        }
        break;

    case 'comparison':
        echo "🔄 Running Comparative Tests (with/without web search)...\n\n";

        // Get current state
        $agent = $iris->agents->get($agentId);
        $originalWebSearch = $agent->settings['enabledFunctions']['deepResearch'] ?? false;

        // Test with web search enabled
        echo "Test 1: Web Search ENABLED\n";
        $iris->agents->patch($agentId, [
            'settings' => ['enabledFunctions' => ['deepResearch' => true]]
        ]);

        $testEnabled = new EvaluationTest(
            'web_search_enabled',
            'What are the latest developments in quantum computing?',
            [
                'requires_web_search' => true,
                'min_response_length' => 100,
                'max_response_time_ms' => 20000
            ]
        );
        $results['web_search_enabled'] = $evaluator->runTest($agentId, $testEnabled);

        echo "Score: {$results['web_search_enabled']['evaluation']['score']}%\n\n";

        // Test with web search disabled
        echo "Test 2: Web Search DISABLED\n";
        $iris->agents->patch($agentId, [
            'settings' => ['enabledFunctions' => ['deepResearch' => false]]
        ]);

        $testDisabled = new EvaluationTest(
            'web_search_disabled',
            'What are the latest developments in quantum computing?',
            [
                'requires_web_search' => true,
                'min_response_length' => 100,
                'max_response_time_ms' => 20000
            ]
        );
        $results['web_search_disabled'] = $evaluator->runTest($agentId, $testDisabled);

        echo "Score: {$results['web_search_disabled']['evaluation']['score']}%\n\n";

        // Restore original
        $iris->agents->patch($agentId, [
            'settings' => ['enabledFunctions' => ['deepResearch' => $originalWebSearch]]
        ]);

        echo "🔄 Restored original settings\n";
        break;

    default:
        echo "❌ Unknown test type: {$testType}\n";
        echo "Available: core, custom, comparison\n";
        exit(1);
}

// Generate report
$report = $evaluator->generateReport($results);
echo $report;

// Save results
$filename = "agent-eval-{$testType}-{$agentId}-" . date('Y-m-d-H-i-s') . '.json';
file_put_contents($filename, json_encode($results, JSON_PRETTY_PRINT));
echo "📁 Results saved to: {$filename}\n";

// Show quick summary
$totalTests = count($results);
$passed = array_filter($results, fn($r) => $r['success'] ?? false);
$passRate = $totalTests > 0 ? round((count($passed) / $totalTests) * 100, 0) : 0;

echo "\n⚡ QUICK SUMMARY:\n";
echo "Pass Rate: {$passRate}% (" . count($passed) . "/{$totalTests})\n";
echo "Status: " . ($passRate >= 70 ? '🟢 GOOD' : ($passRate >= 50 ? '🟡 OK' : '🔴 NEEDS WORK')) . "\n";
