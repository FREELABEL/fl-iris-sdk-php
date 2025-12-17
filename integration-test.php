<?php
/**
 * IRIS SDK Integration Test Script
 *
 * Tests the SDK against a real API (local or production).
 *
 * Usage:
 *   # Test against local
 *   php integration-test.php local
 *
 *   # Test against production
 *   php integration-test.php production
 *
 *   # Test specific resource
 *   php integration-test.php local agents
 *   php integration-test.php production workflows
 */

require_once __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;
use IRIS\SDK\Resources\Agents\AgentConfig;
use IRIS\SDK\Exceptions\IRISException;
use IRIS\SDK\Exceptions\AuthenticationException;

// =============================================================================
// Configuration
// =============================================================================

$environments = [
    'local' => [
        'base_url' => 'http://localhost:8000',
        'iris_url' => 'http://localhost:8001',  // LangGraph/IRIS API
        'api_key' => getenv('IRIS_LOCAL_API_KEY') ?: 'YOUR_LOCAL_API_KEY',
        'user_id' => (int)(getenv('IRIS_LOCAL_USER_ID') ?: 1),
    ],
    'production' => [
        'base_url' => 'https://api.freelabel.net',
        'iris_url' => 'https://iris.freelabel.net',
        'api_key' => getenv('IRIS_PROD_API_KEY') ?: 'YOUR_PROD_API_KEY',
        'user_id' => (int)(getenv('IRIS_PROD_USER_ID') ?: 1),
    ],
];

// =============================================================================
// Test Runner
// =============================================================================

class IntegrationTestRunner
{
    private IRIS $iris;
    private string $env;
    private array $results = [];
    private int $passed = 0;
    private int $failed = 0;

    public function __construct(array $config, string $env)
    {
        $this->env = $env;
        $this->iris = new IRIS([
            'api_key' => $config['api_key'],
            'base_url' => $config['base_url'],
            'iris_url' => $config['iris_url'],
            'user_id' => $config['user_id'],
            'timeout' => 30,
            'debug' => true,
        ]);

        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║           IRIS PHP SDK - Integration Tests                   ║\n";
        echo "╠══════════════════════════════════════════════════════════════╣\n";
        echo "║  Environment: " . str_pad(strtoupper($env), 46) . "║\n";
        echo "║  Base URL:    " . str_pad($config['base_url'], 46) . "║\n";
        echo "║  User ID:     " . str_pad((string)$config['user_id'], 46) . "║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n";
        echo "\n";
    }

    public function run(?string $filter = null): void
    {
        $tests = [
            'connection' => [$this, 'testConnection'],
            'agents' => [$this, 'testAgents'],
            'bloqs' => [$this, 'testBloqs'],
            'leads' => [$this, 'testLeads'],
            'integrations' => [$this, 'testIntegrations'],
            // 'workflows' => [$this, 'testWorkflows'],  // Requires more setup
            // 'rag' => [$this, 'testRAG'],  // Requires Pinecone
        ];

        foreach ($tests as $name => $callback) {
            if ($filter && $filter !== $name) {
                continue;
            }

            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            echo "Testing: " . strtoupper($name) . "\n";
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

            try {
                $callback();
            } catch (\Throwable $e) {
                $this->fail($name, $e->getMessage());
            }

            echo "\n";
        }

        $this->printSummary();
    }

    // =========================================================================
    // Test: Connection
    // =========================================================================

    private function testConnection(): void
    {
        $this->test('API Health Check', function () {
            // Try to get user account (requires auth)
            $account = $this->iris->account();
            return isset($account['id']) || isset($account['data']);
        });
    }

    // =========================================================================
    // Test: Agents
    // =========================================================================

    private function testAgents(): void
    {
        $createdAgentId = null;

        $this->test('List Agents', function () {
            $agents = $this->iris->agents->list();
            echo "    Found {$agents->count()} agents\n";
            return true;
        });

        $this->test('Create Agent', function () use (&$createdAgentId) {
            $config = new AgentConfig(
                name: 'SDK Test Agent ' . date('H:i:s'),
                prompt: 'You are a test agent created by the IRIS PHP SDK integration tests.',
                model: 'gpt-4o-mini',
            );

            $agent = $this->iris->agents->create($config);
            $createdAgentId = $agent->id;

            echo "    Created agent ID: {$agent->id}\n";
            echo "    Name: {$agent->name}\n";

            return $agent->id > 0;
        });

        $this->test('Get Agent', function () use (&$createdAgentId) {
            if (!$createdAgentId) {
                echo "    Skipped (no agent created)\n";
                return true;
            }

            $agent = $this->iris->agents->get($createdAgentId);
            echo "    Retrieved: {$agent->name}\n";

            return $agent->id === $createdAgentId;
        });

        $this->test('Chat with Agent', function () use (&$createdAgentId) {
            if (!$createdAgentId) {
                echo "    Skipped (no agent created)\n";
                return true;
            }

            $response = $this->iris->agents->chat($createdAgentId, [
                ['role' => 'user', 'content' => 'Say hello in exactly 3 words.']
            ]);

            echo "    Response: " . substr($response->content, 0, 100) . "...\n";
            echo "    Tokens: {$response->totalTokens}\n";

            return strlen($response->content) > 0;
        });

        $this->test('Update Agent', function () use (&$createdAgentId) {
            if (!$createdAgentId) {
                echo "    Skipped (no agent created)\n";
                return true;
            }

            $agent = $this->iris->agents->update($createdAgentId, [
                'name' => 'SDK Test Agent (Updated)',
            ]);

            echo "    Updated name: {$agent->name}\n";

            return str_contains($agent->name, 'Updated');
        });

        $this->test('Delete Agent', function () use (&$createdAgentId) {
            if (!$createdAgentId) {
                echo "    Skipped (no agent created)\n";
                return true;
            }

            $result = $this->iris->agents->delete($createdAgentId);
            echo "    Deleted: " . ($result ? 'Yes' : 'No') . "\n";

            return $result;
        });
    }

    // =========================================================================
    // Test: Bloqs
    // =========================================================================

    private function testBloqs(): void
    {
        $createdBloqId = null;
        $createdListId = null;

        $this->test('List Bloqs', function () {
            $bloqs = $this->iris->bloqs->list();
            echo "    Found {$bloqs->count()} bloqs\n";
            return true;
        });

        $this->test('Create Bloq', function () use (&$createdBloqId) {
            $bloq = $this->iris->bloqs->create('SDK Test Bloq ' . date('H:i:s'), [
                'description' => 'Created by IRIS PHP SDK integration tests',
            ]);

            $createdBloqId = $bloq->id;
            echo "    Created bloq ID: {$bloq->id}\n";

            return $bloq->id > 0;
        });

        $this->test('Get Bloq', function () use (&$createdBloqId) {
            if (!$createdBloqId) {
                echo "    Skipped\n";
                return true;
            }

            $bloq = $this->iris->bloqs->get($createdBloqId);
            echo "    Title: {$bloq->title}\n";

            return $bloq->id === $createdBloqId;
        });

        $this->test('Create List in Bloq', function () use (&$createdBloqId, &$createdListId) {
            if (!$createdBloqId) {
                echo "    Skipped\n";
                return true;
            }

            $list = $this->iris->bloqs->lists($createdBloqId)->create([
                'title' => 'Test List',
                'type' => 'checklist',
            ]);

            $createdListId = $list->id;
            echo "    Created list ID: {$list->id}\n";

            return $list->id > 0;
        });

        $this->test('Create Item in List', function () use (&$createdListId) {
            if (!$createdListId) {
                echo "    Skipped\n";
                return true;
            }

            $item = $this->iris->bloqs->items($createdListId)->create([
                'title' => 'Test Item',
                'content' => 'Created by SDK test',
            ]);

            echo "    Created item ID: {$item->id}\n";

            return $item->id > 0;
        });

        $this->test('Delete Bloq', function () use (&$createdBloqId) {
            if (!$createdBloqId) {
                echo "    Skipped\n";
                return true;
            }

            $result = $this->iris->bloqs->delete($createdBloqId);
            echo "    Deleted: " . ($result ? 'Yes' : 'No') . "\n";

            return $result;
        });
    }

    // =========================================================================
    // Test: Leads
    // =========================================================================

    private function testLeads(): void
    {
        $createdLeadId = null;

        $this->test('List Leads', function () {
            $leads = $this->iris->leads->list();
            echo "    Found {$leads->count()} leads\n";
            return true;
        });

        $this->test('Create Lead', function () use (&$createdLeadId) {
            $lead = $this->iris->leads->create([
                'name' => 'SDK Test Lead',
                'email' => 'sdk-test-' . time() . '@example.com',
                'company' => 'Test Company',
            ]);

            $createdLeadId = $lead->id;
            echo "    Created lead ID: {$lead->id}\n";
            echo "    Name: {$lead->name}\n";

            return $lead->id > 0;
        });

        $this->test('Get Lead', function () use (&$createdLeadId) {
            if (!$createdLeadId) {
                echo "    Skipped\n";
                return true;
            }

            $lead = $this->iris->leads->get($createdLeadId);
            echo "    Email: {$lead->email}\n";

            return $lead->id === $createdLeadId;
        });

        $this->test('Add Note to Lead', function () use (&$createdLeadId) {
            if (!$createdLeadId) {
                echo "    Skipped\n";
                return true;
            }

            $result = $this->iris->leads->addNote($createdLeadId, 'Test note from SDK');
            echo "    Note added\n";

            return true;
        });

        $this->test('Delete Lead', function () use (&$createdLeadId) {
            if (!$createdLeadId) {
                echo "    Skipped\n";
                return true;
            }

            $result = $this->iris->leads->delete($createdLeadId);
            echo "    Deleted: " . ($result ? 'Yes' : 'No') . "\n";

            return $result;
        });
    }

    // =========================================================================
    // Test: Integrations
    // =========================================================================

    private function testIntegrations(): void
    {
        $this->test('List Available Integrations', function () {
            $integrations = $this->iris->integrations->available();
            echo "    Found {$integrations->count()} integrations\n";

            foreach ($integrations->all() as $integration) {
                echo "    - {$integration->type}: {$integration->name}\n";
            }

            return true;
        });

        $this->test('Get Integration Types', function () {
            $types = $this->iris->integrations->types();
            echo "    Types: " . count($types) . "\n";
            return true;
        });

        $this->test('List Connected Integrations', function () {
            $connected = $this->iris->integrations->enabled();
            echo "    Connected: {$connected->count()}\n";
            return true;
        });
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function test(string $name, callable $callback): void
    {
        echo "\n  ▸ {$name}... ";

        try {
            $result = $callback();

            if ($result) {
                echo "✅ PASS\n";
                $this->passed++;
            } else {
                echo "❌ FAIL\n";
                $this->failed++;
            }
        } catch (AuthenticationException $e) {
            echo "❌ AUTH ERROR: {$e->getMessage()}\n";
            $this->failed++;
        } catch (IRISException $e) {
            echo "❌ API ERROR: {$e->getMessage()}\n";
            $this->failed++;
        } catch (\Throwable $e) {
            echo "❌ ERROR: {$e->getMessage()}\n";
            $this->failed++;
        }
    }

    private function fail(string $name, string $message): void
    {
        echo "  ❌ {$name} FAILED: {$message}\n";
        $this->failed++;
    }

    private function printSummary(): void
    {
        $total = $this->passed + $this->failed;

        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║                        TEST SUMMARY                          ║\n";
        echo "╠══════════════════════════════════════════════════════════════╣\n";
        echo "║  Total:  " . str_pad((string)$total, 51) . "║\n";
        echo "║  Passed: " . str_pad((string)$this->passed, 51) . "║\n";
        echo "║  Failed: " . str_pad((string)$this->failed, 51) . "║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n";

        if ($this->failed > 0) {
            echo "\n⚠️  Some tests failed. Check the output above for details.\n";
            exit(1);
        } else {
            echo "\n✅ All tests passed!\n";
            exit(0);
        }
    }
}

// =============================================================================
// Main
// =============================================================================

// Parse arguments
$env = $argv[1] ?? 'local';
$filter = $argv[2] ?? null;

if (!isset($environments[$env])) {
    echo "Unknown environment: {$env}\n";
    echo "Usage: php integration-test.php [local|production] [agents|bloqs|leads|integrations]\n";
    exit(1);
}

// Check for API key
$config = $environments[$env];
if (str_contains($config['api_key'], 'YOUR_')) {
    echo "\n";
    echo "⚠️  API key not configured!\n";
    echo "\n";
    echo "Set environment variables:\n";
    echo "  export IRIS_LOCAL_API_KEY='your-local-api-key'\n";
    echo "  export IRIS_LOCAL_USER_ID='your-user-id'\n";
    echo "\n";
    echo "Or for production:\n";
    echo "  export IRIS_PROD_API_KEY='your-prod-api-key'\n";
    echo "  export IRIS_PROD_USER_ID='your-user-id'\n";
    echo "\n";
    exit(1);
}

// Run tests
$runner = new IntegrationTestRunner($config, $env);
$runner->run($filter);
