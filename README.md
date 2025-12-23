# IRIS PHP SDK

Official PHP SDK for the **IRIS AI Platform** - Build intelligent agents, execute multi-step workflows, and manage documents with RAG-enhanced knowledge bases.

## Installation

```bash
composer require iris-ai/sdk
```

### Requirements
- PHP 8.1+
- Guzzle 7.0+

## CLI Tool

The SDK includes a lightweight CLI for quick access to all SDK features from the command line.

### Setup

```bash
# Set your credentials
export IRIS_API_KEY=sk_live_xxxxx
export IRIS_USER_ID=123

# Make the CLI executable (if needed)
chmod +x vendor/iris-ai/sdk/bin/iris

# Or use it directly
./vendor/bin/iris list
```

### Usage

The CLI uses a dynamic proxy pattern to access any SDK resource and method:

```bash
# Pattern: iris sdk:call <resource>.<method> [params] [options]

# Lead aggregation statistics
./vendor/bin/iris sdk:call leads.aggregation.statistics --json

# Get 10 most recently updated leads
./vendor/bin/iris sdk:call leads.aggregation.getRecentLeads 10

# List high-priority leads with incomplete tasks
./vendor/bin/iris sdk:call leads.aggregation.list has_incomplete_tasks=1 sort=priority

# Get leads by status (Won/Negotiation/Proposal)
./vendor/bin/iris sdk:call leads.aggregation.list status=Won sort=updated_at per_page=10

# Get specific lead details
./vendor/bin/iris sdk:call leads.get 123

# Chat with an agent
./vendor/bin/iris sdk:call agents.chat agent_id=5 message="Hello"

# Execute workflow
./vendor/bin/iris sdk:call workflows.execute '{"agent_id":5,"query":"Research"}'

# List bloqs
./vendor/bin/iris sdk:call bloqs.list

# RAG semantic search
./vendor/bin/iris sdk:call rag.query question="vacation policy" topK=5
```

### Output Formats

```bash
# JSON output (for scripting/automation)
iris sdk:call leads.list --json

# Raw output (no formatting)
iris sdk:call leads.get 123 --raw

# Smart tables (default) - automatically formats data
iris sdk:call leads.aggregation.statistics
```

### Parameter Types

The CLI auto-detects parameter types:
- `true`/`false` → boolean
- `123` → integer  
- `12.5` → float
- `null` → null
- `{"key":"val"}` → JSON object
- `[1,2,3]` → JSON array
- `anything else` → string

### For Autonomous Agents

Perfect for programmatic access in autonomous development pipelines:

```bash
#!/bin/bash
# Platform AI Agent - Find high-priority work
LEADS=$(iris sdk:call leads.aggregation.list has_incomplete_tasks=1 --json)

# SDK AI Agent - Get requirements
REQS=$(iris sdk:call leads.aggregation.requirements 123 --json)

# QA Engineer Agent - Monitor stats
STATS=$(iris sdk:call leads.aggregation.statistics --json)

# Process results
echo $LEADS | jq '.[] | select(.priority_score > 50)'
```

### Extensibility

The CLI is a pure proxy - any new SDK resources or methods are automatically available without code changes.

## Quick Start

```php
<?php
use IRIS\SDK\IRIS;
use IRIS\SDK\Resources\Agents\AgentConfig;

// Initialize the SDK
$iris = new IRIS([
    'api_key' => 'sk_live_xxxxx',
    'user_id' => 123,  // Your user ID
]);

// Chat with an agent
$response = $iris->agents->chat('agent_123', [
    ['role' => 'user', 'content' => 'Draft a marketing email for our Q1 launch']
]);

echo $response->content;
```

## Features

### 🤖 AI Agents

Create, configure, and interact with intelligent AI agents.

```php
// Create an agent
$agent = $iris->agents->create(new AgentConfig(
    name: 'Marketing Assistant',
    prompt: 'You are a helpful marketing assistant specializing in email campaigns.',
    model: 'gpt-4o-mini',
    integrations: ['gmail', 'google-drive'],
));

// Chat with the agent
$response = $iris->agents->chat($agent->id, [
    ['role' => 'user', 'content' => 'Write a subject line for our product launch']
]);

// Add knowledge to agent's memory
$iris->agents->addMemory($agent->id, '/path/to/brand-guide.pdf');
```

### 🔄 V5 Multi-Step Workflows

Execute complex workflows with real-time progress tracking and human-in-the-loop support.

```php
// Execute a workflow
$workflow = $iris->workflows->execute([
    'agent_id' => 'research_agent',
    'query' => 'Research competitors in the CRM space and create a comparison report',
]);

// Track progress in real-time
foreach ($workflow->steps() as $step) {
    echo "[{$step->progress}%] {$step->description}\n";

    if ($step->isCompleted()) {
        echo "  Result: " . $step->getResultString() . "\n";
    }
}

// Handle human-in-the-loop approval
if ($workflow->needsHumanInput()) {
    $task = $workflow->pendingTask;
    echo "Approval needed: {$task->description}\n";

    // Approve and continue
    $workflow->approve('Looks good, proceed with the report.');
}

// Get final result
$result = $workflow->result();
echo $result->content;

// Access generated files
foreach ($result->getFileUrls() as $url) {
    echo "File: {$url}\n";
}
```

### 📚 Document Management (Bloqs)

Organize content with projects, lists, and items.

```php
// Create a knowledge base
$kb = $iris->bloqs->create('Company Knowledge Base', [
    'description' => 'Internal documentation and policies',
]);

// Upload documents
$iris->bloqs->uploadFile($kb->id, '/path/to/handbook.pdf', [
    'title' => 'Employee Handbook',
    'tags' => ['hr', 'policy'],
]);

// Create organized lists
$list = $iris->bloqs->lists($kb->id)->create([
    'title' => 'Q1 Marketing Materials',
    'type' => 'folder',
]);

// Add items to lists
$iris->bloqs->items($list->id)->create([
    'title' => 'Campaign Brief',
    'content' => 'Campaign details...',
]);
```

### 🔍 RAG (Retrieval-Augmented Generation)

Query your knowledge base with semantic search.

```php
// Index content
$iris->rag->index(
    content: 'Our vacation policy allows for 20 days of PTO...',
    metadata: [
        'bloq_id' => $kb->id,
        'type' => 'policy',
        'title' => 'Vacation Policy',
    ]
);

// Query with semantic search
$results = $iris->rag->query(
    question: 'What is our vacation policy?',
    filters: ['bloq_id' => $kb->id],
    topK: 3
);

foreach ($results->documents as $doc) {
    echo "Score: {$doc->score}\n";
    echo "Content: {$doc->content}\n\n";
}
```

### 👤 Lead Management

CRM functionality for managing contacts and outreach.

```php
// Create a lead
$lead = $iris->leads->create([
    'name' => 'John Smith',
    'email' => 'john@acme.com',
    'company' => 'Acme Corp',
    'tags' => ['enterprise', 'hot'],
]);

// Generate AI-powered outreach
$email = $iris->leads->generateResponse($lead->id,
    'Write a personalized introduction email based on their company profile'
);

// Track activity
$iris->leads->activities($lead->id)->create([
    'type' => 'email_sent',
    'content' => $email,
    'metadata' => ['campaign' => 'Q1_outreach'],
]);
```

### 🔌 Integrations

Access 16+ third-party integrations.

```php
// List available integrations
$integrations = $iris->integrations->available();

// Execute integration function
$files = $iris->integrations->execute(
    type: 'google-drive',
    function: 'search_files',
    params: ['query' => 'Q1 Report', 'limit' => 10]
);

// Get OAuth URL for connecting
$oauthUrl = $iris->integrations->getOAuthUrl('google-drive');
```

**Supported Integrations:**
- Google Drive, Gmail, Calendar
- Slack, Discord
- Mailjet, Mailchimp
- YouTube, ElevenLabs
- Servis.ai (15+ functions)
- And more...

## Error Handling

```php
use IRIS\SDK\Exceptions\{
    IRISException,
    AuthenticationException,
    RateLimitException,
    ValidationException,
    WorkflowException
};

try {
    $response = $iris->agents->chat('agent_123', $messages);
} catch (AuthenticationException $e) {
    // Invalid API key
    echo "Auth failed: " . $e->getMessage();
} catch (RateLimitException $e) {
    // Rate limited - wait and retry
    echo "Rate limited. Retry after {$e->retryAfter} seconds";
    sleep($e->retryAfter);
    // Retry...
} catch (ValidationException $e) {
    // Validation errors
    foreach ($e->validationErrors as $field => $errors) {
        echo "{$field}: " . implode(', ', $errors) . "\n";
    }
} catch (WorkflowException $e) {
    // Workflow execution failed
    echo "Step '{$e->stepName}' failed: " . $e->getMessage();
} catch (IRISException $e) {
    // Generic error
    echo "Error: " . $e->getMessage();
}
```

## Laravel Integration

The SDK includes a Laravel service provider for seamless integration.

### Configuration

```php
// config/iris.php
return [
    'api_key' => env('IRIS_API_KEY'),
    'base_url' => env('IRIS_API_URL', 'https://api.iris.ai'),
];
```

### Usage

```php
use IRIS\SDK\Laravel\Facades\IRIS;

// Using the facade
$response = IRIS::agents()->chat($agentId, $messages);

// Or with dependency injection
use IRIS\SDK\IRIS;

class ChatController
{
    public function chat(IRIS $iris, Request $request)
    {
        return $iris->agents->chat(
            $request->agent_id,
            $request->messages
        );
    }
}
```

## Webhook Handling

Receive real-time workflow events via webhooks.

```php
// In your webhook controller
$handler = $iris->webhooks();

$handler->onStepCompleted(function ($event) {
    Log::info('Step completed', [
        'workflow_id' => $event->workflowId,
        'step' => $event->stepNumber,
        'progress' => $event->progress,
    ]);
});

$handler->onHumanInputRequired(function ($event) {
    // Notify user
    Notification::send($user, new ApprovalRequired($event->task));
});

$handler->onWorkflowCompleted(function ($event) {
    // Process result
    ProcessResult::dispatch($event->result);
});

// Handle incoming webhook
$handler->handle(request());
```

## Configuration Options

```php
$iris = new IRIS([
    'api_key' => 'sk_live_xxxxx',      // Required: API key
    'base_url' => 'https://api.iris.ai', // Optional: API base URL
    'iris_url' => 'https://iris.iris.ai', // Optional: IRIS workflows URL
    'user_id' => 123,                   // Optional: Default user context
    'timeout' => 30,                    // Optional: Request timeout (seconds)
    'retries' => 3,                     // Optional: Max retry attempts
    'webhook_secret' => 'whsec_xxx',   // Optional: Webhook verification secret
    'debug' => false,                   // Optional: Enable debug logging
    'polling_interval' => 500,          // Optional: Workflow polling interval (ms)
    'max_polling_duration' => 300,      // Optional: Max polling time (seconds)
]);

// Switch user context
$iris->asUser(456);
```

## Testing

The SDK includes comprehensive test suites and example scripts.

### Quick Start - Lead Aggregation Test

Test the Lead Aggregation API with automatic environment configuration:

```bash
# 1. Copy environment template
cp .env.example .env

# 2. Add your API key to .env
# IRIS_API_KEY=your_api_key_here

# 3. Run the test
php test-lead-aggregation-user-193.php
```

**Output:**
```
🔧 Configuration:
   Environment: local
   Base URL: https://local.raichu.freelabel.net
   User ID: 193

📊 Lead Statistics:
  ✓ Total Leads: 125
  ✓ Total Tasks: 487
  ✓ Incomplete Tasks: 234
  ✓ Active Leads: 89

  🔥 Top Priority Leads:
     [95] Acme Corp (active)
     [87] Tech Startup (qualified)
✅ Test completed successfully!
```

**Environment Configuration:**

For **local development** (default):
```env
IRIS_ENV=local
IRIS_LOCAL_URL=https://local.raichu.freelabel.net
```

For **production testing**:
```env
IRIS_ENV=production
IRIS_PRODUCTION_URL=https://apiv2.heyiris.io
```

📖 **[Full Testing Documentation →](TEST_README.md)**

### Unit Tests

```php
use IRIS\SDK\Http\MockClient;

// Create mock client for testing
$mockHttp = new MockClient();
$mockHttp->addResponse('POST', '/v1/bloqs/agents/generate-response', [
    'content' => 'Mocked response',
    'tokens_used' => 100,
]);

$iris = new IRIS([
    'api_key' => 'test_key',
    'http_client' => $mockHttp,
]);

// Your tests
$response = $iris->agents->chat('agent_123', $messages);
assert($response->content === 'Mocked response');
```

## API Reference

| Resource | Methods |
|----------|---------|
| `$iris->agents` | `list`, `get`, `create`, `update`, `delete`, `chat`, `multiStep`, `addMemory`, `togglePublic`, `generateWebhook` |
| `$iris->workflows` | `execute`, `getStatus`, `continue`, `completeTask`, `generate`, `generateWithAgents`, `templates`, `importTemplate`, `runs`, `getLogs` |
| `$iris->bloqs` | `list`, `get`, `create`, `update`, `delete`, `lists`, `items`, `uploadFile`, `files` |
| `$iris->leads` | `list`, `get`, `create`, `update`, `delete`, `addNote`, `activities`, `tasks`, `generateResponse`, `recordOutreach` |
| `$iris->integrations` | `available`, `connected`, `getOAuthUrl`, `test`, `execute`, `functions` |
| `$iris->rag` | `query`, `index`, `indexFile`, `searchSimilar`, `delete` |

## License

MIT License - see [LICENSE](LICENSE) for details.

## Support

- Documentation: https://docs.iris.ai/sdk/php
- Issues: https://github.com/iris-ai/php-sdk/issues
- Email: support@iris.ai
