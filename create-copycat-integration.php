#!/usr/bin/env php
<?php

// This script creates a CopycatAI integration record for user 1

// Change to fl-iris-api directory
chdir(__DIR__ . '/../../fl-iris-api');

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check if integration already exists
$integration = App\Models\Integration::where('user_id', 1)
    ->where('type', 'copycat-ai')
    ->first();

if (!$integration) {
    // Create new integration
    $integration = App\Models\Integration::create([
        'user_id' => 1,
        'name' => 'CopycatAI',
        'type' => 'copycat-ai',
        'credentials' => json_encode(['api_key' => 'test_key']),
        'is_active' => true
    ]);
    
    echo "✓ Created CopycatAI integration\n";
    echo "  ID: {$integration->id}\n";
    echo "  User: {$integration->user_id}\n";
    echo "  Type: {$integration->type}\n";
    echo "  Active: " . ($integration->is_active ? 'Yes' : 'No') . "\n";
} else {
    echo "✓ CopycatAI integration already exists\n";
    echo "  ID: {$integration->id}\n";
    echo "  User: {$integration->user_id}\n";
    echo "  Type: {$integration->type}\n";
    echo "  Active: " . ($integration->is_active ? 'Yes' : 'No') . "\n";
}
