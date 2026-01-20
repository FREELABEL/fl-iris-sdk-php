#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use Iris\SDK\IRIS;

// Load .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Configure for LOCAL environment
$config = [
    'api_key' => $_ENV['LOCAL_API_TOKEN'] ?? 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiI5NzlhYmI4YS1hNWEzLTQ4MTMtYmFhYi0xNWVmODMxNWMyNTQiLCJqdGkiOiIyNTEzYzhkMzVhOTM5NzMyMjEwYmI0ZDEyOTMwYzA1ZDQyNGM1YmYwMzkzZWRlYzQ4ZWFlMmVmODE4ZjQzZWI4MGJjYzAzZTg0MDQ1ODA5ZiIsImlhdCI6MTc2ODk0NjMxMS42MTY5MTIsIm5iZiI6MTc2ODk0NjMxMS42MTY5MTMsImV4cCI6MTgwMDQ4MjMxMS42MTE0OTcsInN1YiI6IjE5MyIsInNjb3BlcyI6W119.VawVfAJalSWS84B_ROOa5FXcg6EClKGYmRnsw_Ar7X4X9O1KQFYw5vgzz0qpEOmiBmWetRsSWj0MwMMHXWRSnaT-Ax5OIUvRWlXcSJqmGc_08z1HhgBCLhdVB7Wex5i4fCkcBlWKx4NCs-btFx8RwuCN_bGcD-dZ5k2G094rfsSuI2uZrq7CLfl84klGYugqBFXxdGSybZoKCwXZe6OUk_1Gflu9AGskO3d7ccybO09i028bc8uB32zRsQKNkHT0oNjkHtc8_rHASHHoJ0n7gkhU0q4b9krMA3pb4qnBWqgwDUTm8r5a4zD7snIhxP81-B2i6PvXJ21-6kUzhmouhUvSn2ukbx8hycX2RK1h6UxuNiW5NjXxWTbOpt67sDST-teKB4vzdUBY9DrmSrSQKkb8oMP66mDBvY-AVh-Qp8HvuxjinmbXMzFEKB5j_7oGCmtDuziAW_-t_vk7f1KQUPuiU15lWrjRM6aFFs26B9E-Dn2bOxDkiPYZ1bbWuVcgZMMgFZBkpSM8p_x24LTA9UbFTZabX_dHTf31mjgM5T0Q6mR46BoLir3R0nguWvpBDU70VBYv0xBLjHkE1VijY5RJ4OgKdCmxv4g2JbGCAkiyTLJQ4GdxrMDAKbqNg2inO3cQVdY2r7iMZ7Eg0x78otSY3v5SFvyQb9acA2tYdFk',
    'user_id' => 193,
    'base_url' => 'http://host.docker.internal:8001',
    'fl_api_url' => 'http://host.docker.internal:8000',
    'environment' => 'development',
    'debug' => true,
];

$iris = new IRIS($config);

echo "=== Testing Local API with Fix ===\n\n";

// Test 1: Search for our test lead via aggregation endpoint
echo "1. Searching for 'API Test Person' via leads.search():\n";
try {
    $results = $iris->leads->search(['search' => 'API Test Person']);
    
    if (count($results) > 0) {
        $lead = $results[0];
        echo "   ✅ Found lead:\n";
        echo "      ID: {$lead['id']}\n";
        echo "      Name: {$lead['name']}\n";
        echo "      Email: {$lead['email']}\n";
        echo "      User ID: " . ($lead['user_id'] ?? 'NULL') . "\n";
        
        if (isset($lead['user_id']) && $lead['user_id'] === 193) {
            echo "   🎉 SUCCESS: user_id is correctly returned!\n";
        } else {
            echo "   ❌ FAIL: user_id is missing or incorrect\n";
        }
    } else {
        echo "   No leads found\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: List all leads
echo "2. Listing all leads (first 5):\n";
try {
    $results = $iris->leads->list(['per_page' => 5]);
    echo "   Found " . count($results) . " leads\n";
    
    foreach ($results as $lead) {
        $hasUserId = isset($lead['user_id']) && $lead['user_id'] !== null;
        $status = $hasUserId ? '✅' : '❌';
        echo "   $status Lead #{$lead['id']}: {$lead['name']} (user_id: " . ($lead['user_id'] ?? 'NULL') . ")\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
