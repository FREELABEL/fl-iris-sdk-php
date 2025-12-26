#!/usr/bin/env php
<?php
/**
 * Quick test to verify Integration Management MVP is working
 */

require_once __DIR__ . '/../vendor/autoload.php';

use IRIS\SDK\IRIS;
use IRIS\SDK\Config;

echo "🧪 Testing Integration Management MVP\n";
echo "=====================================\n\n";

// Test 1: SDK Initialization
echo "✓ Test 1: SDK loads successfully\n";
$iris = new IRIS([
    'api_key' => 'test-key',
    'user_id' => 1,
    'base_url' => 'https://api.freelabel.net'
]);

// Test 2: IntegrationsResource exists
echo "✓ Test 2: IntegrationsResource accessible\n";
$integrations = $iris->integrations;
assert($integrations instanceof IRIS\SDK\Resources\Integrations\IntegrationsResource);

// Test 3: MVP methods exist
echo "✓ Test 3: Checking MVP methods exist...\n";
$methods = [
    'status',
    'connected',
    'disconnect',
    'connectWithApiKey',
    'connectVapi',
    'connectServisAi',
    'connectSmtp',
    'startOAuthFlow',
    'usesOAuth',
    'usesApiKey',
];

foreach ($methods as $method) {
    if (!method_exists($integrations, $method)) {
        echo "  ✗ Missing method: {$method}\n";
        exit(1);
    }
    echo "  ✓ {$method}()\n";
}

// Test 4: Collection helpers exist
echo "✓ Test 4: Checking collection helper methods...\n";
$collectionMethods = [
    'findByType',
    'filterByStatus',
    'filterByCategory',
];

$reflectionClass = new ReflectionClass(IRIS\SDK\Resources\Integrations\IntegrationCollection::class);
foreach ($collectionMethods as $method) {
    if (!$reflectionClass->hasMethod($method)) {
        echo "  ✗ Missing collection method: {$method}\n";
        exit(1);
    }
    echo "  ✓ {$method}()\n";
}

// Test 5: CLI command exists
echo "✓ Test 5: CLI command file exists\n";
$commandFile = __DIR__ . '/../src/Console/Commands/IntegrationsCommand.php';
if (!file_exists($commandFile)) {
    echo "  ✗ IntegrationsCommand.php not found\n";
    exit(1);
}

// Test 6: Command is registered
echo "✓ Test 6: Command registered in Application\n";
$appFile = __DIR__ . '/../src/Console/Application.php';
$appContents = file_get_contents($appFile);
if (strpos($appContents, 'IntegrationsCommand') === false) {
    echo "  ✗ IntegrationsCommand not registered\n";
    exit(1);
}

// Test 7: Type detection methods work
echo "✓ Test 7: Type detection methods work\n";
assert($integrations->usesOAuth('slack') === true, 'Slack should use OAuth');
assert($integrations->usesOAuth('vapi') === false, 'Vapi should not use OAuth');
assert($integrations->usesApiKey('vapi') === true, 'Vapi should use API key');
assert($integrations->usesApiKey('slack') === false, 'Slack should not use API key');
echo "  ✓ OAuth detection\n";
echo "  ✓ API key detection\n";

echo "\n";
echo "=====================================\n";
echo "✅ All tests passed!\n";
echo "=====================================\n\n";

echo "Integration Management MVP is ready to use!\n\n";

echo "Try these commands:\n";
echo "  ./bin/iris integrations list\n";
echo "  ./bin/iris integrations types\n";
echo "  ./bin/iris integrations connect vapi\n";
echo "  ./bin/iris integrations status vapi\n\n";

exit(0);
