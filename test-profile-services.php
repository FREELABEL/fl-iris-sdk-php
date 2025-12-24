<?php

// Simple test to debug profile_id filtering in services API

// Use local API key directly
$apiKey = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiJhMGE4NTA0My1lYWMyLTQ1ZDItOWFlYS0xYWU4ZTkxZWUyZWQiLCJqdGkiOiIyMmMxN2MwMTliMWI0ZTJiMzI4N2Q1M2I0Mjg1ZjY4OTZmMGRmNjM3NTAzODZhYTQ5MmM1YWI4NjNhZWU1MWFkYjgwNGZkYWZhMDdmMTljNCIsImlhdCI6MTc2NjUzODk5OS4yNjE5NSwibmJmIjoxNzY2NTM4OTk5LjI2MTk1MSwiZXhwIjoxNzk4MDc0OTk5LjIyNjI5OSwic3ViIjoiIiwic2NvcGVzIjpbXX0.WblZ7hgkdZlz96VMzUEuzSUlA71eiqAF0rAoHUvoklAjsnyWoJwwV0xqLFr8erZ-o9jIknmjO1GJS1ZWpQKkk82FCUCUPHYB5eVhug6KkoR1an99NBk7AKGlwdmpjlnqhDEHOhB57jfgu5vb_2aNDGApkft62G2JHZxB9ifjywtmKHrGdnszzX_UMRY_FQMZkNjLTkLpu2K_ZZMYkGawowMUZkHV5-35eHxhjzn5QNrDOHWuvqrDTlLxFFpMzIRYoWAHXBYoTtFfrFszWrl34Ts34tLKWcYioKCBWcJR-673wxqwyX0yF8tioeIpK3_yw_4ybM7vXtW9B9VXKAqpi8TnZyP5Fr0tXk_wF0-3TkONJ5oILAsCsGMSirTPxWeTLz-Iujh2us8IcbmEPjNJr12PLG0F8hjGPGoEb7dj_e_StN_4PabeJq48IOD6TayOP10zNy13ffyFHb2erk3Kl_2-O_MLjg6h5ILzbw6XZ-vfo78piAjD47wOR8Ck3e0SJFT6-cUzrcqobvnE-wktJ4M6_pyBqh3fSjbrjfA5groQeqf8j7A7ObuopjjAVCDaPG-NMPSjZ7DghsOjouTveEBJ12Pg2ES1o1QvDHBIEonzlNdhpyLyA7MfZzEw85DbV6ItiVKX-cSBWO2ZAh8zNzs-KwMjzVdBYj_XTqOB2ho';
$baseUrl = 'https://local.raichu.freelabel.net';

echo "=== Testing Profile Services Filtering ===\n\n";

// Test with profile_id parameter
echo "Test: GET /api/v1/services?profile_id=2811028\n";
echo "Expected: Only 2 services (IDs 203, 204)\n";
echo "---\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/v1/services?profile_id=2811028&limit=100');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: {$httpCode}\n\n";

// Debug: Show raw response
echo "Raw JSON Response (first 500 chars):\n";
echo substr($response, 0, 500) . "\n\n";

if ($httpCode !== 200) {
    echo "❌ API Error\n";
    echo "Response: " . substr($response, 0, 500) . "\n";
    exit(1);
}

$data = json_decode($response, true);
$services = $data['data']['data'] ?? [];

echo "Total services returned: " . count($services) . "\n\n";

if (count($services) === 0) {
    echo "❌ No services found\n";
    exit(1);
}

// Show all services
echo "Services found:\n";
foreach ($services as $service) {
    $id = $service['id'] ?? 'null';
    $name = $service['name'] ?? 'unnamed';
    $profileId = $service['profile_id'] ?? 'null';
    echo "  - ID: {$id}, Name: {$name}, Profile ID: {$profileId}\n";
}

// Check filtering
echo "\n";
$correctServices = array_filter($services, function($s) {
    return isset($s['profile_id']) && $s['profile_id'] == 2811028;
});

$wrongServices = array_filter($services, function($s) {
    return !isset($s['profile_id']) || $s['profile_id'] != 2811028;
});

echo "Services with profile_id=2811028: " . count($correctServices) . "\n";
echo "Services with other profile_id: " . count($wrongServices) . "\n\n";

if (count($wrongServices) > 0) {
    echo "❌ FILTERING FAILED\n";
    echo "API is returning services from multiple profiles:\n";
    $uniqueProfiles = array_unique(array_map(function($s) {
        return $s['profile_id'] ?? 'null';
    }, $services));
    echo "Profile IDs found: " . implode(', ', $uniqueProfiles) . "\n";
} else if (count($correctServices) === 2) {
    echo "✅ FILTERING SUCCESS\n";
    echo "API correctly returned only the 2 services for profile 2811028\n";
} else {
    echo "⚠️  PARTIAL SUCCESS\n";
    echo "All services belong to profile 2811028, but expected 2, got " . count($correctServices) . "\n";
}

echo "\n=== Test Complete ===\n";
