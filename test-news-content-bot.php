#!/usr/bin/env php
<?php
/**
 * MVP Test: News Content Bot with Web Search
 *
 * Tests:
 * 1. Create agent with webSearch enabled
 * 2. Verify settings are applied
 * 3. Test web search for AI + Real Estate news
 * 4. Generate 7-day content schedule
 */

require_once __DIR__ . '/autoload.php';

use IRIS\SDK\IRIS;
use IRIS\SDK\Evaluation\AgentEvaluator;
use IRIS\SDK\Evaluation\EvaluationTest;

// Note: IRIS SDK auto-loads .env from its Config class
// No need to manually load - it handles IRIS_ENV=local automatically

echo "🚀 News Content Bot - MVP Test\n";
echo str_repeat('=', 50) . "\n\n";

// IRIS SDK auto-loads from .env (respects IRIS_ENV=local)
$iris = new IRIS([]);

// ============================================
// STEP 1: Create Agent with Web Search
// ============================================
echo "📝 Step 1: Creating News Content Bot...\n";

$agentConfig = [
    'name' => 'News Content Bot - ' . date('M j H:i'),
    'type' => 'content',
    'bloq_id' => 40,
    'initial_prompt' => <<<PROMPT
You are a News Content Strategist specializing in AI and Real Estate trends.

Your capabilities:
1. Search the web for latest news and trends
2. Analyze news for content opportunities
3. Create social media content calendars
4. Generate article ideas and outlines

When asked to create content schedules:
- Provide specific, actionable post ideas
- Include suggested hashtags
- Mix educational, entertaining, and promotional content
- Reference current events and trends from your research

Always cite sources when referencing specific news or data.
PROMPT,
    'config' => [
        'model' => 'gpt-4o-mini',
        'temperature' => 0.7,
    ],
    'settings' => [
        'webAccess' => true,  // API checks this field for web search
        'enabledFunctions' => [
            'webSearch' => true,
            'deepResearch' => false,  // Phase 2
        ],
        'responseMode' => 'balanced',
        'memoryPersistence' => true,
    ],
];

try {
    $agent = $iris->agents->createFromArray($agentConfig);
    echo "✅ Agent created: {$agent->name} (ID: {$agent->id})\n";
} catch (Exception $e) {
    echo "❌ Failed to create agent: {$e->getMessage()}\n";
    exit(1);
}

// ============================================
// STEP 2: Verify Settings Applied
// ============================================
echo "\n📋 Step 2: Verifying settings...\n";

$fetchedAgent = $iris->agents->get($agent->id);
$settings = $fetchedAgent->settings ?? [];
$webAccess = $settings['webAccess'] ?? false;
$enabledFunctions = $settings['enabledFunctions'] ?? [];

echo "   webAccess: " . ($webAccess ? '✅ ON' : '❌ OFF') . " (API checks this)\n";
echo "   enabledFunctions.webSearch: " . (($enabledFunctions['webSearch'] ?? false) ? '✅ ON' : '❌ OFF') . "\n";
echo "   enabledFunctions.deepResearch: " . (($enabledFunctions['deepResearch'] ?? false) ? '✅ ON' : '❌ OFF') . "\n";

// If webAccess not enabled, try to enable it
if (!$webAccess) {
    echo "\n⚠️  webAccess not enabled, attempting to update settings...\n";
    $iris->agents->update($agent->id, [
        'settings' => array_merge($settings, ['webAccess' => true]),
    ]);

    // Re-fetch and verify
    $fetchedAgent = $iris->agents->get($agent->id);
    $settings = $fetchedAgent->settings ?? [];
    echo "   webAccess: " . (($settings['webAccess'] ?? false) ? '✅ ON' : '❌ STILL OFF') . "\n";
}

// ============================================
// STEP 3: Test Web Search
// ============================================
echo "\n🔍 Step 3: Testing web search capability...\n";

$searchPrompt = "Search for the latest news about AI in real estate from the past week. Give me 3 specific news items with sources.";

echo "   Prompt: {$searchPrompt}\n";
echo "   Sending...\n";

$startTime = microtime(true);
try {
    $response = $iris->agents->chat($agent->id, [
        ['role' => 'user', 'content' => $searchPrompt],
    ], ['bloq_id' => 40]);

    $responseTime = round((microtime(true) - $startTime) * 1000);
    $content = $response->content ?? '';

    echo "\n   Response ({$responseTime}ms, " . strlen($content) . " chars):\n";
    echo "   " . str_repeat('-', 45) . "\n";

    // Show first 500 chars
    $preview = substr($content, 0, 500);
    foreach (explode("\n", $preview) as $line) {
        echo "   " . $line . "\n";
    }
    if (strlen($content) > 500) {
        echo "   ...[truncated]\n";
    }
    echo "   " . str_repeat('-', 45) . "\n";

    // Quick validation
    $hasContent = strlen($content) > 50;
    $mentionsAI = stripos($content, 'AI') !== false || stripos($content, 'artificial intelligence') !== false;
    $mentionsRealEstate = stripos($content, 'real estate') !== false || stripos($content, 'property') !== false || stripos($content, 'housing') !== false;

    echo "\n   Validation:\n";
    echo "   - Has content (>50 chars): " . ($hasContent ? '✅' : '❌') . "\n";
    echo "   - Mentions AI: " . ($mentionsAI ? '✅' : '❌') . "\n";
    echo "   - Mentions Real Estate: " . ($mentionsRealEstate ? '✅' : '❌') . "\n";

    $searchWorking = $hasContent && ($mentionsAI || $mentionsRealEstate);

} catch (Exception $e) {
    echo "   ❌ Error: {$e->getMessage()}\n";
    $searchWorking = false;
}

// ============================================
// STEP 4: Generate 7-Day Content Schedule
// ============================================
echo "\n📅 Step 4: Generating 7-day content schedule...\n";

$schedulePrompt = <<<PROMPT
Based on current AI and Real Estate trends, create a 7-day social media content schedule with 2 posts per day.

Format for each day:
Day X (Topic Theme):
- Post 1 (Time): [Content idea] #hashtags
- Post 2 (Time): [Content idea] #hashtags

Include a mix of:
- News commentary
- Tips and insights
- Engagement questions
- Industry statistics
PROMPT;

echo "   Generating schedule...\n";

$startTime = microtime(true);
try {
    $response = $iris->agents->chat($agent->id, [
        ['role' => 'user', 'content' => $schedulePrompt],
    ], ['bloq_id' => 40]);

    $responseTime = round((microtime(true) - $startTime) * 1000);
    $scheduleContent = $response->content ?? '';

    echo "\n   Schedule ({$responseTime}ms, " . strlen($scheduleContent) . " chars):\n";
    echo "   " . str_repeat('-', 45) . "\n";

    // Show full schedule (up to 2000 chars)
    $preview = substr($scheduleContent, 0, 2000);
    foreach (explode("\n", $preview) as $line) {
        echo "   " . $line . "\n";
    }
    if (strlen($scheduleContent) > 2000) {
        echo "   ...[truncated]\n";
    }
    echo "   " . str_repeat('-', 45) . "\n";

    // Validate schedule
    $hasDays = preg_match_all('/Day\s*\d|Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday/i', $scheduleContent);
    $hasPosts = preg_match_all('/Post\s*\d|:\d{1,2}(am|pm|:)/i', $scheduleContent);
    $hasHashtags = substr_count($scheduleContent, '#');

    echo "\n   Validation:\n";
    echo "   - Days mentioned: {$hasDays} " . ($hasDays >= 5 ? '✅' : '⚠️') . "\n";
    echo "   - Posts/times found: {$hasPosts} " . ($hasPosts >= 10 ? '✅' : '⚠️') . "\n";
    echo "   - Hashtags: {$hasHashtags} " . ($hasHashtags >= 5 ? '✅' : '⚠️') . "\n";

    $scheduleWorking = strlen($scheduleContent) > 500 && $hasDays >= 3;

} catch (Exception $e) {
    echo "   ❌ Error: {$e->getMessage()}\n";
    $scheduleWorking = false;
}

// ============================================
// SUMMARY
// ============================================
echo "\n" . str_repeat('=', 50) . "\n";
echo "📊 SUMMARY\n";
echo str_repeat('=', 50) . "\n";

echo "Agent ID: {$agent->id}\n";
echo "Agent Name: {$agent->name}\n";
echo "Web Search Test: " . ($searchWorking ? '✅ WORKING' : '❌ NEEDS TROUBLESHOOTING') . "\n";
echo "Schedule Generation: " . ($scheduleWorking ? '✅ WORKING' : '❌ NEEDS TROUBLESHOOTING') . "\n";

$overallSuccess = $searchWorking && $scheduleWorking;
echo "\nOverall: " . ($overallSuccess ? '🟢 MVP WORKING' : '🔴 NEEDS FIXES') . "\n";

// Cleanup option
echo "\n💡 To delete this test agent: \$iris->agents->delete({$agent->id})\n";

exit($overallSuccess ? 0 : 1);
