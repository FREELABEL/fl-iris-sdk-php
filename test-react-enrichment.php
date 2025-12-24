#!/usr/bin/env php
<?php
/**
 * Test ReAct AI Enrichment on Coffee Shop Leads
 * Tests leads: #512, #511, #510, #460
 */

require_once __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;
use Dotenv\Dotenv;

// Load environment
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
}

$baseUrl = $_ENV['IRIS_URL'] ?? getenv('IRIS_URL') ?? 'http://localhost:8000';
$apiKey = $_ENV['IRIS_API_KEY'] ?? getenv('IRIS_API_KEY');

echo "\n";
echo "=== ReAct AI Enrichment Test ===\n";
echo "Base URL: {$baseUrl}\n\n";

// Coffee shop leads to test (production IDs - may differ locally)
$leads = [
    1 => '@danielramireztx (local test lead)',
    512 => 'Coffeehouse at Caroline',
    511 => '5th & Brew',
    510 => "Jo's Coffee – Symphony Square",
    460 => 'Brew and Brew'
];

// Test one lead at a time - use local lead ID 1 for testing
$testLeadId = 1; // Local test lead

echo "Testing ReAct enrichment on lead #{$testLeadId} ({$leads[$testLeadId]})...\n\n";

// Make the API call using curl
$url = "{$baseUrl}/api/v1/leads/{$testLeadId}/enrich-react";

$payload = json_encode([
    'goal' => 'email', // Primary goal: find email
    'max_iterations' => 3,
    'use_native_http' => true // Use free scraping first
]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 120); // 2 minute timeout for enrichment

echo "Calling: POST {$url}\n";
echo "Payload: {$payload}\n\n";

$startTime = microtime(true);
$response = curl_exec($ch);
$endTime = microtime(true);

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

$duration = round($endTime - $startTime, 2);
echo "Duration: {$duration}s\n";
echo "HTTP Status: {$httpCode}\n\n";

if ($error) {
    echo "CURL Error: {$error}\n";
    exit(1);
}

$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "JSON Parse Error: " . json_last_error_msg() . "\n";
    echo "Raw Response:\n{$response}\n";
    exit(1);
}

// Pretty print the response
echo "Response:\n";
echo str_repeat('-', 60) . "\n";

if ($data['success'] ?? false) {
    echo "SUCCESS!\n\n";

    $resultData = $data['data'] ?? [];

    echo "Goal: " . ($resultData['goal'] ?? 'N/A') . "\n";
    echo "Goal Achieved: " . ($resultData['goal_achieved'] ? 'YES' : 'NO') . "\n";
    echo "Iterations: " . ($resultData['iterations'] ?? 0) . "\n";
    echo "Native HTTP Used: " . ($resultData['native_http_used'] ? 'Yes' : 'No') . "\n\n";

    if (!empty($resultData['found_contacts'])) {
        echo "Found Contacts:\n";
        $contacts = $resultData['found_contacts'];
        if (!empty($contacts['emails'])) {
            echo "  Emails:\n";
            foreach ($contacts['emails'] as $email) {
                echo "    - {$email}\n";
            }
        }
        if (!empty($contacts['phones'])) {
            echo "  Phones:\n";
            foreach ($contacts['phones'] as $phone) {
                echo "    - {$phone}\n";
            }
        }
        if (!empty($contacts['company'])) {
            echo "  Company: {$contacts['company']}\n";
        }
        if (!empty($contacts['website'])) {
            echo "  Website: {$contacts['website']}\n";
        }
        if (!empty($contacts['linkedin_url'])) {
            echo "  LinkedIn: {$contacts['linkedin_url']}\n";
        }
    } else {
        echo "No contacts found yet.\n";
    }

    if (!empty($resultData['reasoning'])) {
        echo "\nAI Reasoning:\n";
        foreach ($resultData['reasoning'] as $i => $reason) {
            echo "  " . ($i + 1) . ". {$reason}\n";
        }
    }

    if (!empty($resultData['sources'])) {
        echo "\nSources Used:\n";
        foreach ($resultData['sources'] as $source) {
            echo "  - {$source}\n";
        }
    }
} else {
    echo "FAILED!\n";
    echo "Error: " . ($data['message'] ?? $data['error'] ?? 'Unknown error') . "\n";

    if (!empty($data['reasoning'])) {
        echo "\nAI Reasoning:\n";
        foreach ($data['reasoning'] as $i => $reason) {
            echo "  " . ($i + 1) . ". {$reason}\n";
        }
    }
}

echo str_repeat('-', 60) . "\n\n";

// Show full JSON for debugging
echo "Full JSON Response:\n";
echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
