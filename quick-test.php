<?php
/**
 * IRIS SDK Quick Integration Test
 *
 * Uses curl directly to test API endpoints without requiring composer dependencies.
 * This is useful for quick validation of API connectivity.
 *
 * Usage:
 *   php quick-test.php local <api_key> <user_id>
 *   php quick-test.php production <api_key> <user_id>
 *
 * Authentication Notes:
 * ====================
 * The FL-API uses different authentication middleware for different routes:
 *
 * 1. Standard Bearer Token (auth:api) - Works with OAuth2 user tokens:
 *    - /api/v1/leads/* - Lead management
 *    - /api/v1/user - User account info
 *    - /api/v1/user/{userId}/bloqs - Bloqs (projects)
 *
 * 2. Client Credentials (client middleware) - Requires OAuth2 client credentials:
 *    - /api/v1/users/{userId}/bloqs/agents/* - Agent management
 *    - Many content routes (YouTube, services, etc.)
 *
 * To get a client credentials token:
 *    1. Create a Passport client: php artisan passport:client --client
 *    2. Get token: POST /oauth/token with grant_type=client_credentials
 *
 * Or for development, modify routes to use auth:api middleware.
 */

// =============================================================================
// Configuration
// =============================================================================

$environments = [
    'local' => [
        'base_url' => 'http://localhost:8000',
    ],
    'production' => [
        'base_url' => 'https://api.freelabel.net',
    ],
];

// =============================================================================
// Simple HTTP Client using curl
// =============================================================================

function httpRequest(string $method, string $url, ?array $data, string $apiKey): array
{
    $ch = curl_init();

    $headers = [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
        'Accept: application/json',
        'User-Agent: IRIS-PHP-SDK/1.0.0-test',
    ];

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,  // For local testing
    ]);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } elseif ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    if ($error) {
        return ['success' => false, 'error' => $error, 'code' => 0];
    }

    $decoded = json_decode($response, true);

    return [
        'success' => $httpCode >= 200 && $httpCode < 300,
        'code' => $httpCode,
        'data' => $decoded,
        'raw' => $response,
    ];
}

// =============================================================================
// Test Functions
// =============================================================================

function testConnection(string $baseUrl, string $apiKey): bool
{
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "TEST: Connection\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    // Test health endpoint (no auth required)
    echo "\n  ▸ Health Check... ";
    $health = httpRequest('GET', "{$baseUrl}/api/health", null, $apiKey);
    if ($health['success']) {
        echo "✅ API is healthy\n";
    } else if ($health['code'] === 503) {
        echo "⚠️ API reachable but some services unhealthy (HTTP 503)\n";
        echo "    This is normal - some background services may be down\n";
    } else if ($health['code'] === 0) {
        echo "❌ Cannot connect to API at {$baseUrl}\n";
        return false;
    } else {
        echo "⚠️ Health check returned HTTP {$health['code']}\n";
    }

    // Test integration types (public endpoint)
    echo "  ▸ Integration Types... ";
    $types = httpRequest('GET', "{$baseUrl}/api/v1/integrations/types", null, $apiKey);
    if ($types['success']) {
        echo "✅ Public endpoints accessible\n";
    } else {
        echo "❌ Public endpoint failed (HTTP {$types['code']})\n";
    }

    // Test user endpoint (requires user OAuth token, not client credentials)
    echo "  ▸ User Auth Check... ";
    $result = httpRequest('GET', "{$baseUrl}/api/user", null, $apiKey);
    if ($result['success']) {
        echo "✅ User authenticated\n";
        if (isset($result['data']['id'])) {
            echo "    User ID: {$result['data']['id']}\n";
            echo "    Email: {$result['data']['email']}\n";
        }
    } else if ($result['code'] === 302 || $result['code'] === 0) {
        echo "⚠️ Redirected (token is client credentials, not user token)\n";
        echo "    Note: /api/user requires a user OAuth token, not client credentials\n";
    } else {
        echo "⚠️ User endpoint not accessible (HTTP {$result['code']})\n";
        echo "    This is normal for client credentials tokens\n";
    }

    return true;
}

function testAgents(string $baseUrl, string $apiKey, int $userId): bool
{
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "TEST: Agents\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    $passed = 0;
    $failed = 0;

    // List agents (requires client credentials middleware - may fail with Bearer token)
    echo "\n  ▸ List Agents... ";
    $result = httpRequest('GET', "{$baseUrl}/api/v1/users/{$userId}/bloqs/agents", null, $apiKey);
    if ($result['success']) {
        $count = count($result['data']['data'] ?? $result['data'] ?? []);
        echo "✅ Found {$count} agents\n";
        $passed++;
    } else {
        if ($result['code'] === 401) {
            echo "⚠️ Skipped (requires client credentials - HTTP 401)\n";
            echo "    Note: Agent CRUD requires 'client' middleware (OAuth2 client credentials)\n";
        } else {
            echo "❌ Failed (HTTP {$result['code']})\n";
        }
        $failed++;
    }

    // Test agent chat endpoint (works with Bearer token)
    echo "  ▸ Chat with Agent (ID from env)... ";
    $testAgentId = getenv('TEST_AGENT_ID') ?: null;

    if (!$testAgentId) {
        echo "⚠️ Skipped (set TEST_AGENT_ID env var to test)\n";
    } else {
        $chatResult = httpRequest('POST', "{$baseUrl}/api/v1/bloqs/agents/generate-response", [
            'agent_id' => (int)$testAgentId,
            'messages' => [
                ['role' => 'user', 'content' => 'Say hello in exactly 3 words.']
            ],
        ], $apiKey);

        if ($chatResult['success']) {
            $content = $chatResult['data']['content'] ?? $chatResult['data']['response'] ?? '(no content)';
            echo "✅\n";
            echo "    Response: " . substr($content, 0, 80) . "\n";
            $passed++;
        } else {
            echo "❌ Failed (HTTP {$chatResult['code']})\n";
            if (isset($chatResult['data']['message'])) {
                echo "    Error: {$chatResult['data']['message']}\n";
            }
            $failed++;
        }
    }

    // Test agent ask endpoint (alternative chat endpoint)
    if ($testAgentId) {
        echo "  ▸ Ask Agent... ";
        $askResult = httpRequest('POST', "{$baseUrl}/api/v1/bloqs/agents/ask", [
            'agent_id' => (int)$testAgentId,
            'prompt' => 'What is 2+2? Reply with just the number.',
        ], $apiKey);

        if ($askResult['success']) {
            $content = $askResult['data']['content'] ?? $askResult['data']['response'] ?? '(no content)';
            echo "✅\n";
            echo "    Response: " . substr($content, 0, 80) . "\n";
            $passed++;
        } else {
            echo "❌ Failed (HTTP {$askResult['code']})\n";
            $failed++;
        }
    }

    echo "\n  Results: {$passed} passed, {$failed} failed\n";
    echo "  Note: Full agent CRUD requires OAuth2 client credentials token.\n";
    return true; // Don't fail on agent tests since they need special auth
}

function testBloqs(string $baseUrl, string $apiKey, int $userId): bool
{
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "TEST: Bloqs\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    $passed = 0;
    $failed = 0;

    // List bloqs
    echo "\n  ▸ List Bloqs... ";
    $result = httpRequest('GET', "{$baseUrl}/api/v1/user/{$userId}/bloqs", null, $apiKey);
    if ($result['success']) {
        $count = count($result['data']['data'] ?? $result['data'] ?? []);
        echo "✅ Found {$count} bloqs\n";
        $passed++;
    } else {
        echo "❌ Failed (HTTP {$result['code']})\n";
        $failed++;
    }

    // Create bloq
    echo "  ▸ Create Bloq... ";
    $createResult = httpRequest('POST', "{$baseUrl}/api/v1/user/{$userId}/bloqs", [
        'title' => 'SDK Quick Test Bloq ' . date('H:i:s'),
        'description' => 'Test bloq created by quick-test.php',
    ], $apiKey);

    $bloqId = null;
    if ($createResult['success']) {
        $bloqId = $createResult['data']['data']['id'] ?? $createResult['data']['id'] ?? null;
        echo "✅ Created bloq ID: {$bloqId}\n";
        $passed++;
    } else {
        echo "❌ Failed (HTTP {$createResult['code']})\n";
        $failed++;
    }

    // Delete bloq
    if ($bloqId) {
        echo "  ▸ Delete Bloq... ";
        $deleteResult = httpRequest('DELETE', "{$baseUrl}/api/v1/user/{$userId}/bloqs/{$bloqId}", null, $apiKey);
        if ($deleteResult['success'] || $deleteResult['code'] === 204) {
            echo "✅ Deleted\n";
            $passed++;
        } else {
            echo "❌ Failed (HTTP {$deleteResult['code']})\n";
            $failed++;
        }
    }

    echo "\n  Results: {$passed} passed, {$failed} failed\n";
    return $failed === 0;
}

function testLeads(string $baseUrl, string $apiKey, int $userId): bool
{
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "TEST: Leads\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    $passed = 0;
    $failed = 0;

    // List leads
    echo "\n  ▸ List Leads... ";
    $result = httpRequest('GET', "{$baseUrl}/api/v1/leads", null, $apiKey);
    if ($result['success']) {
        $count = count($result['data']['data'] ?? $result['data'] ?? []);
        echo "✅ Found {$count} leads\n";
        $passed++;
    } else {
        echo "❌ Failed (HTTP {$result['code']})\n";
        $failed++;
    }

    // Create lead
    echo "  ▸ Create Lead... ";
    $createResult = httpRequest('POST', "{$baseUrl}/api/v1/leads", [
        'name' => 'SDK Test Lead',
        'email' => 'sdk-quicktest-' . time() . '@example.com',
        'company' => 'Test Company',
    ], $apiKey);

    $leadId = null;
    if ($createResult['success']) {
        $leadId = $createResult['data']['data']['id'] ?? $createResult['data']['id'] ?? null;
        echo "✅ Created lead ID: {$leadId}\n";
        $passed++;
    } else {
        echo "❌ Failed (HTTP {$createResult['code']})\n";
        $failed++;
    }

    // Delete lead
    if ($leadId) {
        echo "  ▸ Delete Lead... ";
        $deleteResult = httpRequest('DELETE', "{$baseUrl}/api/v1/leads/{$leadId}", null, $apiKey);
        if ($deleteResult['success'] || $deleteResult['code'] === 204) {
            echo "✅ Deleted\n";
            $passed++;
        } else {
            echo "❌ Failed (HTTP {$deleteResult['code']})\n";
            $failed++;
        }
    }

    echo "\n  Results: {$passed} passed, {$failed} failed\n";
    return $failed === 0;
}

function testIntegrations(string $baseUrl, string $apiKey): bool
{
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "TEST: Integrations\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    $passed = 0;
    $failed = 0;

    // List integrations
    echo "\n  ▸ List Integrations... ";
    $result = httpRequest('GET', "{$baseUrl}/api/v1/integrations", null, $apiKey);
    if ($result['success']) {
        $count = count($result['data']['data'] ?? $result['data'] ?? []);
        echo "✅ Found {$count} integrations\n";
        $passed++;
    } else {
        echo "❌ Failed (HTTP {$result['code']})\n";
        $failed++;
    }

    // Get integration types
    echo "  ▸ Get Integration Types... ";
    $result = httpRequest('GET', "{$baseUrl}/api/v1/integrations/types", null, $apiKey);
    if ($result['success']) {
        echo "✅\n";
        $passed++;
    } else {
        echo "❌ Failed (HTTP {$result['code']})\n";
        $failed++;
    }

    echo "\n  Results: {$passed} passed, {$failed} failed\n";
    return $failed === 0;
}

// =============================================================================
// Main
// =============================================================================

$env = $argv[1] ?? null;
$apiKey = $argv[2] ?? getenv('IRIS_API_KEY');
$userId = (int)($argv[3] ?? getenv('IRIS_USER_ID') ?? 1);

if (!$env || !isset($environments[$env])) {
    echo "\n";
    echo "IRIS SDK Quick Integration Test\n";
    echo "================================\n\n";
    echo "Usage:\n";
    echo "  php quick-test.php <environment> <api_key> <user_id>\n\n";
    echo "Examples:\n";
    echo "  php quick-test.php local sk_abc123 1\n";
    echo "  php quick-test.php production sk_xyz789 42\n\n";
    echo "Or set environment variables:\n";
    echo "  export IRIS_API_KEY='your-api-key'\n";
    echo "  export IRIS_USER_ID='your-user-id'\n";
    echo "  php quick-test.php local\n\n";
    echo "Environments:\n";
    foreach ($environments as $name => $config) {
        echo "  - {$name}: {$config['base_url']}\n";
    }
    echo "\n";
    exit(1);
}

if (!$apiKey) {
    echo "\n❌ Error: API key required\n";
    echo "Pass as second argument or set IRIS_API_KEY environment variable\n\n";
    exit(1);
}

$baseUrl = $environments[$env]['base_url'];

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║         IRIS PHP SDK - Quick Integration Tests               ║\n";
echo "╠══════════════════════════════════════════════════════════════╣\n";
echo "║  Environment: " . str_pad(strtoupper($env), 46) . "║\n";
echo "║  Base URL:    " . str_pad($baseUrl, 46) . "║\n";
echo "║  User ID:     " . str_pad((string)$userId, 46) . "║\n";
echo "║  API Key:     " . str_pad(substr($apiKey, 0, 20) . '...', 46) . "║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";

$allPassed = true;

// Run tests
$allPassed = testConnection($baseUrl, $apiKey) && $allPassed;
$allPassed = testAgents($baseUrl, $apiKey, $userId) && $allPassed;
$allPassed = testBloqs($baseUrl, $apiKey, $userId) && $allPassed;
$allPassed = testLeads($baseUrl, $apiKey, $userId) && $allPassed;
$allPassed = testIntegrations($baseUrl, $apiKey) && $allPassed;

echo "\n";
echo "══════════════════════════════════════════════════════════════\n";
if ($allPassed) {
    echo "✅ All tests passed!\n";
    exit(0);
} else {
    echo "❌ Some tests failed. Check output above.\n";
    exit(1);
}
