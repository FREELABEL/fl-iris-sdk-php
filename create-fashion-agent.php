<?php
require __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;
use IRIS\SDK\Resources\Agents\AgentConfig; // Hypothetical class based on usage in TECHNICAL.md
use Dotenv\Dotenv;

// Load .env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$iris = new IRIS([
    'api_key' => $_ENV['IRIS_API_KEY'],
    'user_id' => (int) $_ENV['IRIS_USER_ID'],
]);

try {
    echo "Creating Fashion Designer Agent...\n";
    
    // Create the agent
    // Note: 'google-gemini' is the likely slug for the integration based on TECHNICAL.md "Google Gemini"
    $agent = $iris->agents->create(new AgentConfig(
        name: 'Fashion Designer & Stylist',
        prompt: "You are a world-class Fashion Designer and Stylist Agent.\n\n" .
                "YOUR GOAL: Help users visualize fashion by creating photo-realistic images of people wearing specific outfits.\n\n" .
                "CAPABILITIES:\n" .
                "- You have access to the 'google-gemini' integration which can generate and manipulate images.\n\n" .
                "WORKFLOW:\n" .
                "1. User provides an image of a Person and an image of an Outfit.\n" .
                "2. You analyze the style, fit, and lighting of both images.\n" .
                "3. You use your Gemini integration to GENERATE a new image of the Person wearing the Outfit. Be creative and act like a high-end fashion photographer.\n" .
                "4. Present the result with a fashion-forward description.",
        model: 'gemini-1.5-pro',
        integrations: ['google-gemini']
    ));

    echo "\n✅ Success! Agent Created:\n";
    echo "ID: " . $agent->id . "\n";
    echo "Name: " . $agent->name . "\n";
    echo "Model: " . $agent->model . "\n";
    echo "Integrations: " . json_encode($agent->integrations) . "\n";
    
    // Get the shareable URL
    $url = $agent->getSimpleUrl();
    echo "\n🔗 Shareable URL: " . $url . "\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    if (method_exists($e, 'getResponse')) {
        echo "Response: " . $e->getResponse()->getBody()->getContents() . "\n";
    }
}
