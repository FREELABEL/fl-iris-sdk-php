<?php

require __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "=== CloudFile Upload Test ===\n\n";

try {
    // Initialize SDK
    $iris = new IRIS([
        'api_key' => $_ENV['IRIS_API_KEY'],
        'base_url' => $_ENV['IRIS_BASE_URL']
    ]);

    echo "API Key: " . substr($_ENV['IRIS_API_KEY'], 0, 10) . "...\n";
    echo "Base URL: " . $_ENV['IRIS_BASE_URL'] . "\n\n";

    // Check if test file exists
    $filePath = __DIR__ . '/test-demo.mp3';
    if (!file_exists($filePath)) {
        throw new Exception("Test file not found: $filePath");
    }

    echo "Test file: $filePath\n";
    echo "File size: " . filesize($filePath) . " bytes\n\n";

    // Upload test MP3
    echo "Uploading file...\n";
    $response = $iris->cloudFiles->upload($filePath, [
        'title' => 'Test Demo Upload - ' . date('Y-m-d H:i:s'),
        'user_id' => 193,  // Test user
        'bypass_save' => true  // Don't save to database, just upload to storage
    ]);

    echo "\n✅ Upload successful!\n\n";
    echo "Response:\n";
    echo json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

    if (isset($response['filepath'])) {
        echo "Public URL: " . $response['filepath'] . "\n";
    }

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
