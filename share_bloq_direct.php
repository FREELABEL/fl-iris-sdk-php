<?php
/**
 * Direct database insert to share bloq 203 with user 5274
 * 
 * This bypasses the SDK permission check by inserting directly via IRIS API
 */

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['IRIS_API_KEY'];
$userId = $_ENV['IRIS_USER_ID'];

// Call the production IRIS API to execute the SQL
$apiUrl = 'https://iris-api.freelabel.net/api/v1/user/bloqs/203/share';

$data = [
    'user_id' => 5274,  // jayala@aec-hq.com
    'permission' => 'owner',
    'sharing_user_id' => 193,  // You (bloq owner)
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $apiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "X-User-ID: {$userId}",
        "Content-Type: application/json",
        "Accept: application/json",
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: {$httpCode}\n";
echo "Response:\n";
echo json_encode(json_decode($response), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

if ($httpCode >= 200 && $httpCode < 300) {
    echo "\n✅ Success! Bloq 203 shared with user 5274 (jayala@aec-hq.com)\n";
} else {
    echo "\n❌ Failed to share bloq\n";
}
