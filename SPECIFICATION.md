# IRIS PHP SDK - Technical Specification

**Version**: 1.0.0
**Target PHP**: 8.1+
**License**: MIT
**Package Name**: `iris-ai/sdk`

---

## 1. Executive Summary

The FreeLABEL PHP SDK provides a fluent, type-safe interface to the IRIS AI platform API, enabling developers to:

- **Manage AI Agents** - Create, configure, and chat with intelligent assistants
- **Execute Workflows** - Run multi-step V5 workflows with human-in-the-loop support
- **Handle Documents** - Manage BloqItems, lists, and file attachments
- **Leverage RAG** - Query vector-enhanced knowledge bases
- **Integrate Services** - Access 16+ third-party integrations (Google, Slack, etc.)

---

## 2. Installation

```bash
composer require iris-ai/sdk
```

### Requirements
- PHP 8.1+
- ext-json
- ext-curl
- guzzlehttp/guzzle ^7.0

---

## 3. Quick Start

```php
<?php
use IRIS\SDK\FreeLABEL;
use IRIS\SDK\Resources\Agents\AgentConfig;

// Initialize client
$fl = new IRIS([
    'api_key' => 'sk_live_xxxxx',
    'base_url' => 'https://api.iris.ai', // optional
]);

// Chat with an agent
$response = $iris->agents->chat('agent_123', [
    ['role' => 'user', 'content' => 'Draft a marketing email for our Q1 launch']
]);

echo $response->content;

// Run a multi-step workflow
$workflow = $iris->workflows->execute([
    'agent_id' => 'agent_123',
    'query' => 'Research competitors and create a report',
]);

// Listen for progress (polling mode)
foreach ($workflow->steps() as $step) {
    echo "Step {$step->number}: {$step->description} ({$step->progress}%)\n";
}

echo "Result: " . $workflow->result()->content;
```

---

## 4. Architecture

```
freelabel/
├── src/
│   ├── FreeLABEL.php              # Main client entry point
│   ├── Config.php                  # Configuration manager
│   ├── Http/
│   │   ├── Client.php              # HTTP client wrapper (Guzzle)
│   │   ├── Request.php             # Request builder
│   │   └── Response.php            # Response wrapper
│   ├── Resources/
│   │   ├── Agents/
│   │   │   ├── AgentsResource.php  # Agent CRUD + chat
│   │   │   ├── Agent.php           # Agent model
│   │   │   └── AgentConfig.php     # Agent configuration DTO
│   │   ├── Workflows/
│   │   │   ├── WorkflowsResource.php
│   │   │   ├── Workflow.php
│   │   │   ├── WorkflowRun.php     # Execution instance
│   │   │   ├── WorkflowStep.php
│   │   │   └── HumanTask.php
│   │   ├── Bloqs/
│   │   │   ├── BloqsResource.php
│   │   │   ├── Bloq.php
│   │   │   ├── BloqList.php
│   │   │   └── BloqItem.php
│   │   ├── Leads/
│   │   │   ├── LeadsResource.php
│   │   │   ├── Lead.php
│   │   │   └── LeadActivity.php
│   │   ├── Integrations/
│   │   │   ├── IntegrationsResource.php
│   │   │   └── Integration.php
│   │   └── RAG/
│   │       ├── RAGResource.php
│   │       └── SearchResult.php
│   ├── Events/
│   │   ├── EventHandler.php        # Polling-based event handler
│   │   ├── WebhookHandler.php      # Webhook receiver
│   │   └── Events/
│   │       ├── WorkflowStarted.php
│   │       ├── StepCompleted.php
│   │       ├── HumanInputRequired.php
│   │       └── WorkflowCompleted.php
│   ├── Exceptions/
│   │   ├── IRISException.php
│   │   ├── AuthenticationException.php
│   │   ├── RateLimitException.php
│   │   ├── ValidationException.php
│   │   └── WorkflowException.php
│   └── Contracts/
│       ├── ResourceInterface.php
│       ├── ModelInterface.php
│       └── EventInterface.php
├── tests/
├── composer.json
└── README.md
```

---

## 5. Core Components

### 5.1 Main Client

```php
<?php
namespace IRIS\SDK;

class IRIS
{
    public AgentsResource $agents;
    public WorkflowsResource $workflows;
    public BloqsResource $bloqs;
    public LeadsResource $leads;
    public IntegrationsResource $integrations;
    public RAGResource $rag;

    public function __construct(array $config)
    {
        $this->config = new Config($config);
        $this->http = new HttpClient($this->config);

        // Initialize resources
        $this->agents = new AgentsResource($this->http);
        $this->workflows = new WorkflowsResource($this->http, $this->config);
        $this->bloqs = new BloqsResource($this->http);
        $this->leads = new LeadsResource($this->http);
        $this->integrations = new IntegrationsResource($this->http);
        $this->rag = new RAGResource($this->http);
    }

    /**
     * Create webhook handler for incoming events
     */
    public function webhooks(): WebhookHandler
    {
        return new WebhookHandler($this->config->webhookSecret);
    }
}
```

### 5.2 Configuration

```php
<?php
namespace IRIS\SDK;

class Config
{
    public string $apiKey;
    public string $baseUrl = 'https://api.iris.ai';
    public string $irisUrl = 'https://workflows.iris.ai';  // V5 workflows
    public int $timeout = 30;
    public int $retries = 3;
    public ?string $webhookSecret = null;
    public bool $debug = false;

    // User context (required for most endpoints)
    public ?int $userId = null;

    public function __construct(array $options)
    {
        $this->apiKey = $options['api_key'] ?? throw new \InvalidArgumentException('api_key required');
        $this->baseUrl = $options['base_url'] ?? $this->baseUrl;
        $this->irisUrl = $options['iris_url'] ?? $this->irisUrl;
        $this->userId = $options['user_id'] ?? null;
        $this->webhookSecret = $options['webhook_secret'] ?? null;
        $this->debug = $options['debug'] ?? false;
    }
}
```

---

## 6. Resource Modules

### 6.1 Agents Resource

**Endpoints Covered:**
- `GET /v1/users/{userId}/bloqs/agents` - List agents
- `POST /v1/users/{userId}/bloqs/agents` - Create agent
- `GET /v1/users/{userId}/bloqs/agents/{agentId}` - Get agent
- `PUT /v1/users/{userId}/bloqs/agents/{agent}` - Update agent
- `DELETE /v1/users/{userId}/bloqs/agents/{agent}` - Delete agent
- `POST /v1/bloqs/agents/generate-response` - Chat with agent
- `POST /v1/bloqs/agents/multi-step-response` - Multi-step agent

```php
<?php
namespace IRIS\SDK\Resources\Agents;

class AgentsResource
{
    /**
     * List all agents for user
     */
    public function list(array $options = []): AgentCollection;

    /**
     * Get a specific agent
     */
    public function get(int|string $agentId): Agent;

    /**
     * Create a new agent
     */
    public function create(AgentConfig $config): Agent;

    /**
     * Update an existing agent
     */
    public function update(int|string $agentId, array $data): Agent;

    /**
     * Delete an agent
     */
    public function delete(int|string $agentId): bool;

    /**
     * Chat with an agent (single turn)
     */
    public function chat(
        int|string $agentId,
        array $messages,
        array $options = []
    ): ChatResponse;

    /**
     * Multi-step agent conversation (V5 workflow)
     */
    public function multiStep(
        int|string $agentId,
        string $query,
        array $options = []
    ): WorkflowRun;

    /**
     * Add memory/file to agent
     */
    public function addMemory(
        int|string $agentId,
        string $filePath,
        array $metadata = []
    ): bool;
}
```

**Agent Configuration DTO:**

```php
<?php
namespace IRIS\SDK\Resources\Agents;

class AgentConfig
{
    public function __construct(
        public string $name,
        public string $prompt,
        public string $type = 'assistant',  // assistant, human, specialist
        public string $model = 'gpt-4o-mini',
        public array $integrations = [],     // ['google-drive', 'gmail', etc.]
        public ?int $knowledgeBaseId = null, // Bloq ID for RAG
        public array $personality = [
            'communication_style' => 'professional',
            'response_mode' => 'conversational',
            'response_length' => 'medium',
        ],
        public array $capabilities = [],
        public bool $isPublic = false,
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'prompt' => $this->prompt,
            'type' => $this->type,
            'model' => $this->model,
            'integrations' => $this->integrations,
            'bloq_id' => $this->knowledgeBaseId,
            'personality' => $this->personality,
            'capabilities' => $this->capabilities,
            'is_public' => $this->isPublic,
        ];
    }
}
```

### 6.2 Workflows Resource (V5 System)

**Endpoints Covered:**
- `POST /v1/users/{user}/bloqs/workflow-runs` - Start workflow
- `GET /v1/users/{user}/bloqs/workflow-runs/{runId}` - Get run status
- `POST /v1/bloqs/workflow-runs/{runId}/continue` - Continue workflow
- `POST /v1/bloqs/workflow-human-tasks/{taskId}/complete` - Complete human task
- `POST /v1/bloqs/workflows/generate` - Generate workflow from natural language

```php
<?php
namespace IRIS\SDK\Resources\Workflows;

class WorkflowsResource
{
    /**
     * Execute a workflow (async with polling support)
     */
    public function execute(array $params): WorkflowRun;

    /**
     * Get workflow run status
     */
    public function getStatus(string $runId): WorkflowStatus;

    /**
     * Continue a paused workflow
     */
    public function continue(string $runId, array $input = []): WorkflowRun;

    /**
     * Complete a human task
     */
    public function completeTask(string $taskId, array $response): bool;

    /**
     * Generate workflow from natural language
     */
    public function generate(string $description, array $options = []): Workflow;

    /**
     * List workflow templates
     */
    public function templates(array $filters = []): TemplateCollection;

    /**
     * Import a template
     */
    public function importTemplate(string $slug): Workflow;
}
```

**Workflow Run Model:**

```php
<?php
namespace IRIS\SDK\Resources\Workflows;

class WorkflowRun
{
    public string $id;
    public string $status;  // running, completed, awaiting_human, failed
    public int $progress;   // 0-100
    public ?string $currentStep;
    public array $stepRecords = [];
    public ?array $result = null;
    public ?HumanTask $pendingTask = null;

    /**
     * Poll for step updates (generator)
     */
    public function steps(): \Generator
    {
        while ($this->status === 'running') {
            $status = $this->resource->getStatus($this->id);

            foreach ($status->stepRecords as $step) {
                if (!isset($this->yieldedSteps[$step->id])) {
                    $this->yieldedSteps[$step->id] = true;
                    yield $step;
                }
            }

            if ($status->status === 'awaiting_human') {
                $this->pendingTask = $status->humanTask;
                return;
            }

            if ($status->status === 'completed') {
                $this->result = $status->result;
                return;
            }

            usleep(500000); // 500ms polling interval
        }
    }

    /**
     * Get final result (blocks until complete)
     */
    public function result(): WorkflowResult
    {
        // Exhaust the generator
        iterator_to_array($this->steps());
        return new WorkflowResult($this->result);
    }

    /**
     * Check if human input is required
     */
    public function needsHumanInput(): bool
    {
        return $this->status === 'awaiting_human';
    }

    /**
     * Provide human input and continue
     */
    public function provideInput(array $input): self
    {
        if ($this->pendingTask) {
            $this->resource->completeTask($this->pendingTask->id, $input);
        }
        return $this->resource->continue($this->id, $input);
    }
}
```

### 6.3 Bloqs Resource (Documents/Content)

**Endpoints Covered:**
- `GET/POST/PUT/DELETE /v1/user/{userId}/bloqs/*` - CRUD operations
- `GET/POST /v1/user/{userId}/bloqs/{bloqId}/lists` - List management
- `POST /v1/user/{userId}/bloqs/lists/{listId}/items` - Item management
- `POST /v1/cloud-files/upload` - File uploads

```php
<?php
namespace IRIS\SDK\Resources\Bloqs;

class BloqsResource
{
    /**
     * List all bloqs for user
     */
    public function list(array $options = []): BloqCollection;

    /**
     * Get a specific bloq with its contents
     */
    public function get(int $bloqId, bool $withLists = false): Bloq;

    /**
     * Create a new bloq (project container)
     */
    public function create(string $title, array $options = []): Bloq;

    /**
     * Update bloq metadata
     */
    public function update(int $bloqId, array $data): Bloq;

    /**
     * Delete a bloq
     */
    public function delete(int $bloqId): bool;

    /**
     * List operations
     */
    public function lists(int $bloqId): ListsResource;

    /**
     * Item operations
     */
    public function items(int $listId): ItemsResource;

    /**
     * Upload file to bloq
     */
    public function uploadFile(
        int $bloqId,
        string $filePath,
        array $metadata = []
    ): CloudFile;

    /**
     * Get bloq files
     */
    public function files(int $bloqId): FileCollection;
}
```

**Fluent List/Item Management:**

```php
// Create a bloq with nested lists and items
$bloq = $iris->bloqs->create('Q1 Marketing Campaign', [
    'description' => 'All marketing materials for Q1',
]);

// Add a list
$list = $iris->bloqs->lists($bloq->id)->create([
    'title' => 'Social Media Posts',
    'type' => 'checklist',
]);

// Add items to the list
$iris->bloqs->items($list->id)->create([
    'title' => 'LinkedIn announcement',
    'content' => 'Draft content here...',
    'metadata' => ['platform' => 'linkedin'],
]);

// Upload a file
$file = $iris->bloqs->uploadFile($bloq->id, '/path/to/brand-guide.pdf', [
    'title' => 'Brand Guidelines',
    'tags' => ['brand', 'design'],
]);
```

### 6.4 RAG Resource (Knowledge Base)

**Endpoints Covered:**
- `POST /v1/vector/store` - Index content
- `POST /v1/vector/search` - Semantic search
- Internal: Uses Pinecone via fl-iris-api

```php
<?php
namespace IRIS\SDK\Resources\RAG;

class RAGResource
{
    /**
     * Query knowledge base with semantic search
     */
    public function query(
        string $question,
        array $filters = [],
        int $topK = 5
    ): RAGResponse;

    /**
     * Index new content
     */
    public function index(
        string $content,
        array $metadata = []
    ): bool;

    /**
     * Index file (auto-extracts content)
     */
    public function indexFile(
        string $filePath,
        array $metadata = []
    ): bool;

    /**
     * Search similar documents
     */
    public function searchSimilar(
        string $query,
        int $limit = 5,
        array $filters = []
    ): SearchResultCollection;

    /**
     * Delete indexed content
     */
    public function delete(string $vectorId): bool;
}
```

**Usage Example:**

```php
// Index a document
$iris->rag->index(
    content: 'Our company policy states that all employees must...',
    metadata: [
        'bloq_id' => 32,
        'type' => 'policy',
        'title' => 'Employee Handbook',
    ]
);

// Query with semantic search
$results = $iris->rag->query(
    question: 'What is the vacation policy?',
    filters: ['bloq_id' => 32],
    topK: 3
);

foreach ($results->documents as $doc) {
    echo "Score: {$doc->score}\n";
    echo "Content: {$doc->content}\n";
}
```

### 6.5 Leads Resource

**Endpoints Covered:**
- Full CRUD for leads
- Notes, activities, tasks
- Gmail integration
- Outreach tracking

```php
<?php
namespace IRIS\SDK\Resources\Leads;

class LeadsResource
{
    public function list(array $filters = []): LeadCollection;
    public function get(int $leadId): Lead;
    public function create(array $data): Lead;
    public function update(int $leadId, array $data): Lead;
    public function delete(int $leadId): bool;

    // Notes
    public function addNote(int $leadId, string $content, array $metadata = []): Note;

    // Activities
    public function activities(int $leadId): ActivityResource;

    // Tasks
    public function tasks(int $leadId): TaskResource;

    // AI Response
    public function generateResponse(int $leadId, string $context = ''): string;

    // Outreach
    public function recordOutreach(int $leadId, array $data): bool;
}
```

### 6.6 Integrations Resource

```php
<?php
namespace IRIS\SDK\Resources\Integrations;

class IntegrationsResource
{
    /**
     * Get available integrations
     */
    public function available(): IntegrationCollection;

    /**
     * Get user's connected integrations
     */
    public function connected(): IntegrationCollection;

    /**
     * Get OAuth URL for integration
     */
    public function getOAuthUrl(string $type): string;

    /**
     * Test integration connection
     */
    public function test(string $type): TestResult;

    /**
     * Execute integration function
     */
    public function execute(
        string $type,
        string $function,
        array $params = []
    ): array;

    /**
     * Get available functions for integration
     */
    public function functions(string $type): array;
}
```

**Supported Integrations:**
- Google Drive (11 functions)
- Gmail (6 functions)
- Google Calendar (6 functions)
- Slack
- Discord
- Mailjet (6 functions)
- Servis.ai (15+ functions)
- YouTube Transcript
- ElevenLabs
- And more...

---

## 7. Error Handling

```php
<?php
namespace IRIS\SDK\Exceptions;

// Base exception
class IRISException extends \Exception
{
    public ?array $errors = null;
    public ?string $requestId = null;
}

// Authentication failed (401/403)
class AuthenticationException extends IRISException {}

// Rate limit exceeded (429)
class RateLimitException extends IRISException
{
    public int $retryAfter;  // Seconds until rate limit resets
}

// Validation errors (422)
class ValidationException extends IRISException
{
    public array $errors;  // Field-specific errors
}

// Workflow execution failed
class WorkflowException extends IRISException
{
    public string $stepName;
    public array $stepParams;
}
```

**Usage:**

```php
use IRIS\SDK\Exceptions\{
    RateLimitException,
    ValidationException,
    WorkflowException
};

try {
    $response = $iris->agents->chat('agent_123', $messages);
} catch (RateLimitException $e) {
    // Wait and retry
    sleep($e->retryAfter);
    $response = $iris->agents->chat('agent_123', $messages);
} catch (ValidationException $e) {
    // Handle validation errors
    foreach ($e->errors as $field => $messages) {
        echo "$field: " . implode(', ', $messages) . "\n";
    }
} catch (WorkflowException $e) {
    // Workflow step failed
    echo "Step '{$e->stepName}' failed: {$e->getMessage()}\n";
}
```

---

## 8. Event Handling

### 8.1 Polling Mode (Default)

```php
$workflow = $iris->workflows->execute([
    'agent_id' => 'agent_123',
    'query' => 'Research competitors',
]);

// Poll for updates
foreach ($workflow->steps() as $step) {
    echo "[{$step->progress}%] {$step->description}\n";

    if ($step->status === 'completed') {
        echo "  Result: " . json_encode($step->result) . "\n";
    }
}

// Handle human-in-the-loop
if ($workflow->needsHumanInput()) {
    $task = $workflow->pendingTask;
    echo "Human input required: {$task->description}\n";

    // Provide input and continue
    $workflow->provideInput([
        'approved' => true,
        'feedback' => 'Looks good, proceed',
    ]);
}
```

### 8.2 Webhook Mode

```php
// Set up webhook receiver in your controller
$handler = $iris->webhooks();

$handler->onStepCompleted(function ($event) {
    Log::info("Step completed", [
        'workflow_id' => $event->workflowId,
        'step' => $event->stepNumber,
        'progress' => $event->progress,
    ]);
});

$handler->onHumanInputRequired(function ($event) {
    // Notify user via your preferred channel
    Notification::send($user, new HumanTaskNotification($event->task));
});

$handler->onWorkflowCompleted(function ($event) {
    // Process final result
    ProcessWorkflowResult::dispatch($event->result);
});

// Handle incoming webhook
$handler->handle(request());
```

---

## 9. Laravel Integration

### Service Provider

```php
<?php
namespace IRIS\SDK\Laravel;

class IRISServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/config.php', 'freelabel');

        $this->app->singleton(FreeLABEL::class, function ($app) {
            return new IRIS([
                'api_key' => config('iris.api_key'),
                'base_url' => config('iris.base_url'),
                'user_id' => auth()->id(),
            ]);
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/config.php' => config_path('freelabel.php'),
        ], 'iris-config');
    }
}
```

### Facade

```php
<?php
namespace IRIS\SDK\Laravel\Facades;

class IRIS extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \IRIS\SDK\FreeLABEL::class;
    }
}
```

**Usage:**

```php
use IRIS\SDK\Laravel\Facades\FreeLABEL;

// In controller
$response = FreeLABEL::agents()->chat($agentId, $messages);

// Or with dependency injection
public function chat(FreeLABEL $fl, Request $request)
{
    return $iris->agents->chat(
        $request->agent_id,
        $request->messages
    );
}
```

---

## 10. WordPress Integration Guide

```php
<?php
/**
 * Plugin Name: FreeLABEL AI Assistant
 */

require_once __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\FreeLABEL;

// Initialize on plugin load
add_action('init', function() {
    global $freelabel;

    $freelabel = new IRIS([
        'api_key' => get_option('iris.api_key'),
        'user_id' => get_current_user_id(),
    ]);
});

// Shortcode for agent chat
add_shortcode('fl_agent_chat', function($atts) {
    global $freelabel;

    $atts = shortcode_atts([
        'agent_id' => '',
    ], $atts);

    // Render chat interface
    return fl_render_chat_widget($atts['agent_id']);
});

// AJAX handler for chat
add_action('wp_ajax_fl_chat', function() {
    global $freelabel;

    $response = $freelabel->agents->chat(
        $_POST['agent_id'],
        $_POST['messages']
    );

    wp_send_json_success($response);
});
```

---

## 11. Use Cases

### Use Case A: Simple Agent Chat

```php
$fl = new IRIS(['api_key' => 'sk_...', 'user_id' => 123]);

$response = $iris->agents->chat('agent_456', [
    ['role' => 'user', 'content' => 'Summarize our Q3 sales data']
]);

echo $response->content;
```

### Use Case B: Multi-Step Research Workflow

```php
$workflow = $iris->workflows->execute([
    'agent_id' => 'research_agent',
    'query' => 'Research top 5 competitors in the CRM space and create a comparison report',
]);

// Show progress
foreach ($workflow->steps() as $step) {
    sendProgressUpdate($step->progress, $step->description);
}

// Get final report
$report = $workflow->result();
saveReport($report->content, $report->files);
```

### Use Case C: Document Management with RAG

```php
// Create knowledge base
$kb = $iris->bloqs->create('Company Knowledge Base', [
    'description' => 'Internal documentation and policies',
]);

// Upload documents
$iris->bloqs->uploadFile($kb->id, '/path/to/handbook.pdf');
$iris->bloqs->uploadFile($kb->id, '/path/to/policies.pdf');

// Create agent with knowledge base
$agent = $iris->agents->create(new AgentConfig(
    name: 'HR Assistant',
    prompt: 'You are an HR assistant that helps employees with policy questions.',
    model: 'gpt-4o-mini',
    knowledgeBaseId: $kb->id,
));

// Query with RAG-enhanced responses
$response = $iris->agents->chat($agent->id, [
    ['role' => 'user', 'content' => 'What is our parental leave policy?']
]);
// Response includes relevant policy excerpts from uploaded documents
```

### Use Case D: Lead Management with AI

```php
// Create lead
$lead = $iris->leads->create([
    'name' => 'John Smith',
    'email' => 'john@example.com',
    'company' => 'Acme Corp',
]);

// Generate AI response for outreach
$emailContent = $iris->leads->generateResponse($lead->id,
    'Write a personalized introduction email based on their company profile'
);

// Record activity
$iris->leads->activities($lead->id)->create([
    'type' => 'email_sent',
    'content' => $emailContent,
    'metadata' => ['campaign' => 'Q1_outreach'],
]);
```

---

## 12. Testing

```php
// Use mock HTTP client for testing
$mockHttp = new MockHttpClient();
$mockHttp->addResponse('POST', '/v1/bloqs/agents/generate-response', [
    'content' => 'Mocked response',
    'tokens_used' => 100,
]);

$fl = new IRIS([
    'api_key' => 'test_key',
    'http_client' => $mockHttp,
]);

// Test your integration
$response = $iris->agents->chat('agent_123', [['role' => 'user', 'content' => 'test']]);
$this->assertEquals('Mocked response', $response->content);
```

---

## 13. Implementation Roadmap

### Phase 1: Core SDK (2-3 weeks)
- [ ] Project setup (composer, autoloading, CI/CD)
- [ ] HTTP client with auth, retries, rate limiting
- [ ] Agents resource (CRUD + chat)
- [ ] Basic error handling
- [ ] Unit tests

### Phase 2: Workflows (2 weeks)
- [ ] Workflow execution with polling
- [ ] Human-in-the-loop support
- [ ] Workflow templates
- [ ] Step progress tracking

### Phase 3: Documents & RAG (1-2 weeks)
- [ ] Bloqs CRUD
- [ ] Lists and Items
- [ ] File uploads
- [ ] RAG query interface

### Phase 4: Integrations (1 week)
- [ ] Integration discovery
- [ ] OAuth URL generation
- [ ] Function execution
- [ ] Leads resource

### Phase 5: Ecosystem (1-2 weeks)
- [ ] Laravel service provider
- [ ] WordPress plugin skeleton
- [ ] Webhook handler
- [ ] Documentation site

---

## 14. API Reference Summary

| Resource | Methods |
|----------|---------|
| `$iris->agents` | `list`, `get`, `create`, `update`, `delete`, `chat`, `multiStep`, `addMemory` |
| `$iris->workflows` | `execute`, `getStatus`, `continue`, `completeTask`, `generate`, `templates`, `importTemplate` |
| `$iris->bloqs` | `list`, `get`, `create`, `update`, `delete`, `lists`, `items`, `uploadFile`, `files` |
| `$iris->leads` | `list`, `get`, `create`, `update`, `delete`, `addNote`, `activities`, `tasks`, `generateResponse`, `recordOutreach` |
| `$iris->integrations` | `available`, `connected`, `getOAuthUrl`, `test`, `execute`, `functions` |
| `$iris->rag` | `query`, `index`, `indexFile`, `searchSimilar`, `delete` |

---

## 15. Security Considerations

1. **API Key Storage**: Never commit API keys. Use environment variables.
2. **Server-Side Only**: This SDK is designed for server-side use. Never expose API keys in browser code.
3. **Rate Limiting**: Built-in handling with automatic retries and backoff.
4. **Webhook Verification**: Always verify webhook signatures using `$iris->webhooks()->verify($payload, $signature)`.
5. **Input Validation**: All user input should be validated before passing to SDK methods.

---

## Next Steps

1. **Repository Setup**: Create `freelabel/php-sdk` repository
2. **Composer Package**: Register on Packagist
3. **CI/CD**: GitHub Actions for tests, linting, release automation
4. **Documentation**: Auto-generate from PHPDoc + manual guides
5. **Examples**: Create example projects (Laravel app, WordPress plugin)

---

*Last Updated: December 2024*
*Specification Version: 1.0.0*
