# Custom Functions in ToolRegistry - Implementation Guide

## Problem Statement

Agents already have `custom_functions` configured in their `settings` JSON, but these custom functions are **NOT being injected into the ToolRegistry** dynamically. This means:

1. ❌ Custom functions don't appear in the agent's available tools
2. ❌ LLM doesn't know about custom functions when planning
3. ❌ Custom functions can't be called during workflow execution

## Current State

### Where Custom Functions Are Stored

**Agent Model** (`bloq_agents` table):
```php
// fl-api/app/Models/Bloq/Agent.php
protected $fillable = [
    // ...
    'settings',  // JSON column
];

protected $casts = [
    'settings' => 'array',
];
```

**Settings Structure:**
```json
{
  "webAccess": true,
  "agentIntegrations": {
    "gmail": true,
    "google-drive": false
  },
  "enabledFunctions": {
    "deepResearch": true,
    "staffManagement": false
  },
  "customFunctions": [
    {
      "name": "fetchPhoneNumbers",
      "description": "Fetch all phone numbers for a client",
      "endpoint": "https://api.example.com/numbers",
      "method": "GET",
      "auth": {
        "type": "bearer",
        "token": "{{API_KEY}}"
      },
      "parameters": {
        "type": "object",
        "properties": {
          "client_id": {
            "type": "string",
            "description": "Client ID",
            "required": true
          }
        }
      }
    }
  ]
}
```

### Current ToolRegistry Behavior

**ToolRegistry.php** only returns **hardcoded** built-in tools:
```php
protected function getAllTools(): array
{
    return [
        'CallIntegrationTool' => [...],
        'WebSearchTool' => [...],
        'DeepResearchTool' => [...],
        // ... 40+ hardcoded tools
    ];
}
```

**Missing:** Dynamic injection of `agent->settings['customFunctions']` into the tool list.

---

## Solution: Dynamic Custom Function Injection

### Step 1: Extend `ToolRegistry::getAllTools()`

We need to merge custom functions from agent settings into the tool registry.

**File:** `fl-iris-api/app/Services/ToolRegistry.php`

```php
/**
 * Get all tools registered in the system.
 * NOW INCLUDES agent-specific custom functions dynamically.
 */
protected function getAllTools(?BloqAgent $agent = null): array
{
    $builtInTools = $this->getBuiltInTools();
    
    // If agent has custom functions, inject them dynamically
    if ($agent && isset($agent->settings['customFunctions'])) {
        $customTools = $this->buildCustomFunctionTools($agent->settings['customFunctions']);
        return array_merge($builtInTools, $customTools);
    }
    
    return $builtInTools;
}

/**
 * Get hardcoded built-in tools (existing logic moved here)
 */
protected function getBuiltInTools(): array
{
    return [
        // Integration Tools
        'CallIntegrationTool' => [
            'name' => 'CallIntegrationTool',
            'description' => 'Execute actions using connected integrations.',
            'category' => 'integrations',
            'requires' => 'integrations',
        ],
        
        // ... all existing tools
    ];
}

/**
 * Convert agent's custom functions into ToolRegistry format
 * 
 * @param array $customFunctions - Array from agent->settings['customFunctions']
 * @return array - Tools in ToolRegistry format
 */
protected function buildCustomFunctionTools(array $customFunctions): array
{
    $tools = [];
    
    foreach ($customFunctions as $func) {
        // Validate required fields
        if (!isset($func['name']) || !isset($func['description'])) {
            \Log::warning('ToolRegistry: Skipping invalid custom function', [
                'function' => $func,
            ]);
            continue;
        }
        
        // Generate unique tool name (prefix with "Custom_" to avoid collisions)
        $toolName = 'Custom_' . $func['name'];
        
        $tools[$toolName] = [
            'name' => $toolName,
            'description' => $func['description'],
            'category' => 'custom', // New category for custom functions
            'requires' => null, // Always available if defined
            'custom_function' => true, // Flag to identify custom functions
            'endpoint' => $func['endpoint'] ?? null,
            'method' => $func['method'] ?? 'POST',
            'auth' => $func['auth'] ?? null,
            'parameters' => $func['parameters'] ?? [],
        ];
    }
    
    return $tools;
}
```

### Step 2: Update `getAvailableTools()` to Accept Agent

The method signature already accepts `?BloqAgent $agent`, but we need to ensure it's passed through:

```php
public function getAvailableTools(?BloqAgent $agent = null, ?Authenticatable $user = null): array
{
    // This now calls getAllTools($agent), which includes custom functions
    $allTools = $this->getAllTools($agent);
    $availableTools = [];
    $categories = [];

    foreach ($allTools as $toolName => $toolData) {
        if ($this->isToolAvailable($toolData, $agent, $user)) {
            $availableTools[$toolName] = $toolData;
            if (!in_array($toolData['category'], $categories)) {
                $categories[] = $toolData['category'];
            }
        }
    }

    return [
        'tools' => $availableTools,
        'categories' => $categories,
    ];
}
```

### Step 3: Update `buildToolDescription()` for System Prompt

Ensure custom functions appear in the system prompt:

```php
public function buildToolDescription(?BloqAgent $agent = null, ?Authenticatable $user = null): string
{
    $available = $this->getAvailableTools($agent, $user);
    $disabled = $this->getDisabledCategories($agent, $user);

    $description = "🔧 AVAILABLE TOOLS:\n\n";

    // List available tools by category
    if (!empty($available['tools'])) {
        $toolsByCategory = [];
        foreach ($available['tools'] as $toolData) {
            $category = $toolData['category'];
            if (!isset($toolsByCategory[$category])) {
                $toolsByCategory[$category] = [];
            }
            // Skip legacy mappings in the list
            if (!isset($toolData['maps_to'])) {
                $toolsByCategory[$category][] = $toolData;
            }
        }

        foreach ($toolsByCategory as $category => $tools) {
            $categoryName = ucfirst($category);
            
            // Special formatting for custom functions
            if ($category === 'custom') {
                $description .= "**🎨 Custom Functions (Your API)**:\n";
            } else {
                $description .= "**{$categoryName} Tools**:\n";
            }
            
            foreach ($tools as $toolData) {
                $description .= "  - {$toolData['name']}: {$toolData['description']}\n";
            }
            $description .= "\n";
        }
    }

    // ... rest of existing logic
    
    return $description;
}
```

### Step 4: Tool Execution Handler

When the LLM calls a custom function, we need to execute it. This likely happens in the workflow executor.

**File:** `fl-iris-api/app/Services/Workflows/ToolExecutor.php` (or similar)

```php
public function executeTool(string $toolName, array $arguments, BloqAgent $agent): mixed
{
    // Check if this is a custom function
    if (str_starts_with($toolName, 'Custom_')) {
        return $this->executeCustomFunction($toolName, $arguments, $agent);
    }
    
    // Otherwise execute built-in tool
    return $this->executeBuiltInTool($toolName, $arguments, $agent);
}

protected function executeCustomFunction(string $toolName, array $arguments, BloqAgent $agent): mixed
{
    // Find the custom function definition
    $customFunctions = $agent->settings['customFunctions'] ?? [];
    $funcName = str_replace('Custom_', '', $toolName);
    
    $funcDef = collect($customFunctions)->firstWhere('name', $funcName);
    
    if (!$funcDef) {
        throw new \Exception("Custom function not found: {$funcName}");
    }
    
    // Build HTTP request
    $endpoint = $funcDef['endpoint'];
    $method = $funcDef['method'] ?? 'POST';
    $auth = $funcDef['auth'] ?? null;
    
    // Replace template variables in endpoint
    $endpoint = $this->replaceTemplateVars($endpoint, $arguments, $agent);
    
    // Build request
    $httpClient = Http::timeout(30);
    
    // Add authentication
    if ($auth) {
        if ($auth['type'] === 'bearer') {
            $token = $this->resolveAuthToken($auth['token'], $agent);
            $httpClient = $httpClient->withToken($token);
        } elseif ($auth['type'] === 'header') {
            $httpClient = $httpClient->withHeaders([
                $auth['key'] => $this->resolveAuthToken($auth['value'], $agent),
            ]);
        }
    }
    
    // Execute request
    try {
        if ($method === 'GET') {
            $response = $httpClient->get($endpoint, $arguments);
        } elseif ($method === 'POST') {
            $response = $httpClient->post($endpoint, $arguments);
        } elseif ($method === 'PUT') {
            $response = $httpClient->put($endpoint, $arguments);
        } elseif ($method === 'DELETE') {
            $response = $httpClient->delete($endpoint, $arguments);
        } else {
            throw new \Exception("Unsupported HTTP method: {$method}");
        }
        
        if (!$response->successful()) {
            throw new \Exception("Custom function failed: HTTP {$response->status()}");
        }
        
        return $response->json();
        
    } catch (\Exception $e) {
        \Log::error('Custom function execution failed', [
            'function' => $funcName,
            'agent_id' => $agent->id,
            'error' => $e->getMessage(),
        ]);
        
        throw $e;
    }
}

/**
 * Replace template variables like {{API_KEY}} or {{client_id}}
 */
protected function replaceTemplateVars(string $text, array $arguments, BloqAgent $agent): string
{
    // Replace {{variable}} with values from arguments or agent context
    $text = preg_replace_callback('/\{\{([^}]+)\}\}/', function($matches) use ($arguments, $agent) {
        $varName = trim($matches[1]);
        
        // Check arguments first
        if (isset($arguments[$varName])) {
            return $arguments[$varName];
        }
        
        // Check agent settings (for secrets like API_KEY)
        if (isset($agent->settings['customFunctionSecrets'][$varName])) {
            return $agent->settings['customFunctionSecrets'][$varName];
        }
        
        // Check environment variables
        $envValue = env($varName);
        if ($envValue) {
            return $envValue;
        }
        
        \Log::warning("Template variable not found: {$varName}");
        return $matches[0]; // Return original if not found
    }, $text);
    
    return $text;
}

/**
 * Resolve authentication token from various sources
 */
protected function resolveAuthToken(string $token, BloqAgent $agent): string
{
    // If token is a template variable like {{API_KEY}}
    if (preg_match('/\{\{([^}]+)\}\}/', $token, $matches)) {
        $varName = $matches[1];
        
        // Check agent settings
        if (isset($agent->settings['customFunctionSecrets'][$varName])) {
            return $agent->settings['customFunctionSecrets'][$varName];
        }
        
        // Check environment
        $envValue = env($varName);
        if ($envValue) {
            return $envValue;
        }
        
        throw new \Exception("Authentication token variable not found: {$varName}");
    }
    
    // Otherwise return as-is
    return $token;
}
```

---

## Example Usage

### 1. Define Custom Functions in Agent Settings

**Via API/SDK:**
```php
// Update agent with custom functions
$agent = Agent::find(500);

$agent->settings = array_merge($agent->settings, [
    'customFunctions' => [
        [
            'name' => 'fetchPhoneNumbers',
            'description' => 'Fetch all phone numbers for a client. Returns array of phone numbers with call history.',
            'endpoint' => 'https://phone-api.dima.com/api/numbers',
            'method' => 'GET',
            'auth' => [
                'type' => 'bearer',
                'token' => '{{DIMA_API_KEY}}', // Resolved from env or agent secrets
            ],
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'client_id' => [
                        'type' => 'string',
                        'description' => 'Client ID (tenant)',
                        'required' => true,
                    ],
                    'filter' => [
                        'type' => 'string',
                        'description' => 'Optional filter (unused, low-usage)',
                        'required' => false,
                    ],
                ],
            ],
        ],
        [
            'name' => 'releasePhoneNumber',
            'description' => 'Release a phone number to avoid charges. Use when number has no usage for 72+ hours.',
            'endpoint' => 'https://phone-api.dima.com/api/numbers/{{number_id}}/release',
            'method' => 'POST',
            'auth' => [
                'type' => 'bearer',
                'token' => '{{DIMA_API_KEY}}',
            ],
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'number_id' => [
                        'type' => 'string',
                        'description' => 'Phone number ID',
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
    ],
    'customFunctionSecrets' => [
        'DIMA_API_KEY' => 'sk_dima_live_xxxxxxxxxxxxx',
    ],
]);

$agent->save();
```

### 2. Agent Automatically Gets Custom Functions

When the agent runs, ToolRegistry will inject custom functions:

```php
// In workflow executor
$toolRegistry = new ToolRegistry();
$availableTools = $toolRegistry->getAvailableTools($agent);

// Returns:
[
    'tools' => [
        'WebSearchTool' => [...],
        'ManageBloqItemsTool' => [...],
        'Custom_fetchPhoneNumbers' => [
            'name' => 'Custom_fetchPhoneNumbers',
            'description' => 'Fetch all phone numbers for a client.',
            'category' => 'custom',
            'custom_function' => true,
            'endpoint' => 'https://phone-api.dima.com/api/numbers',
            'method' => 'GET',
            // ...
        ],
        'Custom_releasePhoneNumber' => [
            'name' => 'Custom_releasePhoneNumber',
            'description' => 'Release a phone number to avoid charges.',
            'category' => 'custom',
            'custom_function' => true,
            'endpoint' => 'https://phone-api.dima.com/api/numbers/{{number_id}}/release',
            'method' => 'POST',
            // ...
        ],
    ],
]
```

### 3. LLM Gets Custom Functions in System Prompt

```
🔧 AVAILABLE TOOLS:

**Web Tools**:
  - WebSearchTool: Search the web for information...

**Bloq Tools**:
  - ManageBloqItemsTool: Manage BLOQ items...

**🎨 Custom Functions (Your API)**:
  - Custom_fetchPhoneNumbers: Fetch all phone numbers for a client. Returns array of phone numbers with call history.
  - Custom_releasePhoneNumber: Release a phone number to avoid charges. Use when number has no usage for 72+ hours.
```

### 4. LLM Calls Custom Function

**LLM Output:**
```json
{
  "tool": "Custom_fetchPhoneNumbers",
  "arguments": {
    "client_id": "client_123",
    "filter": "unused"
  }
}
```

**Workflow Executor:**
```php
$result = $toolExecutor->executeTool('Custom_fetchPhoneNumbers', [
    'client_id' => 'client_123',
    'filter' => 'unused',
], $agent);

// Makes HTTP request:
// GET https://phone-api.dima.com/api/numbers?client_id=client_123&filter=unused
// Authorization: Bearer sk_dima_live_xxxxxxxxxxxxx

// Returns to LLM:
[
    ['id' => 1001, 'number' => '+1-555-0100', 'calls' => 0, 'days_since_last_call' => 85],
    ['id' => 1002, 'number' => '+1-555-0101', 'calls' => 1, 'days_since_last_call' => 12],
]
```

---

## Implementation Checklist

- [ ] **Step 1:** Add `buildCustomFunctionTools()` method to ToolRegistry
- [ ] **Step 2:** Modify `getAllTools()` to merge custom functions
- [ ] **Step 3:** Update `buildToolDescription()` to format custom functions
- [ ] **Step 4:** Create `ToolExecutor::executeCustomFunction()` method
- [ ] **Step 5:** Add `replaceTemplateVars()` helper for {{variable}} substitution
- [ ] **Step 6:** Add `resolveAuthToken()` for secure API key handling
- [ ] **Step 7:** Update agent creation UI to add custom functions
- [ ] **Step 8:** Add validation for custom function schemas
- [ ] **Step 9:** Add error handling and logging
- [ ] **Step 10:** Test with Dima's phone number management POC

---

## Security Considerations

### 1. Store API Keys Securely

**DO NOT** store API keys directly in `customFunctions` array:

```json
// ❌ BAD
{
  "customFunctions": [{
    "auth": {
      "token": "sk_actual_secret_key_here"  // EXPOSED IN DB!
    }
  }]
}

// ✅ GOOD
{
  "customFunctions": [{
    "auth": {
      "token": "{{DIMA_API_KEY}}"  // Template variable
    }
  }],
  "customFunctionSecrets": {
    "DIMA_API_KEY": "sk_actual_secret_key_here"  // Still in DB, but separate field
  }
}
```

**BEST:** Use Laravel's encryption for secrets:

```php
// Store encrypted
$agent->settings = [
    'customFunctionSecrets' => encrypt([
        'DIMA_API_KEY' => 'sk_actual_secret_key_here',
    ]),
];

// Decrypt when resolving
$secrets = decrypt($agent->settings['customFunctionSecrets']);
```

### 2. Validate Custom Function URLs

Prevent SSRF attacks by validating endpoints:

```php
protected function validateCustomFunctionEndpoint(string $endpoint): void
{
    // Disallow internal IPs
    $host = parse_url($endpoint, PHP_URL_HOST);
    
    $internalRanges = [
        '127.0.0.0/8',    // Loopback
        '10.0.0.0/8',     // Private
        '172.16.0.0/12',  // Private
        '192.168.0.0/16', // Private
    ];
    
    foreach ($internalRanges as $range) {
        if ($this->ipInRange($host, $range)) {
            throw new \Exception("Custom function endpoint cannot target internal network: {$endpoint}");
        }
    }
}
```

### 3. Rate Limiting

Add rate limiting to prevent abuse:

```php
use Illuminate\Support\Facades\RateLimiter;

protected function executeCustomFunction(string $toolName, array $arguments, BloqAgent $agent): mixed
{
    // Rate limit: 100 calls per agent per hour
    $key = "custom-function:{$agent->id}";
    
    if (RateLimiter::tooManyAttempts($key, 100)) {
        throw new \Exception("Custom function rate limit exceeded");
    }
    
    RateLimiter::hit($key, 3600); // 1 hour
    
    // ... execute function
}
```

---

## Testing

### Unit Test Example

```php
// tests/Unit/ToolRegistryTest.php

use Tests\TestCase;
use App\Services\ToolRegistry;
use App\Models\FlApi\BloqAgent;

class ToolRegistryTest extends TestCase
{
    /** @test */
    public function it_injects_custom_functions_into_tool_registry()
    {
        $agent = BloqAgent::factory()->create([
            'settings' => [
                'customFunctions' => [
                    [
                        'name' => 'testFunction',
                        'description' => 'Test function',
                        'endpoint' => 'https://api.test.com/test',
                        'method' => 'GET',
                    ],
                ],
            ],
        ]);

        $toolRegistry = new ToolRegistry();
        $tools = $toolRegistry->getAvailableTools($agent);

        $this->assertArrayHasKey('Custom_testFunction', $tools['tools']);
        $this->assertEquals('Test function', $tools['tools']['Custom_testFunction']['description']);
        $this->assertEquals('custom', $tools['tools']['Custom_testFunction']['category']);
    }

    /** @test */
    public function it_replaces_template_variables_in_endpoint()
    {
        // Test replaceTemplateVars() helper
    }

    /** @test */
    public function it_resolves_auth_tokens_from_secrets()
    {
        // Test resolveAuthToken() helper
    }
}
```

---

## Next Steps

1. **Implement ToolRegistry changes** (Steps 1-3)
2. **Implement ToolExecutor** (Steps 4-6)
3. **Add UI for managing custom functions** (Step 7)
4. **Test with Dima's POC** (Step 10)
5. **Document in SDK** (update TECHNICAL.md with custom functions guide)

**Estimated Time:** 8-12 hours for complete implementation

---

## Questions?

- How should we handle pagination for custom API responses?
- Should we support webhooks for long-running custom functions?
- Do we need a custom function marketplace/library for common integrations?
