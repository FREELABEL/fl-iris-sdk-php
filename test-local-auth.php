#!/usr/bin/env php
<?php
/**
 * Quick test: Local API authentication
 */

// Load .env manually
$envFile = __DIR__ . '/.env';
$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$env = [];
foreach ($lines as $line) {
    if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
        list($key, $value) = explode('=', $line, 2);
        $env[trim($key)] = trim($value);
    }
}

echo "=== Environment Check ===\n";
echo "IRIS_ENV: " . ($env['IRIS_ENV'] ?? 'NOT SET') . "\n";
echo "FL_API_LOCAL_URL: " . ($env['FL_API_LOCAL_URL'] ?? 'NOT SET') . "\n";
echo "Token prefix: " . substr($env['IRIS_LOCAL_API_KEY'] ?? '', 0, 30) . "...\n\n";

// List agents for user
$url = ($env['FL_API_LOCAL_URL'] ?? 'http://localhost:8000') . '/api/v1/users/193/bloqs/agents';
$token = $env['IRIS_LOCAL_API_KEY'] ?? '';

echo "=== Listing Local Agents ===\n";
echo "URL: $url\n\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json',
    'Accept: application/json',
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($httpCode >= 200 && $httpCode < 300) {
    echo "✅ Request successful!\n";
    $data = json_decode($response, true);
    $agents = $data['data'] ?? $data ?? [];
    echo "Found " . count($agents) . " agents:\n";
    foreach (array_slice($agents, 0, 5) as $agent) {
        echo "  - ID: " . ($agent['id'] ?? '?') . " | Name: " . ($agent['name'] ?? 'Unknown') . "\n";
    }
} else {
    echo "❌ Request failed!\n";
    echo "Response: " . substr($response, 0, 300) . "\n";
}
