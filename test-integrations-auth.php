#!/usr/bin/env php
<?php
/**
 * Test Integrations Authentication Fix
 *
 * Verifies that the SDK can access integrations endpoints
 * without requiring Passport OAuth authentication.
 *
 * Usage: php test-integrations-auth.php
 */

require __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;
use IRIS\SDK\Auth\CredentialStore;

echo "=== Integrations Authentication Test ===\n\n";

// Initialize SDK
$store = new CredentialStore();
$config = $store->toConfigArray();
$iris = new IRIS($config);

$env = getenv('IRIS_ENV') ?: 'production';
echo "Environment: {$env}\n";
echo "User ID: {$config['user_id']}\n";
echo "FL-API URL: {$config['fl_api_url']}\n\n";

$passed = 0;
$failed = 0;

// Test 1: List integrations
echo "--- Test 1: List Integrations ---\n";
try {
    $integrations = $iris->integrations->list();
    $count = count($integrations);
    echo "Found {$count} integrations\n";

    if ($count > 0) {
        foreach ($integrations as $idx => $integration) {
            if ($idx >= 3) {
                echo "  ... and " . ($count - 3) . " more\n";
                break;
            }
            echo "  - {$integration->name} ({$integration->type}): {$integration->status}\n";
        }
    }

    echo "PASSED\n\n";
    $passed++;
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n\n";
    $failed++;
}

// Test 2: Get integration types
echo "--- Test 2: Get Integration Types ---\n";
try {
    $types = $iris->integrations->types();

    if (is_array($types)) {
        $typeCount = isset($types['data']) ? count($types['data']) : count($types);
        echo "Found {$typeCount} integration types\n";
        echo "PASSED\n\n";
        $passed++;
    } else {
        echo "FAILED: Unexpected response format\n\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n\n";
    $failed++;
}

// Test 3: Check connection status for a known integration type
echo "--- Test 3: Check Integration Status ---\n";
try {
    $status = $iris->integrations->status('google-drive');

    echo "Google Drive connected: " . ($status['connected'] ? 'Yes' : 'No') . "\n";
    echo "PASSED\n\n";
    $passed++;
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n\n";
    $failed++;
}

// Test 4: Get connected integrations
echo "--- Test 4: Get Connected Integrations ---\n";
try {
    $connected = $iris->integrations->connected();
    $count = count($connected);

    echo "Found {$count} connected (active) integrations\n";
    echo "PASSED\n\n";
    $passed++;
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n\n";
    $failed++;
}

// Summary
echo "=== Test Summary ===\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo "Result: " . ($failed === 0 ? "ALL TESTS PASSED" : "SOME TESTS FAILED") . "\n";

exit($failed === 0 ? 0 : 1);
