# IRIS PHP SDK - Implementation Handoff Document

**Purpose:** Technical requirements for completing the IRIS PHP SDK
**Estimated Effort:** 4-6 hours
**Priority:** Resources first, then Tests, then Laravel Provider

---

## Table of Contents
1. [Project Context](#1-project-context)
2. [Existing Patterns to Follow](#2-existing-patterns-to-follow)
3. [Task 1: Bloqs Resource](#3-task-1-bloqs-resource)
4. [Task 2: Leads Resource](#4-task-2-leads-resource)
5. [Task 3: Integrations Resource](#5-task-3-integrations-resource)
6. [Task 4: RAG Resource](#6-task-4-rag-resource)
7. [Task 5: Laravel Service Provider](#7-task-5-laravel-service-provider)
8. [Task 6: PHPUnit Tests](#8-task-6-phpunit-tests)
9. [Code Style Requirements](#9-code-style-requirements)

---

## 1. Project Context

### Location
```
/Users/AlexMayo/Sites/freelabel/fl-docker-dev/sdk/php/
```

### Namespace
```php
namespace IRIS\SDK;
```

### Package Name
```
iris-ai/sdk
```

### Existing Structure
```
src/
├── IRIS.php                 # Main client (COMPLETED)
├── Config.php               # Configuration (COMPLETED)
├── Http/Client.php          # HTTP client (COMPLETED)
├── Exceptions/              # All exceptions (COMPLETED)
├── Resources/
│   ├── Agents/              # COMPLETED
│   ├── Workflows/           # COMPLETED
│   ├── Bloqs/               # NEEDS IMPLEMENTATION
│   ├── Leads/               # NEEDS IMPLEMENTATION
│   ├── Integrations/        # NEEDS IMPLEMENTATION
│   └── RAG/                 # NEEDS IMPLEMENTATION
└── Laravel/                 # NEEDS IMPLEMENTATION
```

### Dependencies
- PHP 8.1+
- Guzzle 7.0+
- PSR-7 HTTP Message

---

## 2. Existing Patterns to Follow

### 2.1 Resource Class Pattern

Every resource follows this pattern:

```php
<?php

declare(strict_types=1);

namespace IRIS\SDK\Resources\{ResourceName};

use IRIS\SDK\Config;
use IRIS\SDK\Http\Client;

class {ResourceName}Resource
{
    protected Client $http;
    protected Config $config;

    public function __construct(Client $http, Config $config)
    {
        $this->http = $http;
        $this->config = $config;
    }

    // Methods...
}
```

### 2.2 Model Class Pattern

```php
<?php

declare(strict_types=1);

namespace IRIS\SDK\Resources\{ResourceName};

class {ModelName}
{
    // Public typed properties
    public int $id;
    public string $name;
    // etc...

    // Raw attributes storage
    protected array $attributes;

    public function __construct(array $data)
    {
        $this->attributes = $data;

        // Map data to properties with defaults
        $this->id = (int) ($data['id'] ?? 0);
        $this->name = $data['name'] ?? '';
    }

    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function toArray(): array
    {
        return $this->attributes;
    }
}
```

### 2.3 Collection Class Pattern

```php
<?php

declare(strict_types=1);

namespace IRIS\SDK\Resources\{ResourceName};

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

class {ModelName}Collection implements IteratorAggregate, Countable
{
    protected array $items;
    protected array $meta;

    public function __construct(array $items, array $meta = [])
    {
        $this->items = $items;
        $this->meta = $meta;
    }

    public function all(): array { return $this->items; }
    public function first(): ?{ModelName} { return $this->items[0] ?? null; }
    public function count(): int { return count($this->items); }
    public function isEmpty(): bool { return empty($this->items); }
    public function getIterator(): Traversable { return new ArrayIterator($this->items); }
    public function getMeta(): array { return $this->meta; }
    public function hasMorePages(): bool { /* pagination logic */ }
    public function toArray(): array { /* map to arrays */ }
}
```

### 2.4 HTTP Client Usage

```php
// GET request
$response = $this->http->get('/api/v1/endpoint', ['query' => 'params']);

// POST request
$response = $this->http->post('/api/v1/endpoint', ['body' => 'data']);

// PUT request
$response = $this->http->put('/api/v1/endpoint', ['body' => 'data']);

// DELETE request
$response = $this->http->delete('/api/v1/endpoint');

// File upload
$response = $this->http->upload('/api/v1/endpoint', '/path/to/file', ['metadata' => 'value']);
```

### 2.5 User ID Requirement

Many endpoints require user context:

```php
$userId = $this->config->requireUserId(); // Throws if not set
$response = $this->http->get("/api/v1/users/{$userId}/resource");
```

---

## 3. Task 1: Bloqs Resource

### Files to Create

```
src/Resources/Bloqs/
├── BloqsResource.php
├── Bloq.php
├── BloqCollection.php
├── BloqList.php
├── BloqListCollection.php
├── BloqItem.php
├── BloqItemCollection.php
├── CloudFile.php
├── CloudFileCollection.php
├── ListsResource.php      # Sub-resource
└── ItemsResource.php      # Sub-resource
```

### API Endpoints to Cover

#### Bloqs CRUD
```
GET    /api/v1/user/{userId}/bloqs                  → list(array $options): BloqCollection
POST   /api/v1/user/{userId}/bloqs                  → create(string $title, array $options): Bloq
GET    /api/v1/user/{userId}/bloqs/{bloqId}         → get(int $bloqId): Bloq
PUT    /api/v1/user/{userId}/bloqs/{bloqId}         → update(int $bloqId, array $data): Bloq
DELETE /api/v1/user/{userId}/bloqs/{bloqId}         → delete(int $bloqId): bool
GET    /api/v1/user/{userId}/bloqs/count            → count(): int
```

#### Bloq Overview
```
GET    /api/v1/users/{userId}/bloqs/overview        → overview(): array
GET    /api/v1/users/{userId}/bloqs/recent-items    → recentItems(): BloqItemCollection
GET    /api/v1/users/{userId}/bloqs/{bloqId}/details → getDetails(int $bloqId): array
POST   /api/v1/users/{userId}/bloqs/{bloqId}/access → trackAccess(int $bloqId): bool
POST   /api/v1/users/{userId}/bloqs/{bloqId}/pin    → togglePin(int $bloqId): Bloq
```

#### Lists (Sub-resource)
```
GET    /api/v1/user/{userId}/bloqs/{bloqId}/lists           → lists(int $bloqId): ListsResource
POST   /api/v1/user/{userId}/bloqs/{bloqId}/lists           → ListsResource::create(array $data): BloqList
GET    /api/v1/user/{userId}/bloqs/list/{listId}            → ListsResource::get(int $listId): BloqList
PATCH  /api/v1/user/{userId}/bloqs/list/{listId}            → ListsResource::update(int $listId, array $data): BloqList
DELETE /api/v1/user/{userId}/bloqs/list/{listId}            → ListsResource::delete(int $listId): bool
PATCH  /api/v1/user/{userId}/bloqs/list/{listId}/position   → ListsResource::updatePosition(int $listId, int $position): BloqList
```

#### Items (Sub-resource)
```
POST   /api/v1/user/{userId}/bloqs/lists/{listId}/items              → items(int $listId): ItemsResource
GET    /api/v1/user/{userId}/bloqs/list/{itemId}/chat/messages       → ItemsResource::getMessages(int $itemId): array
POST   /api/v1/user/{userId}/bloqs/list/{itemId}/chat/messages       → ItemsResource::addMessage(int $itemId, array $message): array
DELETE /api/v1/user/{userId}/bloqs/list/item/{itemId}                → ItemsResource::delete(int $itemId): bool
PATCH  /api/v1/user/{userId}/bloqs/list/item/{itemId}                → ItemsResource::update(int $itemId, array $data): BloqItem
POST   /api/v1/user/{userId}/bloqs/list/item/{id}/make-public        → ItemsResource::makePublic(int $itemId): BloqItem
POST   /api/v1/user/{userId}/bloqs/list/item/{id}/make-private       → ItemsResource::makePrivate(int $itemId): BloqItem
```

#### Cloud Files
```
GET    /api/v1/cloud-files                          → files(): CloudFileCollection
POST   /api/v1/cloud-files/upload                   → uploadFile(int $bloqId, string $filePath, array $metadata): CloudFile
GET    /api/v1/cloud-files/{cloudFile}              → getFile(int $fileId): CloudFile
GET    /api/v1/cloud-files/{cloudFile}/download     → downloadFile(int $fileId): string (URL)
GET    /api/v1/cloud-files/{cloudFile}/status       → getFileStatus(int $fileId): array
DELETE /api/v1/cloud-files/{cloudFile}              → deleteFile(int $fileId): bool
GET    /api/v1/bloqs/{bloqId}/files                 → getBloqFiles(int $bloqId): CloudFileCollection
GET    /api/v1/cloud-files/supported-types          → supportedFileTypes(): array
```

### Bloq Model Properties

```php
class Bloq
{
    public int $id;
    public string $title;
    public ?string $description;
    public int $userId;
    public bool $isPinned;
    public ?string $color;
    public ?string $icon;
    public int $itemCount;
    public int $listCount;
    public ?string $createdAt;
    public ?string $updatedAt;

    // Relations (optional, may be null)
    public ?array $lists;
    public ?array $items;
}
```

### BloqList Model Properties

```php
class BloqList
{
    public int $id;
    public int $bloqId;
    public string $title;
    public ?string $type; // 'folder', 'checklist', 'kanban', etc.
    public int $position;
    public int $itemCount;
    public ?string $createdAt;
    public ?string $updatedAt;

    public ?array $items;
}
```

### BloqItem Model Properties

```php
class BloqItem
{
    public int $id;
    public int $listId;
    public string $title;
    public ?string $content;
    public ?string $type;
    public int $position;
    public bool $isPublic;
    public ?array $metadata;
    public ?string $createdAt;
    public ?string $updatedAt;
}
```

### CloudFile Model Properties

```php
class CloudFile
{
    public int $id;
    public ?int $bloqId;
    public string $filename;
    public string $originalFilename;
    public string $mimeType;
    public int $size;
    public string $url;
    public string $status; // 'pending', 'processing', 'ready', 'failed'
    public ?array $extractionMetadata;
    public ?string $createdAt;
}
```

### Sub-Resource Pattern

```php
// In BloqsResource
public function lists(int $bloqId): ListsResource
{
    return new ListsResource($this->http, $this->config, $bloqId);
}

public function items(int $listId): ItemsResource
{
    return new ItemsResource($this->http, $this->config, $listId);
}
```

```php
// ListsResource example
class ListsResource
{
    protected Client $http;
    protected Config $config;
    protected int $bloqId;

    public function __construct(Client $http, Config $config, int $bloqId)
    {
        $this->http = $http;
        $this->config = $config;
        $this->bloqId = $bloqId;
    }

    public function all(): BloqListCollection { /* ... */ }
    public function create(array $data): BloqList { /* ... */ }
    // etc...
}
```

---

## 4. Task 2: Leads Resource

### Files to Create

```
src/Resources/Leads/
├── LeadsResource.php
├── Lead.php
├── LeadCollection.php
├── LeadActivity.php
├── LeadActivityCollection.php
├── LeadTask.php
├── LeadTaskCollection.php
├── LeadTag.php
├── LeadStage.php
├── ActivitiesResource.php   # Sub-resource
└── TasksResource.php        # Sub-resource
```

### API Endpoints to Cover

#### Leads CRUD
```
GET    /api/v1/leads                    → list(array $filters): LeadCollection
GET    /api/v1/users/{userId}/leads     → listForUser(): LeadCollection
GET    /api/v1/leads/{id}               → get(int $leadId): Lead
POST   /api/v1/leads                    → create(array $data): Lead
PUT    /api/v1/leads/{id}               → update(int $leadId, array $data): Lead
DELETE /api/v1/leads/{id}               → delete(int $leadId): bool
```

#### Lead Notes
```
POST   /api/v1/leads/{id}/notes         → addNote(int $leadId, string $content, array $metadata): array
```

#### Lead AI & Communication
```
GET    /api/v1/leads/{id}/generate-response  → generateResponse(int $leadId, string $context): string
POST   /api/v1/leads/{id}/sync-gmail         → syncGmail(int $leadId): bool
GET    /api/v1/leads/{id}/gmail-thread       → getGmailThread(int $leadId): array
GET    /api/v1/leads/{id}/gmail-threads      → getGmailThreads(int $leadId): array
```

#### Lead Organization
```
POST   /api/v1/leads/{id}/attach-bloq    → attachBloq(int $leadId, int $bloqId): bool
POST   /api/v1/leads/{id}/detach-bloq    → detachBloq(int $leadId, int $bloqId): bool
PATCH  /api/v1/leads/{id}/outreach-agent → setOutreachAgent(int $leadId, int $agentId): Lead
GET    /api/v1/leads/{id}/outreach-config → getOutreachConfig(int $leadId): array
```

#### Lead Tags
```
GET    /api/v1/user/{userId}/lead-tags           → tags(): array
POST   /api/v1/user/{userId}/lead-tags           → createTag(array $data): LeadTag
PUT    /api/v1/user/{userId}/lead-tags/{tagId}   → updateTag(int $tagId, array $data): LeadTag
DELETE /api/v1/user/{userId}/lead-tags/{tagId}   → deleteTag(int $tagId): bool
```

#### Lead Stages
```
GET    /api/v1/user/{userId}/lead-stages                  → stages(): array
POST   /api/v1/user/{userId}/lead-stages                  → createStage(array $data): LeadStage
PUT    /api/v1/user/{userId}/lead-stages/{stageId}        → updateStage(int $stageId, array $data): LeadStage
DELETE /api/v1/user/{userId}/lead-stages/{stageId}        → deleteStage(int $stageId): bool
POST   /api/v1/user/{userId}/lead-stages/update-order     → reorderStages(array $order): bool
```

#### Lead Outreach
```
GET  /api/v1/leads/{leadId}/outreach/check       → checkOutreachEligibility(int $leadId): array
POST /api/v1/leads/{leadId}/outreach/record      → recordOutreach(int $leadId, array $data): bool
GET  /api/v1/leads/{leadId}/outreach/info        → getOutreachInfo(int $leadId): array
PUT  /api/v1/leads/{leadId}/outreach/auto-respond → setAutoRespond(int $leadId, bool $enabled): bool
```

#### Lead Activities (Sub-resource)
```
GET    /api/v1/leads/{leadId}/activities                    → activities(int $leadId): ActivitiesResource
POST   /api/v1/leads/{leadId}/activities                    → ActivitiesResource::create(array $data): LeadActivity
POST   /api/v1/leads/{leadId}/activities/ai-message         → ActivitiesResource::addAiMessage(string $content): LeadActivity
DELETE /api/v1/leads/{leadId}/activities/{activityId}       → ActivitiesResource::delete(int $activityId): bool
GET    /api/v1/activities/types                             → activityTypes(): array
```

#### Lead Tasks (Sub-resource)
```
GET    /api/v1/leads/{leadId}/tasks                → tasks(int $leadId): TasksResource
POST   /api/v1/leads/{leadId}/tasks                → TasksResource::create(array $data): LeadTask
PUT    /api/v1/leads/{leadId}/tasks/{taskId}       → TasksResource::update(int $taskId, array $data): LeadTask
DELETE /api/v1/leads/{leadId}/tasks/{taskId}       → TasksResource::delete(int $taskId): bool
POST   /api/v1/leads/{leadId}/tasks/reorder        → TasksResource::reorder(array $order): bool
```

### Lead Model Properties

```php
class Lead
{
    public int $id;
    public string $name;
    public ?string $email;
    public ?string $phone;
    public ?string $company;
    public ?string $title;
    public ?string $source;
    public ?int $stageId;
    public ?string $stageName;
    public ?int $outreachAgentId;
    public ?float $score;
    public array $tags;
    public ?array $customFields;
    public ?string $notes;
    public ?string $lastContactedAt;
    public ?string $createdAt;
    public ?string $updatedAt;

    // Helper methods
    public function hasEmail(): bool;
    public function hasPhone(): bool;
    public function isHot(): bool; // score > 80
}
```

### LeadActivity Model Properties

```php
class LeadActivity
{
    public int $id;
    public int $leadId;
    public string $type; // 'email_sent', 'call', 'meeting', 'note', 'ai_message', etc.
    public string $content;
    public ?array $metadata;
    public ?int $userId;
    public ?string $createdAt;
}
```

### LeadTask Model Properties

```php
class LeadTask
{
    public int $id;
    public int $leadId;
    public string $title;
    public ?string $description;
    public string $status; // 'pending', 'in_progress', 'completed'
    public ?string $dueDate;
    public int $position;
    public ?string $completedAt;
    public ?string $createdAt;

    public function isOverdue(): bool;
    public function isCompleted(): bool;
}
```

---

## 5. Task 3: Integrations Resource

### Files to Create

```
src/Resources/Integrations/
├── IntegrationsResource.php
├── Integration.php
├── IntegrationCollection.php
├── IntegrationFunction.php
└── TestResult.php
```

### API Endpoints to Cover

#### Integration Management
```
GET    /api/v1/integrations              → list(): IntegrationCollection
POST   /api/v1/integrations              → create(array $data): Integration
GET    /api/v1/integrations/{id}         → get(int $integrationId): Integration
PUT    /api/v1/integrations/{id}         → update(int $integrationId, array $data): Integration
DELETE /api/v1/integrations/{id}         → delete(int $integrationId): bool
POST   /api/v1/integrations/{id}/test    → test(int $integrationId): TestResult
GET    /api/v1/integrations/types        → types(): array
```

#### OAuth
```
GET  /api/v1/integrations/oauth-url/{type}       → getOAuthUrl(string $type): string
GET  /api/v1/integrations/oauth-callback/{type}  → handleCallback(string $type, array $params): Integration
```

#### Discovery & Execution
```
GET  /api/v1/integrations/metadata       → getMetadata(): array
GET  /api/v1/integrations/enabled        → enabled(): IntegrationCollection
POST /api/v1/integrations/execute        → execute(string $type, string $function, array $params): array
GET  /api/v1/integrations/ai-context     → getAiContext(): array
```

#### MCP (Model Context Protocol)
```
GET  /api/v1/mcp/integrations                   → mcpIntegrations(): array
GET  /api/v1/mcp/{integrationType}/functions    → getFunctions(string $type): array
POST /api/v1/mcp/{integrationType}/execute      → executeFunction(string $type, string $function, array $params): array
POST /api/v1/mcp/test/{integrationType}         → testService(string $type): TestResult
```

### Integration Model Properties

```php
class Integration
{
    public int $id;
    public string $type;           // 'google-drive', 'gmail', 'slack', etc.
    public string $name;
    public string $status;         // 'connected', 'disconnected', 'error'
    public ?array $capabilities;   // Available functions
    public ?array $config;
    public bool $isOAuth;
    public ?string $lastSyncedAt;
    public ?string $createdAt;

    public function isConnected(): bool;
    public function hasCapability(string $capability): bool;
}
```

### IntegrationFunction Model

```php
class IntegrationFunction
{
    public string $name;
    public string $description;
    public array $parameters;      // JSON Schema
    public ?array $returns;
    public bool $requiresAuth;
}
```

### TestResult Model

```php
class TestResult
{
    public bool $success;
    public ?string $message;
    public ?array $details;
    public ?int $latencyMs;
}
```

### Supported Integration Types

Include this constant in IntegrationsResource:

```php
public const SUPPORTED_TYPES = [
    'google-drive',
    'google-calendar',
    'gmail',
    'slack',
    'discord',
    'reddit',
    'servis-ai',
    'mailchimp',
    'mailjet',
    'case-reviewer',
    'gamma',
    'youtube-transcript',
    'youtube',
    'elevenlabs',
    'smtp-email',
    'google-gemini',
];
```

---

## 6. Task 4: RAG Resource

### Files to Create

```
src/Resources/RAG/
├── RAGResource.php
├── SearchResult.php
├── SearchResultCollection.php
├── Document.php
└── IndexResult.php
```

### API Endpoints to Cover

```
POST /api/v1/vector/store           → index(string $content, array $metadata): IndexResult
POST /api/v1/vector/search          → query(string $question, array $filters, int $topK): SearchResultCollection
GET  /api/v1/vector/{id}            → getVector(string $vectorId): Document
DELETE /api/v1/vector/{id}          → delete(string $vectorId): bool

GET|POST /api/v1/search/            → search(string $query, array $options): SearchResultCollection
GET      /api/v1/search/suggestions → suggestions(string $query): array
```

### RAGResource Implementation

```php
class RAGResource
{
    /**
     * Query knowledge base with semantic search.
     *
     * @param string $question Natural language question
     * @param array{bloq_id?: int, agent_id?: int, type?: string} $filters Metadata filters
     * @param int $topK Number of results to return
     * @return SearchResultCollection
     */
    public function query(string $question, array $filters = [], int $topK = 5): SearchResultCollection;

    /**
     * Index new content for vector search.
     *
     * @param string $content Text content to index
     * @param array{bloq_id?: int, agent_id?: int, title?: string, type?: string} $metadata
     * @return IndexResult
     */
    public function index(string $content, array $metadata = []): IndexResult;

    /**
     * Index a file (auto-extracts content).
     *
     * @param string $filePath Path to file
     * @param array $metadata Additional metadata
     * @return IndexResult
     */
    public function indexFile(string $filePath, array $metadata = []): IndexResult;

    /**
     * Search for similar documents.
     *
     * @param string $query Search query
     * @param int $limit Max results
     * @param array $filters Metadata filters
     * @return SearchResultCollection
     */
    public function searchSimilar(string $query, int $limit = 5, array $filters = []): SearchResultCollection;

    /**
     * Delete indexed content.
     *
     * @param string $vectorId Vector/document ID
     * @return bool
     */
    public function delete(string $vectorId): bool;

    /**
     * Get search suggestions.
     *
     * @param string $query Partial query
     * @return array<string> Suggestions
     */
    public function suggestions(string $query): array;
}
```

### SearchResult Model

```php
class SearchResult
{
    public string $id;
    public string $content;
    public float $score;           // Similarity score 0-1
    public array $metadata;
    public ?string $title;
    public ?string $source;

    public function isHighlyRelevant(): bool
    {
        return $this->score >= 0.8;
    }
}
```

### IndexResult Model

```php
class IndexResult
{
    public string $vectorId;
    public bool $success;
    public int $tokensUsed;
    public ?string $message;
}
```

### Document Model

```php
class Document
{
    public string $id;
    public string $content;
    public array $metadata;
    public ?array $embedding;      // Vector (usually not exposed)
    public ?string $createdAt;
}
```

---

## 7. Task 5: Laravel Service Provider

### Files to Create

```
src/Laravel/
├── IRISServiceProvider.php
├── Facades/
│   └── IRIS.php
└── config.php
```

### IRISServiceProvider.php

```php
<?php

declare(strict_types=1);

namespace IRIS\SDK\Laravel;

use Illuminate\Support\ServiceProvider;
use IRIS\SDK\IRIS;

class IRISServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config.php', 'iris');

        $this->app->singleton(IRIS::class, function ($app) {
            return new IRIS([
                'api_key' => config('iris.api_key'),
                'base_url' => config('iris.base_url'),
                'iris_url' => config('iris.iris_url'),
                'user_id' => $app['auth']->id(),
                'timeout' => config('iris.timeout', 30),
                'retries' => config('iris.retries', 3),
                'webhook_secret' => config('iris.webhook_secret'),
                'debug' => config('iris.debug', false),
            ]);
        });

        // Alias for convenience
        $this->app->alias(IRIS::class, 'iris');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/config.php' => config_path('iris.php'),
        ], 'iris-config');
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [IRIS::class, 'iris'];
    }
}
```

### Facades/IRIS.php

```php
<?php

declare(strict_types=1);

namespace IRIS\SDK\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use IRIS\SDK\IRIS as IRISClient;
use IRIS\SDK\Resources\Agents\AgentsResource;
use IRIS\SDK\Resources\Workflows\WorkflowsResource;
use IRIS\SDK\Resources\Bloqs\BloqsResource;
use IRIS\SDK\Resources\Leads\LeadsResource;
use IRIS\SDK\Resources\Integrations\IntegrationsResource;
use IRIS\SDK\Resources\RAG\RAGResource;

/**
 * @method static AgentsResource agents()
 * @method static WorkflowsResource workflows()
 * @method static BloqsResource bloqs()
 * @method static LeadsResource leads()
 * @method static IntegrationsResource integrations()
 * @method static RAGResource rag()
 * @method static IRISClient asUser(int $userId)
 * @method static bool testConnection()
 * @method static array account()
 * @method static array usage()
 *
 * @see \IRIS\SDK\IRIS
 */
class IRIS extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return IRISClient::class;
    }
}
```

### config.php

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | IRIS API Key
    |--------------------------------------------------------------------------
    |
    | Your IRIS API key. Get this from your IRIS dashboard.
    |
    */
    'api_key' => env('IRIS_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | API Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the IRIS API.
    |
    */
    'base_url' => env('IRIS_API_URL', 'https://api.iris.ai'),

    /*
    |--------------------------------------------------------------------------
    | IRIS Workflows URL
    |--------------------------------------------------------------------------
    |
    | The base URL for IRIS V5 workflow execution.
    |
    */
    'iris_url' => env('IRIS_WORKFLOWS_URL', 'https://workflows.iris.ai'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout in seconds for API requests.
    |
    */
    'timeout' => env('IRIS_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Retry Attempts
    |--------------------------------------------------------------------------
    |
    | Number of times to retry failed requests.
    |
    */
    'retries' => env('IRIS_RETRIES', 3),

    /*
    |--------------------------------------------------------------------------
    | Webhook Secret
    |--------------------------------------------------------------------------
    |
    | Secret for verifying incoming webhooks.
    |
    */
    'webhook_secret' => env('IRIS_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | Enable debug logging for API requests.
    |
    */
    'debug' => env('IRIS_DEBUG', false),
];
```

---

## 8. Task 6: PHPUnit Tests

### Setup Files

```
tests/
├── TestCase.php
├── Unit/
│   ├── ConfigTest.php
│   ├── Resources/
│   │   ├── AgentsResourceTest.php
│   │   ├── WorkflowsResourceTest.php
│   │   ├── BloqsResourceTest.php
│   │   ├── LeadsResourceTest.php
│   │   ├── IntegrationsResourceTest.php
│   │   └── RAGResourceTest.php
│   └── Models/
│       ├── AgentTest.php
│       ├── WorkflowRunTest.php
│       └── LeadTest.php
├── Feature/
│   └── IRISClientTest.php
└── Mocks/
    └── MockHttpClient.php
```

### phpunit.xml

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         testdox="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    <coverage>
        <include>
            <directory suffix=".php">src</directory>
        </include>
    </coverage>
</phpunit>
```

### TestCase.php

```php
<?php

declare(strict_types=1);

namespace IRIS\SDK\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use IRIS\SDK\IRIS;
use IRIS\SDK\Tests\Mocks\MockHttpClient;

abstract class TestCase extends BaseTestCase
{
    protected MockHttpClient $mockHttp;
    protected IRIS $iris;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockHttp = new MockHttpClient();
        $this->iris = $this->createClient();
    }

    protected function createClient(array $options = []): IRIS
    {
        return new IRIS(array_merge([
            'api_key' => 'test_api_key',
            'user_id' => 123,
        ], $options));
    }

    protected function mockResponse(string $method, string $endpoint, array $response, int $status = 200): void
    {
        $this->mockHttp->addResponse($method, $endpoint, $response, $status);
    }
}
```

### MockHttpClient.php

```php
<?php

declare(strict_types=1);

namespace IRIS\SDK\Tests\Mocks;

use IRIS\SDK\Http\Client;
use IRIS\SDK\Config;
use IRIS\SDK\Exceptions\IRISException;

class MockHttpClient extends Client
{
    protected array $responses = [];
    protected array $requests = [];

    public function __construct()
    {
        // Don't call parent - we're mocking everything
    }

    public function addResponse(string $method, string $endpoint, array $response, int $status = 200): void
    {
        $key = strtoupper($method) . ':' . $endpoint;
        $this->responses[$key] = [
            'body' => $response,
            'status' => $status,
        ];
    }

    public function get(string $endpoint, array $query = []): array
    {
        return $this->mockRequest('GET', $endpoint, $query);
    }

    public function post(string $endpoint, array $data = []): array
    {
        return $this->mockRequest('POST', $endpoint, $data);
    }

    public function put(string $endpoint, array $data = []): array
    {
        return $this->mockRequest('PUT', $endpoint, $data);
    }

    public function delete(string $endpoint): array
    {
        return $this->mockRequest('DELETE', $endpoint);
    }

    protected function mockRequest(string $method, string $endpoint, array $data = []): array
    {
        $this->requests[] = [
            'method' => $method,
            'endpoint' => $endpoint,
            'data' => $data,
        ];

        $key = strtoupper($method) . ':' . $endpoint;

        if (!isset($this->responses[$key])) {
            throw new IRISException("No mock response for: {$key}");
        }

        $response = $this->responses[$key];

        if ($response['status'] >= 400) {
            throw new IRISException($response['body']['message'] ?? 'Error', $response['status']);
        }

        return $response['body'];
    }

    public function getRequests(): array
    {
        return $this->requests;
    }

    public function getLastRequest(): ?array
    {
        return $this->requests[count($this->requests) - 1] ?? null;
    }

    public function assertRequestMade(string $method, string $endpoint): void
    {
        foreach ($this->requests as $request) {
            if ($request['method'] === $method && str_contains($request['endpoint'], $endpoint)) {
                return;
            }
        }

        throw new \PHPUnit\Framework\AssertionFailedError(
            "Expected request {$method} {$endpoint} was not made"
        );
    }
}
```

### Example Test: AgentsResourceTest.php

```php
<?php

declare(strict_types=1);

namespace IRIS\SDK\Tests\Unit\Resources;

use IRIS\SDK\Tests\TestCase;
use IRIS\SDK\Resources\Agents\Agent;
use IRIS\SDK\Resources\Agents\AgentCollection;
use IRIS\SDK\Resources\Agents\AgentConfig;
use IRIS\SDK\Resources\Agents\ChatResponse;

class AgentsResourceTest extends TestCase
{
    public function test_list_agents(): void
    {
        $this->mockResponse('GET', '/api/v1/users/123/bloqs/agents', [
            'data' => [
                ['id' => 1, 'name' => 'Agent 1'],
                ['id' => 2, 'name' => 'Agent 2'],
            ],
            'meta' => ['total' => 2],
        ]);

        $agents = $this->iris->agents->list();

        $this->assertInstanceOf(AgentCollection::class, $agents);
        $this->assertCount(2, $agents);
        $this->assertEquals('Agent 1', $agents->first()->name);
    }

    public function test_get_agent(): void
    {
        $this->mockResponse('GET', '/api/v1/users/123/bloqs/agents/456', [
            'id' => 456,
            'name' => 'Test Agent',
            'model' => 'gpt-4o-mini',
        ]);

        $agent = $this->iris->agents->get(456);

        $this->assertInstanceOf(Agent::class, $agent);
        $this->assertEquals(456, $agent->id);
        $this->assertEquals('Test Agent', $agent->name);
    }

    public function test_create_agent(): void
    {
        $this->mockResponse('POST', '/api/v1/users/123/bloqs/agents', [
            'id' => 789,
            'name' => 'New Agent',
            'prompt' => 'You are helpful.',
        ]);

        $config = new AgentConfig(
            name: 'New Agent',
            prompt: 'You are helpful.',
        );

        $agent = $this->iris->agents->create($config);

        $this->assertEquals(789, $agent->id);
        $this->assertEquals('New Agent', $agent->name);
    }

    public function test_chat_with_agent(): void
    {
        $this->mockResponse('POST', '/v1/bloqs/agents/generate-response', [
            'content' => 'Hello! How can I help?',
            'model' => 'gpt-4o-mini',
            'usage' => [
                'prompt_tokens' => 10,
                'completion_tokens' => 20,
            ],
        ]);

        $response = $this->iris->agents->chat('agent_123', [
            ['role' => 'user', 'content' => 'Hello'],
        ]);

        $this->assertInstanceOf(ChatResponse::class, $response);
        $this->assertEquals('Hello! How can I help?', $response->content);
        $this->assertEquals(10, $response->promptTokens);
    }

    public function test_delete_agent(): void
    {
        $this->mockResponse('DELETE', '/api/v1/users/123/bloqs/agents/456', [
            'success' => true,
        ]);

        $result = $this->iris->agents->delete(456);

        $this->assertTrue($result);
    }
}
```

### Test Coverage Goals

| Resource | Tests | Coverage |
|----------|-------|----------|
| Config | 5 | Constructor, validation, headers |
| IRIS Client | 5 | Init, resources, asUser, testConnection |
| AgentsResource | 10 | CRUD, chat, multiStep, webhooks |
| WorkflowsResource | 12 | execute, polling, humanTask, templates |
| BloqsResource | 10 | CRUD, lists, items, files |
| LeadsResource | 10 | CRUD, activities, tasks, AI |
| IntegrationsResource | 8 | list, OAuth, execute, MCP |
| RAGResource | 6 | query, index, delete |
| **Total** | **~66 tests** | |

---

## 9. Code Style Requirements

### PHP CS Fixer Rules

The project uses `friendsofphp/php-cs-fixer`. Key rules:

- PSR-12 coding standard
- Strict types declaration required
- Array syntax: short (`[]` not `array()`)
- No unused imports
- Single blank line before namespace
- Ordered imports (alphabetical)

### Run Before Committing

```bash
composer cs-fix
composer analyse  # PHPStan level 8
composer test
```

### Naming Conventions

- **Classes**: PascalCase (`AgentsResource`, `LeadCollection`)
- **Methods**: camelCase (`getById`, `createAgent`)
- **Properties**: camelCase (`$apiKey`, `$userId`)
- **Constants**: SCREAMING_SNAKE_CASE (`VERSION`, `SUPPORTED_TYPES`)

### Documentation

Every public method needs:
- PHPDoc with `@param` and `@return`
- Description of what the method does
- Example in doc block for complex methods

```php
/**
 * Execute a workflow with the given parameters.
 *
 * @param array{
 *     agent_id?: int,
 *     query: string,
 *     bloq_id?: int
 * } $params Workflow parameters
 * @return WorkflowRun The executing workflow
 *
 * @example
 * ```php
 * $run = $iris->workflows->execute([
 *     'agent_id' => 123,
 *     'query' => 'Research competitors',
 * ]);
 * ```
 */
public function execute(array $params): WorkflowRun
```

---

## Acceptance Criteria

### For Resources (Tasks 1-4)

- [ ] All API endpoints covered
- [ ] Models have typed properties
- [ ] Collections implement `Countable` and `IteratorAggregate`
- [ ] Sub-resources follow established pattern
- [ ] PHPDoc on all public methods
- [ ] No PHPStan errors at level 8

### For Laravel Provider (Task 5)

- [ ] Service provider registers singleton
- [ ] Facade works with IDE autocomplete
- [ ] Config file is publishable
- [ ] Works with Laravel 9, 10, 11

### For Tests (Task 6)

- [ ] 60+ tests minimum
- [ ] All resources have unit tests
- [ ] Mock client works for isolation
- [ ] Tests pass with `composer test`
- [ ] Coverage report generates

---

## Questions?

If you need clarification on any endpoint's expected behavior, refer to:

1. **API Routes**: `fl-docker-dev/fl-api/routes/api.php`
2. **Controllers**: `fl-docker-dev/fl-api/app/Http/Controllers/`
3. **Existing SDK Code**: `fl-docker-dev/sdk/php/src/Resources/Agents/` (as pattern reference)

The completed implementations in `Agents/` and `Workflows/` are your primary reference for patterns and style.
