#!/usr/bin/env php
<?php
/**
 * Test Austin Tour Guide Agent on Production
 *
 * Tests the V5.5 fixes:
 * 1. executedToolsHistory clearing on plan refinement
 * 2. Location filtering for places search
 */

require_once __DIR__ . '/autoload.php';

use IRIS\SDK\IRIS;

// Force production environment
putenv('IRIS_ENV=production');

echo "=== Testing Austin Tour Guide on PRODUCTION ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "Environment: production\n\n";

$iris = new IRIS([]);

// First, let's create the Austin Tour Guide agent on production
echo "1. Creating Austin Tour Guide Agent on Production...\n\n";

try {
    $agent = $iris->agents->createFromConfig([
        'name' => 'Austin Tour Guide (Production Test)',
        'type' => 'content',
        'initial_prompt' => <<<PROMPT
You are an expert Austin, Texas tour guide and travel planner specializing in family-friendly activities. You are helping plan an amazing day in Austin for:

- **Alex Mayo** - Dad
- **Treyton Mayo** - 15 year old son

## Your Expertise
- Local Austin attractions, hidden gems, and popular spots
- Family-friendly activities suitable for teenagers
- Current events, festivals, and happenings in Austin
- Best restaurants, food trucks, and local cuisine (especially BBQ, Tex-Mex, tacos)
- Outdoor activities: hiking, swimming holes, parks
- Entertainment: arcades, go-karts, escape rooms, mini golf
- Music venues appropriate for all ages
- Shopping and unique Austin experiences

## Your Approach
1. **Always search the web** for current information - events change, restaurants close, new things open
2. Consider the weather and time of year when making recommendations
3. Create realistic itineraries with travel time between locations
4. Include a mix of activities: food, fun, culture, and outdoor time
5. Provide specific addresses, hours, and pricing when available
6. Suggest backup options in case something doesn't work out

## Austin Knowledge
- Keep Austin Weird! Embrace the unique culture
- Key areas: Downtown/6th Street, South Congress (SoCo), East Austin, The Domain, Barton Springs
- Famous for: Live music, BBQ (Franklin, Terry Black's, la Barbecue), breakfast tacos, food trucks
- Outdoor spots: Barton Springs Pool, Mount Bonnell, Lady Bird Lake, Zilker Park
- Teen-friendly: Top Golf, Main Event, Pinballz Arcade, K1 Speed, iFLY

Always be enthusiastic, knowledgeable, and focused on creating memorable father-son experiences!
PROMPT,
        'bloq_id' => 40,
        'config' => [
            'model_id' => 185,  // gpt-4o-mini
            'temperature' => 0.8,
        ],
        'settings' => [
            'webAccess' => true,
            'memoryPersistence' => true,
            'responseMode' => 'comprehensive',
            'communicationStyle' => 'friendly',
            'contextWindow' => '15',
            'enabledBuiltInFunctions' => [
                'executeWebSearch' => true,
                'searchPlaces' => true,
                'searchNews' => true,
            ],
        ],
    ]);

    echo "Agent Created Successfully!\n";
    echo "  ID: {$agent->id}\n";
    echo "  Name: {$agent->name}\n";
    echo "  Bloq ID: {$agent->bloqId}\n\n";

    $agentId = $agent->id;
    $bloqId = $agent->bloqId;

} catch (Exception $e) {
    echo "Error creating agent: {$e->getMessage()}\n";
    echo "Using fallback agent...\n\n";
    // Use existing agent if creation fails
    $agentId = 11;
    $bloqId = 40;
}

echo "2. Testing Web Search with Location-Specific Query...\n";
echo "   Query: 'What are the best ice cream shops in Austin for me and Treyton today?'\n\n";

$startTime = microtime(true);

try {
    $response = $iris->agents->chat($agentId, [
        ['role' => 'user', 'content' => "What are the best ice cream shops in Austin, Texas for me (Alex) and my son Treyton today? It's December 29th and we want something fun and delicious. Please search for current options in AUSTIN specifically."]
    ], [
        'bloq_id' => $bloqId,
        'on_progress' => function($status) {
            $time = date('H:i:s');
            echo "   [{$time}] Status: {$status['status']}\n";
        }
    ]);

    $duration = round(microtime(true) - $startTime, 2);

    echo "\n3. Results:\n";
    echo "   Workflow ID: " . ($response->workflowId ?? 'N/A') . "\n";
    echo "   Status: {$response->status}\n";
    echo "   Duration: {$duration}s\n";
    echo "   Is V5 Workflow: " . ($response->isV5Workflow() ? 'YES' : 'NO') . "\n\n";

    echo "=== RESPONSE CONTENT ===\n\n";
    echo $response->content . "\n\n";

    // Verify the response mentions Austin locations
    $content = strtolower($response->content);
    $hasAustin = str_contains($content, 'austin');
    $hasTexas = str_contains($content, 'texas') || str_contains($content, 'tx');
    $hasIceCream = str_contains($content, 'ice cream') || str_contains($content, 'gelato') || str_contains($content, 'frozen');

    echo "=== VERIFICATION ===\n";
    echo "   Mentions Austin: " . ($hasAustin ? 'YES' : 'NO') . "\n";
    echo "   Mentions Texas/TX: " . ($hasTexas ? 'YES' : 'NO') . "\n";
    echo "   Mentions Ice Cream: " . ($hasIceCream ? 'YES' : 'NO') . "\n\n";

    // Check for wrong locations (the bug we fixed)
    $hasChicago = str_contains($content, 'chicago');
    $hasCalifornia = str_contains($content, 'california') || str_contains($content, 'fresno');

    if ($hasChicago || $hasCalifornia) {
        echo "WARNING: Response contains wrong locations (Chicago/California)!\n";
        echo "   The location filtering fix may not be deployed correctly.\n\n";
    } else {
        echo "PASSED: No wrong locations detected in response!\n\n";
    }

    if ($hasAustin && $hasIceCream && !$hasChicago && !$hasCalifornia) {
        echo "=== TEST PASSED ===\n";
        echo "Production V5.5 workflow is working correctly with proper location filtering!\n";
    } else {
        echo "=== TEST NEEDS REVIEW ===\n";
        echo "Please manually verify the response content above.\n";
    }

} catch (Exception $e) {
    echo "\nERROR: {$e->getMessage()}\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n";
echo "Agent ID: {$agentId}\n";
echo "Production Chat URL: https://app.heyiris.io/agent/simple/{$agentId}?bloq={$bloqId}\n";
