#!/usr/bin/env php
<?php
/**
 * Test Servis.ai Integration Setup via SDK
 */

require __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;
use IRIS\SDK\Auth\CredentialStore;

echo "=== Servis.ai Integration Test ===\n\n";

// Initialize SDK
$store = new CredentialStore();
$config = $store->toConfigArray();
$iris = new IRIS($config);

$env = getenv('IRIS_ENV') ?: 'production';
echo "Environment: {$env}\n";
echo "User ID: {$config['user_id']}\n\n";

// Servis.ai credentials
$clientId = '694547dc-7979-4674-8cb2-4734a2d92770';
$clientSecret = 'fa-secret-EC18052C262AE9EB7E5300';

// Step 1: Check if Servis.ai integration already exists
echo "--- Step 1: Check Existing Integrations ---\n";
try {
    $integrations = $iris->integrations->list();
    $existingServisAi = null;

    foreach ($integrations as $integration) {
        echo "  Found: {$integration->name} ({$integration->type}) - {$integration->status}\n";
        if ($integration->type === 'servis-ai') {
            $existingServisAi = $integration;
        }
    }

    if ($existingServisAi) {
        echo "\nServis.ai integration already exists (ID: {$existingServisAi->id})\n";
        echo "Deleting existing integration first...\n";
        $iris->integrations->delete($existingServisAi->id);
        echo "Deleted.\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "Error checking integrations: " . $e->getMessage() . "\n\n";
}

// Step 2: Create Servis.ai integration
echo "--- Step 2: Create Servis.ai Integration ---\n";
try {
    $integration = $iris->integrations->connectServisAi($clientId, $clientSecret);

    echo "Created integration:\n";
    echo "  ID: {$integration->id}\n";
    echo "  Name: {$integration->name}\n";
    echo "  Type: {$integration->type}\n";
    echo "  Status: {$integration->status}\n";
    echo "\n";
} catch (Exception $e) {
    echo "FAILED to create integration: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 3: Verify integration exists
echo "--- Step 3: Verify Integration ---\n";
try {
    $status = $iris->integrations->status('servis-ai');

    echo "Servis.ai Status:\n";
    echo "  Connected: " . ($status['connected'] ? 'Yes' : 'No') . "\n";
    if ($status['integration']) {
        echo "  Integration ID: {$status['integration']->id}\n";
        echo "  Status: {$status['integration']->status}\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "Error checking status: " . $e->getMessage() . "\n\n";
}

// Step 4: Test the integration
echo "--- Step 4: Test Integration Connection ---\n";
try {
    $testResult = $iris->integrations->test($integration->id);

    echo "Test Result:\n";
    echo "  Success: " . ($testResult->success ? 'Yes' : 'No') . "\n";
    if ($testResult->message) {
        echo "  Message: {$testResult->message}\n";
    }
    if (!empty($testResult->error)) {
        echo "  Error: {$testResult->error}\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "Test failed: " . $e->getMessage() . "\n\n";
}

// Step 5: List all integrations to confirm
echo "--- Step 5: Final Integration List ---\n";
try {
    $integrations = $iris->integrations->list();
    echo "Total integrations: " . count($integrations) . "\n";

    foreach ($integrations as $int) {
        $mark = $int->type === 'servis-ai' ? ' <-- NEW' : '';
        echo "  - {$int->name} ({$int->type}): {$int->status}{$mark}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
