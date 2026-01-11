#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Initialize SDK with local configuration
$iris = new IRIS([
    'api_key' => $_ENV['IRIS_LOCAL_API_KEY'] ?? $_ENV['IRIS_API_KEY'],
    'user_id' => (int)($_ENV['IRIS_USER_ID'] ?? 193),
    'base_url' => 'https://local.raichu.freelabel.net',
    'debug' => true,
]);

echo "Testing Voice Provider Management System\n";
echo "========================================\n\n";

// Test 1: Get available providers
echo "1. Testing voice providers endpoint...\n";
try {
    $providers = $iris->voice->getProviders();
    echo "✓ Success!\n";
    print_r($providers);
} catch (Exception $e) {
    echo "✗ Failed: " . $e->getMessage() . "\n";
}

echo "\n2. Testing phone providers endpoint...\n";
try {
    $providers = $iris->phone->getProviders();
    echo "✓ Success!\n";
    print_r($providers);
} catch (Exception $e) {
    echo "✗ Failed: " . $e->getMessage() . "\n";
}

echo "\n3. Testing voice configuration for agent 335...\n";
try {
    $config = $iris->voice->get(335);
    echo "✓ Success!\n";
    print_r($config);
} catch (Exception $e) {
    echo "✗ Failed: " . $e->getMessage() . "\n";
}

echo "\nTests complete!\n";
