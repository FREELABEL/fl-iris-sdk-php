<?php
/**
 * Generate API Keys for Dima Semyansky
 * Creates user account and API token for IRIS platform access
 */

require __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;
use Dotenv\Dotenv;

// Load admin credentials
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$iris = new IRIS([
    'api_key' => $_ENV['IRIS_API_KEY'],
    'user_id' => (int) $_ENV['IRIS_USER_ID'],
]);

echo "==============================================\n";
echo "GENERATING API KEYS FOR DIMA SEMYANSKY\n";
echo "==============================================\n\n";

try {
    // User details for Dima
    $userData = [
        'name' => 'Dima Semyansky',
        'email' => 'dima@example.com', // TODO: Get Dima's actual email
        'company' => 'Phone Management & Fire Department Solutions',
        'role' => 'Enterprise Partner',
    ];

    echo "Creating user account...\n";
    echo "Name: {$userData['name']}\n";
    echo "Email: {$userData['email']}\n\n";

    // Note: This assumes IRIS SDK has a users resource
    // If not, we'll need to use direct API calls or Laravel artisan command
    
    // Try SDK first
    if (method_exists($iris, 'users')) {
        echo "Using SDK users resource...\n";
        $user = $iris->users->create($userData);
        $userId = $user->id;
        echo "✅ User created! ID: {$userId}\n\n";
    } else {
        echo "⚠️  SDK doesn't have users resource. Using direct API call...\n\n";
        
        // Direct API call to create user
        $response = $iris->request('POST', '/api/users', $userData);
        
        if (isset($response['data']['id'])) {
            $userId = $response['data']['id'];
            echo "✅ User created via API! ID: {$userId}\n\n";
        } else {
            throw new Exception("Failed to create user. Response: " . json_encode($response));
        }
    }

    // Generate API token
    echo "Generating API token...\n";
    
    if (method_exists($iris, 'tokens')) {
        $token = $iris->tokens->create([
            'user_id' => $userId,
            'name' => 'Dima Production API Key',
            'expires_at' => null, // Never expires
        ]);
        $apiKey = $token->token;
    } else {
        echo "⚠️  SDK doesn't have tokens resource. Using direct API call...\n";
        
        $response = $iris->request('POST', '/api/tokens', [
            'user_id' => $userId,
            'name' => 'Dima Production API Key',
            'expires_at' => null,
        ]);
        
        if (isset($response['data']['token'])) {
            $apiKey = $response['data']['token'];
        } else {
            throw new Exception("Failed to generate API token. Response: " . json_encode($response));
        }
    }

    echo "✅ API token generated!\n\n";

    // Display credentials
    echo "==============================================\n";
    echo "DIMA'S CREDENTIALS\n";
    echo "==============================================\n\n";
    echo "API_KEY={$apiKey}\n";
    echo "USER_ID={$userId}\n";
    echo "DASHBOARD_URL=https://heyiris.io\n\n";

    // Save to file
    $credentialsFile = __DIR__ . '/DIMA_CREDENTIALS.txt';
    $credentialsContent = "# Dima Semyansky - IRIS Platform Credentials\n";
    $credentialsContent .= "# Generated: " . date('Y-m-d H:i:s') . "\n\n";
    $credentialsContent .= "IRIS_API_KEY={$apiKey}\n";
    $credentialsContent .= "IRIS_USER_ID={$userId}\n";
    $credentialsContent .= "IRIS_API_URL=https://heyiris.io\n";
    $credentialsContent .= "DASHBOARD_URL=https://heyiris.io\n\n";
    $credentialsContent .= "# React .env format:\n";
    $credentialsContent .= "REACT_APP_IRIS_API_KEY={$apiKey}\n";
    $credentialsContent .= "REACT_APP_IRIS_USER_ID={$userId}\n";
    $credentialsContent .= "REACT_APP_IRIS_API_URL=https://heyiris.io\n";

    file_put_contents($credentialsFile, $credentialsContent);
    echo "✅ Credentials saved to: {$credentialsFile}\n\n";

    // Test API connection
    echo "Testing API connection...\n";
    $testIris = new IRIS([
        'api_key' => $apiKey,
        'user_id' => $userId,
    ]);
    
    $agents = $testIris->agents->list();
    echo "✅ API connection successful! Found " . count($agents) . " agents.\n\n";

    echo "==============================================\n";
    echo "✅ SETUP COMPLETE!\n";
    echo "==============================================\n\n";
    echo "Next steps:\n";
    echo "1. Send credentials to Dima (DIMA_CREDENTIALS.txt)\n";
    echo "2. Create test agent with custom functions\n";
    echo "3. Attach notes to lead record\n\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    if (method_exists($e, 'getResponse')) {
        $response = $e->getResponse();
        if ($response) {
            echo "Response: " . $response->getBody()->getContents() . "\n";
        }
    }
    echo "\n⚠️  Manual fallback needed:\n";
    echo "1. Create user via Laravel: php artisan user:create\n";
    echo "2. Generate API token via dashboard: https://heyiris.io/developer\n";
    exit(1);
}
