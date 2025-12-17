<?php

declare(strict_types=1);

namespace IRIS\SDK\Resources\Agents;

use IRIS\SDK\Config;
use IRIS\SDK\Http\Client;
use IRIS\SDK\Resources\Workflows\WorkflowRun;

/**
 * Agents Resource
 *
 * Manage AI agents - create, configure, and chat with intelligent assistants.
 *
 * @example
 * ```php
 * // Create an agent
 * $agent = $fl->agents->create(new AgentConfig(
 *     name: 'Marketing Assistant',
 *     prompt: 'You are a helpful marketing assistant.',
 *     model: 'gpt-4o-mini',
 * ));
 *
 * // Chat with an agent
 * $response = $fl->agents->chat($agent->id, [
 *     ['role' => 'user', 'content' => 'Draft a tweet about our new product']
 * ]);
 * ```
 */
class AgentsResource
{
    protected Client $http;
    protected Config $config;

    public function __construct(Client $http, Config $config)
    {
        $this->http = $http;
        $this->config = $config;
    }

    /**
     * List all agents for the current user.
     *
     * @param array{
     *     page?: int,
     *     per_page?: int,
     *     search?: string,
     *     type?: string
     * } $options List options
     * @return AgentCollection
     */
    public function list(array $options = []): AgentCollection
    {
        $userId = $this->config->requireUserId();
        $response = $this->http->get("/api/v1/users/{$userId}/bloqs/agents", $options);

        return new AgentCollection(
            array_map(fn($data) => new Agent($data), $response['data'] ?? $response),
            $response['meta'] ?? []
        );
    }

    /**
     * Get a specific agent by ID.
     *
     * @param int|string $agentId Agent ID
     * @return Agent
     */
    public function get(int|string $agentId): Agent
    {
        $userId = $this->config->requireUserId();
        $response = $this->http->get("/api/v1/users/{$userId}/bloqs/agents/{$agentId}");

        return new Agent($response);
    }

    /**
     * Create a new agent.
     *
     * @param AgentConfig $config Agent configuration
     * @return Agent
     */
    public function create(AgentConfig $config): Agent
    {
        $userId = $this->config->requireUserId();
        $response = $this->http->post(
            "/api/v1/users/{$userId}/bloqs/agents",
            $config->toArray()
        );

        return new Agent($response);
    }

    /**
     * Update an existing agent.
     *
     * @param int|string $agentId Agent ID
     * @param array $data Update data
     * @return Agent
     */
    public function update(int|string $agentId, array $data): Agent
    {
        $userId = $this->config->requireUserId();
        $response = $this->http->put(
            "/api/v1/users/{$userId}/bloqs/agents/{$agentId}",
            $data
        );

        return new Agent($response);
    }

    /**
     * Delete an agent.
     *
     * @param int|string $agentId Agent ID
     * @return bool
     */
    public function delete(int|string $agentId): bool
    {
        $userId = $this->config->requireUserId();
        $this->http->delete("/api/v1/users/{$userId}/bloqs/agents/{$agentId}");
        return true;
    }

    /**
     * Chat with an agent (single turn).
     *
     * @param int|string $agentId Agent ID
     * @param array<array{role: string, content: string}> $messages Chat messages
     * @param array{
     *     bloq_id?: int,
     *     thread_id?: string,
     *     use_rag?: bool,
     *     model?: string
     * } $options Chat options
     * @return ChatResponse
     */
    public function chat(int|string $agentId, array $messages, array $options = []): ChatResponse
    {
        $response = $this->http->post('/api/v1/bloqs/agents/generate-response', [
            'agent_id' => $agentId,
            'messages' => $messages,
            'bloq_id' => $options['bloq_id'] ?? null,
            'thread_id' => $options['thread_id'] ?? null,
            'use_rag' => $options['use_rag'] ?? true,
            'model' => $options['model'] ?? null,
        ]);

        return new ChatResponse($response);
    }

    /**
     * Multi-step agent conversation (V5 workflow).
     *
     * Executes a complex query that may involve multiple steps,
     * tool usage, and human-in-the-loop approval.
     *
     * @param int|string $agentId Agent ID
     * @param string $query The user's query/request
     * @param array{
     *     bloq_id?: int,
     *     conversation_history?: array,
     *     require_approval?: bool,
     *     metadata?: array
     * } $options Workflow options
     * @return WorkflowRun
     */
    public function multiStep(int|string $agentId, string $query, array $options = []): WorkflowRun
    {
        $response = $this->http->post('/api/v1/bloqs/agents/multi-step-response', [
            'agent_id' => $agentId,
            'query' => $query,
            'bloq_id' => $options['bloq_id'] ?? null,
            'conversation_history' => $options['conversation_history'] ?? [],
            'require_approval' => $options['require_approval'] ?? false,
            'metadata' => $options['metadata'] ?? [],
        ]);

        return new WorkflowRun($response, $this->http, $this->config);
    }

    /**
     * Add a file to the agent's memory (knowledge base).
     *
     * @param int|string $agentId Agent ID
     * @param string $filePath Path to the file
     * @param array{
     *     title?: string,
     *     description?: string,
     *     tags?: array
     * } $metadata File metadata
     * @return bool
     */
    public function addMemory(int|string $agentId, string $filePath, array $metadata = []): bool
    {
        $this->http->upload(
            "/api/v1/bloqs/agents/{$agentId}/add-memory",
            $filePath,
            $metadata
        );

        return true;
    }

    /**
     * Toggle public access for an agent.
     *
     * @param int|string $agentId Agent ID
     * @param bool $isPublic Whether the agent should be public
     * @return Agent
     */
    public function togglePublic(int|string $agentId, bool $isPublic): Agent
    {
        $userId = $this->config->requireUserId();
        $response = $this->http->post(
            "/api/v1/users/{$userId}/bloqs/agents/{$agentId}/public/toggle",
            ['is_public' => $isPublic]
        );

        return new Agent($response);
    }

    /**
     * Get analytics for a public agent.
     *
     * @param int|string $agentId Agent ID
     * @return array Analytics data
     */
    public function getAnalytics(int|string $agentId): array
    {
        $userId = $this->config->requireUserId();
        return $this->http->get("/api/v1/users/{$userId}/bloqs/agents/{$agentId}/public/analytics");
    }

    /**
     * Generate a webhook URL for the agent.
     *
     * @param int|string $agentId Agent ID
     * @return array{url: string, secret: string}
     */
    public function generateWebhook(int|string $agentId): array
    {
        return $this->http->post("/api/v1/bloqs/agents/{$agentId}/webhook/generate");
    }

    /**
     * Get webhook settings for an agent.
     *
     * @param int|string $agentId Agent ID
     * @return array Webhook settings
     */
    public function getWebhook(int|string $agentId): array
    {
        return $this->http->get("/api/v1/bloqs/agents/{$agentId}/webhook");
    }

    /**
     * Discover MCP tools available for an agent.
     *
     * @param int|string $agentId Agent ID
     * @return array Available MCP tools
     */
    public function discoverTools(int|string $agentId): array
    {
        return $this->http->post("/api/v1/agents/{$agentId}/mcp/discover-tools");
    }

    /**
     * Get all public agents.
     *
     * @param array{
     *     search?: string,
     *     category?: string,
     *     page?: int,
     *     per_page?: int
     * } $options Search options
     * @return AgentCollection
     */
    public function listPublic(array $options = []): AgentCollection
    {
        $response = $this->http->get('/api/v1/public/agents', $options);

        return new AgentCollection(
            array_map(fn($data) => new Agent($data), $response['data'] ?? $response),
            $response['meta'] ?? []
        );
    }

    /**
     * Chat with a public agent by slug (no authentication required).
     *
     * @param string $slug Agent's public slug
     * @param array<array{role: string, content: string}> $messages Chat messages
     * @return ChatResponse
     */
    public function chatPublic(string $slug, array $messages): ChatResponse
    {
        $response = $this->http->post("/api/v1/public/agents/{$slug}/chat", [
            'messages' => $messages,
        ]);

        return new ChatResponse($response);
    }
}
