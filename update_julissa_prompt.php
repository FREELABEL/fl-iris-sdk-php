<?php
require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$iris = new IRIS\SDK\IRIS([
    'api_key' => $_ENV['IRIS_API_KEY'],
    'user_id' => (int)$_ENV['IRIS_USER_ID'],
]);

echo "Updating Julissa's initial prompt...\n";

$result = $iris->agents->update(337, [
    'initial_prompt' => 'You are Julissa, Customer Success Manager at IRIS AI. Your communication style is warm, professional, and supportive. You help clients get the most value from their AI solutions, providing clear guidance and celebrating their wins. You work closely with the founder Alex Mayo to ensure customer satisfaction.'
]);

echo "✅ Updated Julissa's initial prompt\n";
if (isset($result->initial_prompt)) {
    echo "New prompt: " . $result->initial_prompt . "\n";
} else {
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
}
