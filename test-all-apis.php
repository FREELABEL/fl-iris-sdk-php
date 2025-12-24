<?php
// Test both APIs are configured correctly

echo "=== API Configuration Test ===\n\n";

// Test 1: FL-API (leads, profiles, services)
echo "1. Testing FL-API (https://apiv2.heyiris.io)\n";
$flApiTest = curl_init();
curl_setopt($flApiTest, CURLOPT_URL, 'https://apiv2.heyiris.io/api/health');
curl_setopt($flApiTest, CURLOPT_RETURNTRANSFER, true);
curl_setopt($flApiTest, CURLOPT_SSL_VERIFYPEER, false);
$flResponse = curl_exec($flApiTest);
$flStatus = curl_getinfo($flApiTest, CURLINFO_HTTP_CODE);
curl_close($flApiTest);

if ($flStatus === 200) {
    echo "   ✅ FL-API is UP\n";
    echo "   Response: $flResponse\n";
} else {
    echo "   ❌ FL-API returned: $flStatus\n";
}

echo "\n";

// Test 2: IRIS API (agents, workflows, chat)
echo "2. Testing IRIS API (https://iris-api.freelabel.net)\n";
$irisApiTest = curl_init();
curl_setopt($irisApiTest, CURLOPT_URL, 'https://iris-api.freelabel.net/api/health');
curl_setopt($irisApiTest, CURLOPT_RETURNTRANSFER, true);
curl_setopt($irisApiTest, CURLOPT_SSL_VERIFYPEER, false);
$irisResponse = curl_exec($irisApiTest);
$irisStatus = curl_getinfo($irisApiTest, CURLINFO_HTTP_CODE);
curl_close($irisApiTest);

if ($irisStatus === 200) {
    echo "   ✅ IRIS API is UP\n";
    echo "   Response: $irisResponse\n";
} else {
    echo "   ❌ IRIS API returned: $irisStatus\n";
}

echo "\n=== Summary ===\n";
echo "FL-API (leads/profiles/services): " . ($flStatus === 200 ? "✅ WORKING" : "❌ DOWN") . "\n";
echo "IRIS API (agents/workflows/chat): " . ($irisStatus === 200 ? "✅ WORKING" : "❌ DOWN") . "\n";
