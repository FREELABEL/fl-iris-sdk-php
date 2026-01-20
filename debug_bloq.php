<?php

require __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$sdk = new IRIS([
    'api_key' => $_ENV['IRIS_API_KEY'],
    'user_id' => (int)$_ENV['IRIS_USER_ID'],
    'base_url' => $_ENV['IRIS_ENV'] === 'production' ? 'https://iris-api.freelabel.net' : 'http://fl-iris-api-nginx',
]);

// Use curl to directly call the production API
$apiKey = $_ENV['IRIS_API_KEY'];
$userId = $_ENV['IRIS_USER_ID'];
$apiUrl = "https://iris-api.freelabel.net/api/v1/user/{$userId}/bloqs";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $apiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "X-User-ID: {$userId}",
        "Accept: application/json",
    ],
]);

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

// Find bloq 203
$bloq203 = null;
if (isset($data['data'])) {
    foreach ($data['data'] as $bloq) {
        if ($bloq['id'] == 203) {
            $bloq203 = $bloq;
            break;
        }
    }
}

if ($bloq203) {
    echo "Bloq 203 from API:\n";
    echo json_encode($bloq203, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
} else {
    echo "Bloq 203 not found. Showing first 3 bloqs:\n";
    echo json_encode(array_slice($data['data'] ?? [], 0, 3), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    
    echo "\n\nTotal bloqs returned: " . count($data['data'] ?? []) . "\n";
    
    // Check if bloq 203 exists by calling the specific endpoint
    echo "\n\nTrying direct endpoint /api/v1/bloqs/203:\n";
    $ch2 = curl_init();
    curl_setopt_array($ch2, [
        CURLOPT_URL => 'https://iris-api.freelabel.net/api/v1/bloqs/203',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$apiKey}",
            "X-User-ID: {$userId}",
            "Accept: application/json",
        ],
    ]);
    $response2 = curl_exec($ch2);
    curl_close($ch2);
    echo $response2 . "\n";
}
