#!/usr/bin/env php
<?php
/**
 * Create Austin Texas Tour Guide Agent
 */

require_once __DIR__ . '/autoload.php';

use IRIS\SDK\IRIS;

$iris = new IRIS([]);

echo "Creating Austin Texas Tour Guide Agent...\n\n";

$agent = $iris->agents->createFromConfig([
    'name' => 'Austin Tour Guide',
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

echo "Agent Created!\n";
echo "  ID: {$agent->id}\n";
echo "  Name: {$agent->name}\n";
echo "  Bloq ID: {$agent->bloqId}\n\n";

// Get URLs
$urls = $iris->agents->getUrls($agent->id);
echo "URLs:\n";
echo "  Local Chat: https://local.elon.freelabel.net/agent/simple/{$agent->id}?bloq={$agent->bloqId}\n";
echo "  Production: {$urls['simple']}\n\n";

// Quick test
echo "Testing web search with the new agent...\n\n";

$response = $iris->agents->chat($agent->id, [
    ['role' => 'user', 'content' => "Hey! It's December 29th and we're looking to have an awesome day in Austin. What's happening today? Any cool events or activities you'd recommend for me (Alex) and my 15 year old son Treyton?"]
], [
    'bloq_id' => $agent->bloqId,
    'on_progress' => function($status) {
        echo "  [{$status['status']}]\n";
    }
]);

echo "\n=== Response ===\n\n";
echo $response->content . "\n\n";

echo "Agent ID: {$agent->id}\n";
echo "Chat URL: https://local.elon.freelabel.net/agent/simple/{$agent->id}?bloq={$agent->bloqId}\n";
