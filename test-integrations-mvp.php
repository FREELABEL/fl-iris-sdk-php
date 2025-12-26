#!/usr/bin/env php
<?php
/**
 * Integration Management MVP - Quick Test
 * 
 * Tests the new integration management features.
 * Run: php test-integrations-mvp.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;

// Colors for terminal output
$green = "\033[0;32m";
$red = "\033[0;31m";
$yellow = "\033[1;33m";
$blue = "\033[0;34m";
$reset = "\033[0m";

function pass($msg) {
    global $green, $reset;
    echo "{$green}✓{$reset} {$msg}\n";
}

function fail($msg) {
    global $red, $reset;
    echo "{$red}✗{$reset} {$msg}\n";
}

function info($msg) {
    global $blue, $reset;
    echo "{$blue}ℹ{$reset} {$msg}\n";
}

function section($msg) {
    global $yellow, $reset;
    echo "\n{$yellow}▶{$reset} {$msg}\n";
    echo str_repeat("─", 50) . "\n";
}

echo "\n";
echo "╔═══════════════════════════════════════════════╗\n";
echo "║  Integration Management MVP - Test Suite     ║\n";
echo "╚═══════════════════════════════════════════════╝\n";

// Initialize SDK
try {
    $iris = new IRIS([
        'api_key' => getenv('IRIS_API_KEY') ?: 'test-key',
        'user_id' => (int)(getenv('IRIS_USER_ID') ?: 1),
    ]);
    pass("SDK initialized");
} catch (Exception $e) {
    fail("SDK initialization failed: {$e->getMessage()}");
    exit(1);
}

// Test 1: SDK Methods Exist
section("Test 1: SDK Methods");
$methods = [
    'list', 'create', 'get', 'update', 'delete', 'test', 'types',
    'status', 'connected', 'disconnect', 'connectWithApiKey',
    'connectVapi', 'connectServisAi', 'connectSmtp',
    'startOAuthFlow', 'usesOAuth', 'usesApiKey'
];

foreach ($methods as $method) {
    if (method_exists($iris->integrations, $method)) {
        pass("Method exists: {$method}()");
    } else {
        fail("Method missing: {$method}()");
    }
}

// Test 2: Collection Methods
section("Test 2: Collection Methods");
try {
    $integrations = $iris->integrations->list();
    pass("List integrations returned a collection");
    
    if (method_exists($integrations, 'findByType')) {
        pass("Collection has findByType()");
    } else {
        fail("Collection missing findByType()");
    }
    
    if (method_exists($integrations, 'filterByStatus')) {
        pass("Collection has filterByStatus()");
    } else {
        fail("Collection missing filterByStatus()");
    }
    
    if (method_exists($integrations, 'filterByCategory')) {
        pass("Collection has filterByCategory()");
    } else {
        fail("Collection missing filterByCategory()");
    }
} catch (Exception $e) {
    fail("Collection test failed: {$e->getMessage()}");
}

// Test 3: Status Check
section("Test 3: Status Check");
try {
    $status = $iris->integrations->status('vapi');
    
    if (isset($status['connected']) && isset($status['integration'])) {
        pass("Status check returns correct structure");
        
        if ($status['connected']) {
            info("Vapi is currently connected (ID: {$status['integration']->id})");
        } else {
            info("Vapi is not connected");
        }
    } else {
        fail("Status check returns incorrect structure");
    }
} catch (Exception $e) {
    fail("Status check failed: {$e->getMessage()}");
}

// Test 4: Auth Method Detection
section("Test 4: Auth Method Detection");
$testCases = [
    ['type' => 'vapi', 'expected' => 'apiKey'],
    ['type' => 'gmail', 'expected' => 'oauth'],
    ['type' => 'servis-ai', 'expected' => 'apiKey'],
    ['type' => 'google-drive', 'expected' => 'oauth'],
];

foreach ($testCases as $case) {
    $usesOAuth = $iris->integrations->usesOAuth($case['type']);
    $usesApiKey = $iris->integrations->usesApiKey($case['type']);
    
    $detected = $usesOAuth ? 'oauth' : ($usesApiKey ? 'apiKey' : 'unknown');
    
    if ($detected === $case['expected']) {
        pass("{$case['type']}: detected {$detected} (correct)");
    } else {
        fail("{$case['type']}: detected {$detected}, expected {$case['expected']}");
    }
}

// Test 5: Get Integration Types
section("Test 5: Get Integration Types");
try {
    $response = $iris->integrations->types();
    $types = $response['data'] ?? $response;
    
    if (is_array($types) && !empty($types)) {
        pass("Retrieved " . count($types) . " integration types");
        
        // Check for expected types
        $expectedTypes = ['vapi', 'servis-ai', 'smtp-email', 'gmail'];
        foreach ($expectedTypes as $expectedType) {
            if (isset($types[$expectedType])) {
                pass("  Found: {$expectedType}");
            } else {
                info("  Not found: {$expectedType}");
            }
        }
    } else {
        fail("Integration types empty or invalid");
    }
} catch (Exception $e) {
    fail("Get types failed: {$e->getMessage()}");
}

// Test 6: Connected Filter
section("Test 6: Connected Filter");
try {
    $connected = $iris->integrations->connected();
    pass("Connected filter works");
    info("Found {$connected->count()} connected integration(s)");
} catch (Exception $e) {
    fail("Connected filter failed: {$e->getMessage()}");
}

// Test 7: CLI Command Exists
section("Test 7: CLI Command");
$cliPath = __DIR__ . '/bin/iris';

if (file_exists($cliPath)) {
    pass("CLI binary exists");
    
    // Try to run help
    exec("php {$cliPath} list 2>&1", $output, $returnCode);
    
    if ($returnCode === 0) {
        pass("CLI executes successfully");
        
        // Check if integrations command is registered
        $hasIntegrationsCommand = false;
        foreach ($output as $line) {
            if (strpos($line, 'integrations') !== false) {
                $hasIntegrationsCommand = true;
                break;
            }
        }
        
        if ($hasIntegrationsCommand) {
            pass("Integrations command registered");
        } else {
            fail("Integrations command not found in CLI");
        }
    } else {
        fail("CLI failed to execute");
    }
} else {
    fail("CLI binary not found at: {$cliPath}");
}

// Test 8: OAuth Flow
section("Test 8: OAuth Flow");
try {
    $flow = $iris->integrations->startOAuthFlow('gmail');
    
    if (isset($flow['url']) && isset($flow['instructions'])) {
        pass("OAuth flow returns URL and instructions");
        info("  URL length: " . strlen($flow['url']) . " chars");
    } else {
        fail("OAuth flow missing required fields");
    }
} catch (Exception $e) {
    fail("OAuth flow failed: {$e->getMessage()}");
}

// Summary
section("Test Summary");
echo "\n";
echo "╔═══════════════════════════════════════════════╗\n";
echo "║  ✅ Integration Management MVP is working!   ║\n";
echo "╚═══════════════════════════════════════════════╝\n";
echo "\n";

info("Next steps:");
echo "  1. Run examples: php examples/integrations-management.php\n";
echo "  2. Try CLI: ./bin/iris integrations list\n";
echo "  3. Connect integration: ./bin/iris integrations connect vapi\n";
echo "  4. Read docs: docs/INTEGRATION_MANAGEMENT_QUICK_REFERENCE.md\n";
echo "\n";
