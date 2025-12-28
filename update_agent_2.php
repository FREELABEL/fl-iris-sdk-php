<?php
require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$iris = new IRIS\SDK\IRIS([
    'api_key' => $_ENV['IRIS_API_KEY'],
    'user_id' => (int)$_ENV['IRIS_USER_ID'],
]);

// Get current agent
echo "Fetching agent #2...\n";
$agent = $iris->agents->get(2);
$currentSettings = is_array($agent->settings) ? $agent->settings : json_decode(json_encode($agent->settings), true);

echo "Current settings keys: " . implode(', ', array_keys($currentSettings)) . "\n";

// Add identity to settings - CORRECTED CONTACT INFO
$currentSettings['identity'] = [
    'name' => 'Alex Mayo',
    'role' => 'Founder & CEO',
    'company' => 'IRIS AI',
    'email' => 'alex@freelabel.net',
    'phone' => '713-912-7520',
    'replyTo' => 'alex@freelabel.net',
    'calendarLink' => 'https://calendar.app.google/FKWE8Wy5RKicgz2v5'
];

echo "Updating agent...\n";
$result = $iris->agents->update(2, [
    'settings' => $currentSettings
]);

echo "✅ Updated Agent #2 identity:\n";
echo json_encode($result->settings['identity'] ?? 'NOT FOUND', JSON_PRETTY_PRINT);
echo "\n";
