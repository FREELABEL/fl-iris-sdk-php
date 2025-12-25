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

echo "🎵 Testing YouTube Audio Download via SDK CLI\n";
echo "============================================\n\n";

$youtubeUrl = $argv[1] ?? 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
$uploadToGcs = isset($argv[2]) && $argv[2] === 'true';

echo "YouTube URL: {$youtubeUrl}\n";
echo "Upload to GCS: " . ($uploadToGcs ? 'YES' : 'NO (local storage)') . "\n\n";

try {
    $result = $iris->integrations->execute('copycat-ai', 'download_youtube_audio', [
        'youtube_url' => $youtubeUrl,
        'upload_to_gcs' => $uploadToGcs,
    ]);

    if ($result['success']) {
        echo "✅ SUCCESS!\n\n";
        echo "Title: " . ($result['data']['title'] ?? 'N/A') . "\n";
        echo "Download URL: " . ($result['data']['download_url'] ?? 'N/A') . "\n";
        echo "File Size: " . ($result['data']['file_size_mb'] ?? 'N/A') . " MB\n";
        echo "Format: " . ($result['data']['format'] ?? 'N/A') . "\n";
        echo "Quality: " . ($result['data']['quality'] ?? 'N/A') . "\n";
        echo "Storage: " . ($result['data']['storage_provider'] ?? 'N/A') . "\n";
        echo "Cloud File ID: " . ($result['data']['cloud_file_id'] ?? 'N/A') . "\n";
        echo "BLOQ Item ID: " . ($result['data']['bloq_item_id'] ?? 'N/A') . "\n";
        echo "BLOQ Item URL: " . ($result['data']['bloq_item_url'] ?? 'N/A') . "\n\n";
        echo "Message: " . ($result['message'] ?? 'N/A') . "\n";
    } else {
        echo "❌ FAILED\n\n";
        echo "Error: " . ($result['error'] ?? 'Unknown error') . "\n";
    }

    echo "\n" . str_repeat('=', 50) . "\n";
    echo "Full Response:\n";
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
