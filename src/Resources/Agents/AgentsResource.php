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
     * Create a new agent from array (simplified API).
     * 
     * This method accepts a simple array and handles AgentConfig creation internally.
     * Perfect for CLI usage and quick agent creation.
     *
     * @param array $data Agent data
     * @return Agent
     * 
     * @example Create from simple array
     * ```php
     * $agent = $iris->agents->createFromArray([
     *     'name' => 'News Scout',
     *     'initial_prompt' => 'You are a helpful assistant',
     *     'bloq_id' => 40,
     *     'config' => ['model_id' => 185, 'temperature' => 0.7]
     * ]);
     * ```
     */
    public function createFromArray(array $data): Agent
    {
        $userId = $this->config->requireUserId();
        
        // Add type if not provided
        if (!isset($data['type'])) {
            $data['type'] = 'ai_bloq';
        }
        
        $response = $this->http->post(
            "/api/v1/users/{$userId}/bloqs/agents",
            $data
        );

        return new Agent($response);
    }

    /**
     * Update an existing agent (full replacement).
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
     * Partially update an agent (only specified fields).
     * 
     * This method fetches the current agent, merges your changes,
     * and updates. Perfect for updating just one field without
     * overwriting everything else.
     *
     * @param int|string $agentId Agent ID
     * @param array $data Fields to update (e.g., ['initial_prompt' => '...'])
     * @return Agent
     * 
     * @example Update just the prompt
     * ```php
     * $agent = $iris->agents->patch(356, [
     *     'initial_prompt' => 'New instructions...'
     * ]);
     * ```
     */
    public function patch(int|string $agentId, array $data): Agent
    {
        // Get current agent data
        $current = $this->get($agentId);
        
        // Merge with new data (new data overwrites current)
        $merged = array_merge($current->toArray(), $data);
        
        // Update with merged data
        return $this->update($agentId, $merged);
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
     * Call an integration directly (Pattern 1 - Manual Execution).
     *
     * Execute a specific integration action without LLM planning.
     * Useful for automation, scripting, and programmatic access.
     *
     * @example
     * ```php
     * // Send email via Gmail integration
     * $result = $fl->agents->callIntegration(11, 'gmail', 'send', [
     *     'to' => 'john@example.com',
     *     'subject' => 'Meeting Reminder',
     *     'body' => 'Tomorrow at 2pm'
     * ]);
     *
     * // Post to Slack
     * $result = $fl->agents->callIntegration(11, 'slack', 'post', [
     *     'channel' => '#general',
     *     'message' => 'Deployment complete!'
     * ]);
     * ```
     *
     * @param int|string $agentId Agent ID
     * @param string $integration Integration name (gmail, slack, google-calendar, etc.)
     * @param string $action Action to perform (send, post, create, etc.)
     * @param array $params Action parameters
     * @return array Integration execution result
     */
    public function callIntegration(int|string $agentId, string $integration, string $action, array $params = []): array
    {
        $userId = $this->config->requireUserId();
        return $this->http->post("/api/v1/users/{$userId}/bloqs/agents/{$agentId}/call-integration", [
            'integration' => $integration,
            'action' => $action,
            'params' => $params,
        ]);
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

    /**
     * Get the current file attachments for an agent.
     *
     * @param int|string $agentId Agent ID
     * @return array Current file attachments
     */
    public function getFileAttachments(int|string $agentId): array
    {
        $agent = $this->get($agentId);
        return $agent->fileAttachments ?? [];
    }

    /**
     * Add file attachments to an agent.
     *
     * This method adds files to the agent's existing attachments without
     * removing any current ones. The files should be in the format returned
     * by CloudFilesResource::uploadForAgent().
     *
     * @param int|string $agentId Agent ID
     * @param array $attachments Array of file attachment data
     * @return Agent Updated agent
     *
     * @example
     * ```php
     * // Upload a file and attach it to an agent
     * $attachment = $iris->cloudFiles->uploadForAgent('/path/to/data.csv', 40);
     * $agent = $iris->agents->addFileAttachments(335, [$attachment]);
     *
     * // Or upload and attach in one call
     * $agent = $iris->agents->uploadAndAttachFiles(335, ['/path/to/file.csv'], 40);
     * ```
     */
    public function addFileAttachments(int|string $agentId, array $attachments): Agent
    {
        // Get current agent with its file attachments
        $agent = $this->get($agentId);
        $currentAttachments = $agent->fileAttachments ?? [];

        // Merge with new attachments
        $allAttachments = array_merge($currentAttachments, $attachments);

        // Update agent with new attachments
        return $this->patch($agentId, [
            'fileAttachments' => $allAttachments,
        ]);
    }

    /**
     * Replace all file attachments on an agent.
     *
     * This method replaces ALL file attachments with the new ones.
     * Use addFileAttachments() to add without removing existing.
     *
     * @param int|string $agentId Agent ID
     * @param array $attachments Array of file attachment data
     * @return Agent Updated agent
     */
    public function setFileAttachments(int|string $agentId, array $attachments): Agent
    {
        return $this->patch($agentId, [
            'fileAttachments' => $attachments,
        ]);
    }

    /**
     * Remove a file attachment from an agent.
     *
     * @param int|string $agentId Agent ID
     * @param int $cloudFileId Cloud file ID to remove
     * @return Agent Updated agent
     */
    public function removeFileAttachment(int|string $agentId, int $cloudFileId): Agent
    {
        $agent = $this->get($agentId);
        $currentAttachments = $agent->fileAttachments ?? [];

        // Filter out the attachment
        $filtered = array_values(array_filter(
            $currentAttachments,
            fn($a) => ($a['cloud_file_id'] ?? 0) !== $cloudFileId
        ));

        return $this->patch($agentId, [
            'fileAttachments' => $filtered,
        ]);
    }

    /**
     * Clear all file attachments from an agent.
     *
     * @param int|string $agentId Agent ID
     * @return Agent Updated agent
     */
    public function clearFileAttachments(int|string $agentId): Agent
    {
        return $this->patch($agentId, [
            'fileAttachments' => [],
        ]);
    }

    /**
     * Get the public/shareable URL for an agent.
     *
     * Returns URLs in the format: https://app.heyiris.io/agent/simple/{id}?bloq={bloqId}
     *
     * @param int|string $agentId Agent ID
     * @param string $baseUrl Base URL (default: https://app.heyiris.io)
     * @return array{simple: string, embed: string, public: ?string}
     *
     * @example
     * ```php
     * // Get all URLs for an agent
     * $urls = $iris->agents->getUrls(11);
     * echo $urls['simple'];  // https://app.heyiris.io/agent/simple/11?bloq=40
     * echo $urls['embed'];   // Same as simple
     * echo $urls['public'];  // https://app.heyiris.io/agent/my-agent-slug (if public)
     * ```
     */
    public function getUrls(int|string $agentId, string $baseUrl = 'https://app.heyiris.io'): array
    {
        $agent = $this->get($agentId);
        return $agent->getUrls($baseUrl);
    }

    /**
     * Get the simple/embed URL for an agent.
     *
     * This is the direct link to chat with the agent.
     * Format: https://app.heyiris.io/agent/simple/{id}?bloq={bloqId}
     *
     * @param int|string $agentId Agent ID
     * @param string $baseUrl Base URL (default: https://app.heyiris.io)
     * @return string
     *
     * @example
     * ```php
     * $url = $iris->agents->getUrl(11);
     * // https://app.heyiris.io/agent/simple/11?bloq=40
     * ```
     */
    public function getUrl(int|string $agentId, string $baseUrl = 'https://app.heyiris.io'): string
    {
        $agent = $this->get($agentId);
        return $agent->getSimpleUrl($baseUrl);
    }

    /**
     * Upload files and attach them to an agent in one step.
     *
     * This is a convenience method that:
     * 1. Uploads each file to cloud storage
     * 2. Formats the attachment data
     * 3. Adds them to the agent's file attachments
     *
     * Requires the CloudFilesResource to be injected.
     *
     * @param int|string $agentId Agent ID
     * @param array $filePaths Array of file paths to upload
     * @param int $bloqId Bloq ID to upload files to
     * @param array $options Upload options (applied to all files)
     * @return Agent Updated agent with new attachments
     *
     * @example
     * ```php
     * // Upload and attach files in one call
     * $agent = $iris->agents->uploadAndAttachFiles(335, [
     *     '/path/to/training_data.csv',
     *     '/path/to/product_info.pdf',
     * ], 40);
     *
     * echo "Agent now has " . count($agent->fileAttachments) . " files attached\n";
     * ```
     */
    public function uploadAndAttachFiles(
        int|string $agentId,
        array $filePaths,
        int $bloqId,
        array $options = []
    ): Agent {
        // We need to access the CloudFilesResource
        // Since we have access to the same http client and config, we can create one
        $cloudFiles = new \IRIS\SDK\Resources\CloudFiles\CloudFilesResource(
            $this->http,
            $this->config
        );

        // Upload all files and format for attachment
        $attachments = $cloudFiles->uploadMultipleForAgent($filePaths, $bloqId, $options);

        // Add to agent
        return $this->addFileAttachments($agentId, $attachments);
    }
}
