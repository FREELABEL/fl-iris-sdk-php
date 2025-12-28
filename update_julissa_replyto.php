<?php
require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$iris = new IRIS\SDK\IRIS([
    'api_key' => $_ENV['IRIS_API_KEY'],
    'user_id' => (int)$_ENV['IRIS_USER_ID'],
]);

// Get current Julissa agent (Agent #337)
echo "Fetching Julissa agent #337...\n";
$agent = $iris->agents->get(337);
$currentSettings = is_array($agent->settings) ? $agent->settings : json_decode(json_encode($agent->settings), true);

// Update identity with support@heyiris.io as replyTo
$currentSettings['identity'] = [
    'name' => 'Julissa',
    'role' => 'Customer Success Manager',
    'company' => 'IRIS AI',
    'email' => 'support@heyiris.io',
    'phone' => '713-912-7520',
    'replyTo' => 'support@heyiris.io',
    'calendarLink' => 'https://calendar.app.google/FKWE8Wy5RKicgz2v5'
];

echo "Updating Julissa agent with support@heyiris.io...\n";
$result = $iris->agents->update(337, [
    'settings' => $currentSettings
]);

echo "✅ Updated Julissa (Agent #337) identity:\n";
echo json_encode($result->settings['identity'] ?? 'NOT FOUND', JSON_PRETTY_PRINT);
echo "\n";
