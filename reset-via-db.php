<?php
// Direct database update to reset stuck jobs
$apiKey = trim(file_get_contents(__DIR__ . '/.env'));
preg_match('/IRIS_API_KEY=(.+)/', $apiKey, $matches);
$apiKey = $matches[1] ?? '';

preg_match('/IRIS_USER_ID=(\d+)/', file_get_contents(__DIR__ . '/.env'), $matches);
$userId = $matches[1] ?? '';

$baseUrl = 'https://apiv2.heyiris.io';

echo "Connecting to: $baseUrl\n";
echo "User ID: $userId\n\n";

// Get all stuck jobs
$ch = curl_init("$baseUrl/api/v1/users/$userId/bloqs/scheduled-jobs?agent_jobs_only=1");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Accept: application/json"
]);
$response = curl_exec($ch);
curl_close($ch);

$jobs = json_decode($response, true)['data'] ?? json_decode($response, true) ?? [];

echo "Found " . count($jobs) . " total jobs\n";

$stuckJobs = array_filter($jobs, function($job) {
    return ($job['status'] ?? '') === 'running' 
        && ($job['next_run_at'] ?? '') < date('c');
});

echo "Found " . count($stuckJobs) . " stuck jobs\n\n";

if (empty($stuckJobs)) {
    echo "✅ No stuck jobs! System is healthy.\n";
    exit(0);
}

// Try the reset endpoint for each job
foreach ($stuckJobs as $job) {
    echo "Resetting job #{$job['id']}: {$job['task_name']}\n";
    
    $ch = curl_init("$baseUrl/api/v1/users/$userId/bloqs/scheduled-jobs/{$job['id']}/reset");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([]));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        if ($result['success'] ?? false) {
            echo "  ✅ Reset successfully\n";
            echo "  → New next_run_at: " . ($result['data']['next_run_at'] ?? 'unknown') . "\n";
        } else {
            echo "  ❌ Failed: " . ($result['message'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "  ⚠️  HTTP $httpCode - Endpoint may not be deployed yet\n";
        echo "  Response: " . substr($response, 0, 200) . "\n";
    }
}

echo "\nDone!\n";
