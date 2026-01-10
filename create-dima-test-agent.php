<?php
/**
 * Create Test Agent with Custom Functions for Dima
 * 
 * This demonstrates:
 * 1. Creating an agent with custom functions
 * 2. Phone number management use case
 * 3. Multi-tenant context support
 */

require __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;
use IRIS\SDK\Resources\Agents\AgentConfig;
use Dotenv\Dotenv;

// Load .env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$iris = new IRIS([
    'api_key' => $_ENV['IRIS_API_KEY'],
    'user_id' => (int) $_ENV['IRIS_USER_ID'],
]);

echo "==============================================\n";
echo "CREATING TEST AGENT FOR DIMA\n";
echo "Phone Number Optimizer with Custom Functions\n";
echo "==============================================\n\n";

try {
    // Define custom functions for phone number management
    $customFunctions = [
        [
            'name' => 'fetchPhoneNumbers',
            'description' => 'Fetch all phone numbers for a client. Returns array of phone numbers with their call history, days since last call, and current status.',
            'endpoint' => 'https://dima-phone-api.example.com/api/numbers',
            'method' => 'GET',
            'auth' => [
                'type' => 'bearer',
                'token' => '{{PHONE_API_KEY}}',
            ],
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'client_id' => [
                        'type' => 'string',
                        'description' => 'Client ID (tenant identifier)',
                        'required' => true,
                    ],
                    'filter' => [
                        'type' => 'string',
                        'description' => 'Optional filter: "unused", "low-usage", "active"',
                        'required' => false,
                    ],
                ],
            ],
        ],
        [
            'name' => 'releasePhoneNumber',
            'description' => 'Release a phone number to avoid ongoing charges. Use this when a number has 0 calls for 72+ hours. Always provide a reason for audit trail.',
            'endpoint' => 'https://dima-phone-api.example.com/api/numbers/{{number_id}}/release',
            'method' => 'POST',
            'auth' => [
                'type' => 'bearer',
                'token' => '{{PHONE_API_KEY}}',
            ],
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'number_id' => [
                        'type' => 'string',
                        'description' => 'Phone number ID to release',
                        'required' => true,
                    ],
                    'client_id' => [
                        'type' => 'string',
                        'description' => 'Client ID (for multi-tenant verification)',
                        'required' => true,
                    ],
                    'reason' => [
                        'type' => 'string',
                        'description' => 'Reason for release (for audit log)',
                        'required' => false,
                    ],
                ],
            ],
        ],
        [
            'name' => 'getCallHistory',
            'description' => 'Get detailed call history for a specific phone number. Returns call count, last call date, and call details.',
            'endpoint' => 'https://dima-phone-api.example.com/api/numbers/{{number_id}}/calls',
            'method' => 'GET',
            'auth' => [
                'type' => 'bearer',
                'token' => '{{PHONE_API_KEY}}',
            ],
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'number_id' => [
                        'type' => 'string',
                        'description' => 'Phone number ID',
                        'required' => true,
                    ],
                    'days' => [
                        'type' => 'integer',
                        'description' => 'Number of days to look back (default: 30)',
                        'required' => false,
                    ],
                ],
            ],
        ],
        [
            'name' => 'tagPhoneNumber',
            'description' => 'Add a tag to a phone number for categorization (e.g., "low-usage", "auto-release", "keep").',
            'endpoint' => 'https://dima-phone-api.example.com/api/numbers/{{number_id}}/tag',
            'method' => 'POST',
            'auth' => [
                'type' => 'bearer',
                'token' => '{{PHONE_API_KEY}}',
            ],
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'number_id' => [
                        'type' => 'string',
                        'description' => 'Phone number ID',
                        'required' => true,
                    ],
                    'tag' => [
                        'type' => 'string',
                        'description' => 'Tag to add (e.g., "low-usage", "auto-release")',
                        'required' => true,
                    ],
                ],
            ],
        ],
        [
            'name' => 'sendAlert',
            'description' => 'Send alert notification to admin about actions taken (e.g., numbers released, issues found).',
            'endpoint' => 'https://dima-phone-api.example.com/api/alerts',
            'method' => 'POST',
            'auth' => [
                'type' => 'bearer',
                'token' => '{{PHONE_API_KEY}}',
            ],
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'message' => [
                        'type' => 'string',
                        'description' => 'Alert message content',
                        'required' => true,
                    ],
                    'severity' => [
                        'type' => 'string',
                        'description' => 'Alert severity level',
                        'enum' => ['info', 'warning', 'critical'],
                        'required' => false,
                    ],
                ],
            ],
        ],
    ];

    // Agent prompt
    $prompt = <<<PROMPT
You are an intelligent Phone Number Lifecycle Manager.

**Your Task:**
1. Fetch all phone numbers for the client using fetchPhoneNumbers
2. For each number, check call history using getCallHistory
3. Apply these rules:
   - If a number has ZERO calls in the last 72 hours → release it using releasePhoneNumber
   - If a number has only 1 call in a specific day → tag it as "low-usage" using tagPhoneNumber
   - Keep numbers with regular usage (2+ calls in 72 hours)
4. Send a summary alert using sendAlert with details of actions taken

**Rules:**
- Always check call history before releasing
- Always provide a reason when releasing: "Auto-released: No usage for 72+ hours"
- Log each action clearly
- Send summary alert at the end with counts and cost savings

**Multi-Tenant Context:**
- client_id is provided in the context - always use it for API calls
- current_date is provided in the context - use for date calculations

**Response Format:**
Provide a clear summary including:
- Total numbers checked
- Numbers released (with IDs and phone numbers)
- Numbers tagged as low-usage
- Estimated cost savings
- Any issues encountered

Execute this workflow autonomously and thoroughly.
PROMPT;

    echo "Creating agent with custom functions...\n\n";

    // Create agent (using settings directly as ToolRegistry expects)
    $agentData = [
        'name' => 'Phone Number Optimizer (Dima POC)',
        'prompt' => $prompt,
        'model' => 'gpt-4o-mini',
        'settings' => [
            'webAccess' => false,
            'include_leads' => false,
            'agentIntegrations' => [],
            'enabledFunctions' => [],
            'customFunctions' => $customFunctions,
            'customFunctionSecrets' => [
                'PHONE_API_KEY' => 'test_phone_api_key_replace_with_real',
            ],
        ],
    ];

    $agent = $iris->agents->createFromArray($agentData);

    echo "✅ Success! Agent Created:\n\n";
    echo "ID: " . $agent->id . "\n";
    echo "Name: " . $agent->name . "\n";
    echo "Model: " . $agent->model . "\n";
    echo "Custom Functions: " . count($customFunctions) . " functions registered\n\n";

    // List custom functions
    echo "Custom Functions Registered:\n";
    foreach ($customFunctions as $func) {
        echo "  - {$func['name']}: {$func['description']}\n";
    }
    echo "\n";

    // Get shareable URL
    if (method_exists($agent, 'getSimpleUrl')) {
        $url = $agent->getSimpleUrl();
        echo "🔗 Shareable URL: " . $url . "\n\n";
    }

    // Save agent details
    $agentFile = __DIR__ . '/DIMA_TEST_AGENT.txt';
    $agentContent = "# Dima POC - Test Agent with Custom Functions\n";
    $agentContent .= "# Created: " . date('Y-m-d H:i:s') . "\n\n";
    $agentContent .= "AGENT_ID={$agent->id}\n";
    $agentContent .= "AGENT_NAME={$agent->name}\n";
    $agentContent .= "MODEL={$agent->model}\n\n";
    $agentContent .= "# Custom Functions (5 total):\n";
    foreach ($customFunctions as $func) {
        $agentContent .= "# - {$func['name']}\n";
    }
    $agentContent .= "\n# Test Execution:\n";
    $agentContent .= "curl -X POST \"https://heyiris.io/api/chat/execute\" \\\n";
    $agentContent .= "  -H \"Authorization: Bearer \$IRIS_API_KEY\" \\\n";
    $agentContent .= "  -H \"X-User-ID: \$IRIS_USER_ID\" \\\n";
    $agentContent .= "  -H \"Content-Type: application/json\" \\\n";
    $agentContent .= "  -d '{\n";
    $agentContent .= "    \"agentId\": {$agent->id},\n";
    $agentContent .= "    \"query\": \"Check for unused phone numbers and auto-release them\",\n";
    $agentContent .= "    \"context\": {\n";
    $agentContent .= "      \"client_id\": \"client_123\",\n";
    $agentContent .= "      \"current_date\": \"2026-01-09T12:00:00Z\"\n";
    $agentContent .= "    }\n";
    $agentContent .= "  }'\n";

    file_put_contents($agentFile, $agentContent);
    echo "✅ Agent details saved to: {$agentFile}\n\n";

    echo "==============================================\n";
    echo "✅ TEST AGENT CREATED SUCCESSFULLY!\n";
    echo "==============================================\n\n";
    echo "Next steps:\n";
    echo "1. Update PHONE_API_KEY in agent settings with real API key\n";
    echo "2. Update endpoint URLs to Dima's actual API\n";
    echo "3. Test agent execution with sample context\n";
    echo "4. Attach to Dima's lead record\n\n";

    echo "Example test execution:\n";
    echo "php test-dima-agent.php {$agent->id}\n\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    if (method_exists($e, 'getTrace')) {
        echo "\nStack trace:\n";
        echo $e->getTraceAsString() . "\n";
    }
    exit(1);
}
