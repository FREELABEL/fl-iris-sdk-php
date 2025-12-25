#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;

// Load credentials from .env
$envPath = __DIR__ . '/.env';
if (!file_exists($envPath)) {
    echo "❌ Error: .env file not found at {$envPath}\n";
    exit(1);
}

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['IRIS_API_KEY'] ?? null;
$baseUrl = $_ENV['IRIS_BASE_URL'] ?? 'https://iris-api.freelabel.net';

if (!$apiKey) {
    echo "❌ Error: IRIS_API_KEY not found in .env\n";
    exit(1);
}

// Initialize SDK
$iris = new IRIS([
    'api_key' => $apiKey,
    'base_url' => $baseUrl,
]);

echo "🎵 Testing YouTube Audio Download via Agent callIntegration\n";
echo "==========================================================\n\n";

$youtubeUrl = $argv[1] ?? 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
$uploadToGcs = isset($argv[2]) && $argv[2] === 'true';
$agentId = $argv[3] ?? 1; // Default to agent ID 1

echo "YouTube URL: {$youtubeUrl}\n";
echo "Upload to GCS: " . ($uploadToGcs ? 'YES' : 'NO (local storage)') . "\n";
echo "Agent ID: {$agentId}\n\n";

try {
    // Call via Agent's callIntegration endpoint (Pattern 1: Manual Execution)
    $result = $iris->agents->callIntegration($agentId, 'copycat-ai', 'download_youtube_audio', [
        'youtube_url' => $youtubeUrl,
        'upload_to_gcs' => $uploadToGcs,
    ]);

    if ($result['success'] ?? false) {
        echo "✅ SUCCESS!\n\n";
        $data = $result['data'] ?? [];
        echo "Title: " . ($data['title'] ?? 'N/A') . "\n";
        echo "Download URL: " . ($data['download_url'] ?? 'N/A') . "\n";
        echo "File Size: " . ($data['file_size_mb'] ?? 'N/A') . " MB\n";
        echo "Format: " . ($data['format'] ?? 'N/A') . "\n";
        echo "Quality: " . ($data['quality'] ?? 'N/A') . "\n";
        echo "Storage: " . ($data['storage_provider'] ?? 'N/A') . "\n";
        echo "Cloud File ID: " . ($data['cloud_file_id'] ?? 'N/A') . "\n";
        echo "BLOQ Item ID: " . ($data['bloq_item_id'] ?? 'N/A') . "\n";
        echo "BLOQ Item URL: " . ($data['bloq_item_url'] ?? 'N/A') . "\n\n";
        echo "Message: " . ($result['message'] ?? 'N/A') . "\n";
    } else {
        echo "❌ FAILED\n\n";
        echo "Error: " . ($result['error'] ?? 'Unknown error') . "\n";
    }

    echo "\n" . str_repeat('=', 60) . "\n";
    echo "Full Response:\n";
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
