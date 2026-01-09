<?php

require_once __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;
use IRIS\SDK\Config;

/**
 * RAG Quality Testing for John Ayala's Delivered Agents
 * 
 * Tests file attachment indexing and retrieval quality for:
 * - Agent #349 (Ayala Strategy Agent) with 5 file attachments
 * - Agent #366 (Ayala + Goodbuy Assistant) with no attachments
 */

$config = [
    'api_key' => getenv('IRIS_API_KEY'),
    'user_id' => (int) getenv('IRIS_USER_ID'),
];

$iris = new IRIS($config);

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║  RAG QUALITY TESTING - John Ayala Delivered Agents       ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

/**
 * Test Agent #349 - Ayala Strategy Agent
 * Expected: Should have access to 5 PDFs/DOCX files via RAG
 */
function testAgent349RAG(IRIS $iris): array
{
    echo "🔍 Testing Agent #349 (Ayala Strategy Agent)\n";
    echo str_repeat('-', 60) . "\n";
    
    $agentId = 349;
    $bloqId = 203;
    
    // Get file attachments
    $attachments = $iris->agents->getFileAttachments($agentId);
    echo "✅ File Attachments: " . count($attachments) . " files found\n";
    
    foreach ($attachments as $file) {
        echo "   - {$file->name} ({$file->processingStatus})\n";
    }
    echo "\n";
    
    $tests = [
        [
            'name' => 'Single Document RAG',
            'query' => "What is Dr. John Ayala's educational background according to his CV?",
            'expected_keywords' => ['Doctor of Engineering', 'Texas A&M', 'Electrical Engineering', 'Industrial Engineering'],
            'min_length' => 200,
        ],
        [
            'name' => 'Multi-Document RAG',
            'query' => "Compare Dr. John Ayala and Dr. Norma Guerra's qualifications from their CVs",
            'expected_keywords' => ['Ayala', 'Guerra', 'education', 'experience'],
            'min_length' => 300,
        ],
        [
            'name' => 'DOCX File RAG',
            'query' => "List all the trainers mentioned in the Trainer Profiles document",
            'expected_keywords' => ['trainer', 'profile'],
            'min_length' => 150,
        ],
        [
            'name' => 'Specific Detail Extraction',
            'query' => "What is Alex Mayo's professional title according to his resume?",
            'expected_keywords' => ['Alex Mayo', 'title', 'role'],
            'min_length' => 50,
        ],
    ];
    
    $results = [];
    
    foreach ($tests as $test) {
        echo "📝 Test: {$test['name']}\n";
        echo "   Query: \"{$test['query']}\"\n";
        
        $startTime = microtime(true);
        
        try {
            $response = $iris->agents->chat($agentId, [
                ['role' => 'user', 'content' => $test['query']]
            ], [
                'bloq_id' => $bloqId,
                'use_rag' => true,
            ]);
            
            $endTime = microtime(true);
            $timeMs = (int)(($endTime - $startTime) * 1000);
            
            $content = $response->content ?? $response->message ?? '';
            $contentLower = strtolower($content);
            
            // Check keywords
            $keywordsFound = [];
            foreach ($test['expected_keywords'] as $keyword) {
                if (stripos($content, $keyword) !== false) {
                    $keywordsFound[] = $keyword;
                }
            }
            
            $keywordRatio = count($keywordsFound) / count($test['expected_keywords']);
            $lengthOk = strlen($content) >= $test['min_length'];
            
            $passed = $keywordRatio >= 0.5 && $lengthOk;
            
            $results[] = [
                'test' => $test['name'],
                'passed' => $passed,
                'response_length' => strlen($content),
                'response_time_ms' => $timeMs,
                'keyword_ratio' => $keywordRatio,
                'keywords_found' => $keywordsFound,
                'response_preview' => substr($content, 0, 150),
            ];
            
            $status = $passed ? '✅ PASS' : '❌ FAIL';
            echo "   {$status} | Length: " . strlen($content) . " chars | Time: {$timeMs}ms | Keywords: " . round($keywordRatio * 100) . "%\n";
            echo "   Preview: " . substr(str_replace("\n", " ", $content), 0, 100) . "...\n\n";
            
        } catch (Exception $e) {
            echo "   ❌ ERROR: {$e->getMessage()}\n\n";
            $results[] = [
                'test' => $test['name'],
                'passed' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    return $results;
}

/**
 * Test Agent #366 - Ayala + Goodbuy Assistant
 * Expected: No file attachments, should rely on base knowledge
 */
function testAgent366NoRAG(IRIS $iris): array
{
    echo "\n🔍 Testing Agent #366 (Ayala + Goodbuy Assistant)\n";
    echo str_repeat('-', 60) . "\n";
    
    $agentId = 366;
    $bloqId = 40;
    
    // Get file attachments
    $attachments = $iris->agents->getFileAttachments($agentId);
    echo "📁 File Attachments: " . count($attachments) . " files found";
    if (count($attachments) === 0) {
        echo " (Expected - no RAG configured)\n\n";
    } else {
        echo " (Unexpected!)\n\n";
    }
    
    $tests = [
        [
            'name' => 'General Knowledge Query',
            'query' => "What are the key principles of strategic planning?",
            'min_length' => 200,
        ],
        [
            'name' => 'Expertise Area Query',
            'query' => "How can educational leadership courses be designed for maximum impact?",
            'min_length' => 250,
        ],
    ];
    
    $results = [];
    
    foreach ($tests as $test) {
        echo "📝 Test: {$test['name']}\n";
        echo "   Query: \"{$test['query']}\"\n";
        
        $startTime = microtime(true);
        
        try {
            $response = $iris->agents->chat($agentId, [
                ['role' => 'user', 'content' => $test['query']]
            ], [
                'bloq_id' => $bloqId,
            ]);
            
            $endTime = microtime(true);
            $timeMs = (int)(($endTime - $startTime) * 1000);
            
            $content = $response->content ?? $response->message ?? '';
            $lengthOk = strlen($content) >= $test['min_length'];
            
            $results[] = [
                'test' => $test['name'],
                'passed' => $lengthOk,
                'response_length' => strlen($content),
                'response_time_ms' => $timeMs,
                'response_preview' => substr($content, 0, 150),
            ];
            
            $status = $lengthOk ? '✅ PASS' : '❌ FAIL';
            echo "   {$status} | Length: " . strlen($content) . " chars | Time: {$timeMs}ms\n";
            echo "   Preview: " . substr(str_replace("\n", " ", $content), 0, 100) . "...\n\n";
            
        } catch (Exception $e) {
            echo "   ❌ ERROR: {$e->getMessage()}\n\n";
            $results[] = [
                'test' => $test['name'],
                'passed' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    return $results;
}

// Run tests
$agent349Results = testAgent349RAG($iris);
$agent366Results = testAgent366NoRAG($iris);

// Summary
echo "\n╔═══════════════════════════════════════════════════════════╗\n";
echo "║                    SUMMARY REPORT                         ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

$agent349Passed = count(array_filter($agent349Results, fn($r) => $r['passed']));
$agent349Total = count($agent349Results);
$agent349PassRate = $agent349Total > 0 ? round(($agent349Passed / $agent349Total) * 100) : 0;

$agent366Passed = count(array_filter($agent366Results, fn($r) => $r['passed']));
$agent366Total = count($agent366Results);
$agent366PassRate = $agent366Total > 0 ? round(($agent366Passed / $agent366Total) * 100) : 0;

echo "Agent #349 (RAG-enabled):\n";
echo "  Pass Rate: {$agent349PassRate}% ({$agent349Passed}/{$agent349Total})\n";
echo "  Status: " . ($agent349PassRate >= 75 ? '🟢 EXCELLENT' : ($agent349PassRate >= 50 ? '🟡 OK' : '🔴 NEEDS WORK')) . "\n\n";

echo "Agent #366 (No RAG):\n";
echo "  Pass Rate: {$agent366PassRate}% ({$agent366Passed}/{$agent366Total})\n";
echo "  Status: " . ($agent366PassRate >= 75 ? '🟢 EXCELLENT' : ($agent366PassRate >= 50 ? '🟡 OK' : '🔴 NEEDS WORK')) . "\n\n";

$overallPass = $agent349Passed + $agent366Passed;
$overallTotal = $agent349Total + $agent366Total;
$overallPassRate = $overallTotal > 0 ? round(($overallPass / $overallTotal) * 100) : 0;

echo "Overall:\n";
echo "  Pass Rate: {$overallPassRate}% ({$overallPass}/{$overallTotal})\n";
echo "  Status: " . ($overallPassRate >= 75 ? '🟢 EXCELLENT' : ($overallPassRate >= 50 ? '🟡 OK' : '🔴 NEEDS WORK')) . "\n";

// Save results
$reportFile = 'rag-quality-report-' . date('Y-m-d-H-i-s') . '.json';
file_put_contents($reportFile, json_encode([
    'timestamp' => date('c'),
    'lead_id' => 110,
    'lead_name' => 'Dr. John F. Ayala',
    'agent_349' => [
        'agent_id' => 349,
        'name' => 'Ayala Strategy Agent',
        'bloq_id' => 203,
        'file_attachments' => 5,
        'pass_rate' => $agent349PassRate,
        'results' => $agent349Results,
    ],
    'agent_366' => [
        'agent_id' => 366,
        'name' => 'Ayala + Goodbuy Assistant',
        'bloq_id' => 40,
        'file_attachments' => 0,
        'pass_rate' => $agent366PassRate,
        'results' => $agent366Results,
    ],
    'summary' => [
        'overall_pass_rate' => $overallPassRate,
        'tests_passed' => $overallPass,
        'tests_total' => $overallTotal,
    ],
], JSON_PRETTY_PRINT));

echo "\n✅ Report saved to: {$reportFile}\n";

// Exit code based on overall pass rate
exit($overallPassRate >= 50 ? 0 : 1);
