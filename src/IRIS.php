<?php

declare(strict_types=1);

namespace IRIS\SDK;

use IRIS\SDK\Http\Client;
use IRIS\SDK\Resources\Agents\AgentsResource;
use IRIS\SDK\Resources\Workflows\WorkflowsResource;
use IRIS\SDK\Resources\Bloqs\BloqsResource;
use IRIS\SDK\Resources\Leads\LeadsResource;
use IRIS\SDK\Resources\Integrations\IntegrationsResource;
use IRIS\SDK\Resources\RAG\RAGResource;
use IRIS\SDK\Events\WebhookHandler;

/**
 * IRIS SDK Client
 *
 * The main entry point for interacting with the IRIS AI platform.
 *
 * @example
 * ```php
 * $iris = new IRIS([
 *     'api_key' => 'sk_live_xxxxx',
 *     'user_id' => 123,
 * ]);
 *
 * // Chat with an agent
 * $response = $iris->agents->chat('agent_123', [
 *     ['role' => 'user', 'content' => 'Hello!']
 * ]);
 *
 * // Execute a workflow
 * $workflow = $iris->workflows->execute([
 *     'agent_id' => 'agent_123',
 *     'query' => 'Research competitors',
 * ]);
 * ```
 */
class IRIS
{
    /**
     * SDK Version
     */
    public const VERSION = '1.0.0';

    /**
     * Configuration instance
     */
    protected Config $config;

    /**
     * HTTP client instance
     */
    protected Client $http;

    /**
     * Agents resource for managing AI agents
     */
    public AgentsResource $agents;

    /**
     * Workflows resource for V5 workflow execution
     */
    public WorkflowsResource $workflows;

    /**
     * Bloqs resource for document management
     */
    public BloqsResource $bloqs;

    /**
     * Leads resource for CRM functionality
     */
    public LeadsResource $leads;

    /**
     * Integrations resource for third-party services
     */
    public IntegrationsResource $integrations;

    /**
     * RAG resource for knowledge base operations
     */
    public RAGResource $rag;

    /**
     * Create a new IRIS client instance.
     *
     * @param array{
     *     api_key: string,
     *     base_url?: string,
     *     iris_url?: string,
     *     user_id?: int,
     *     timeout?: int,
     *     retries?: int,
     *     webhook_secret?: string,
     *     debug?: bool
     * } $options Configuration options
     *
     * @throws \InvalidArgumentException If api_key is not provided
     */
    public function __construct(array $options)
    {
        $this->config = new Config($options);
        $this->http = new Client($this->config);

        // Initialize resource modules
        $this->agents = new AgentsResource($this->http, $this->config);
        $this->workflows = new WorkflowsResource($this->http, $this->config);
        $this->bloqs = new BloqsResource($this->http, $this->config);
        $this->leads = new LeadsResource($this->http, $this->config);
        $this->integrations = new IntegrationsResource($this->http, $this->config);
        $this->rag = new RAGResource($this->http, $this->config);
    }

    /**
     * Get the configuration instance.
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Get the HTTP client instance.
     */
    public function getHttpClient(): Client
    {
        return $this->http;
    }

    /**
     * Set the user context for API calls.
     *
     * @param int $userId The user ID to use for subsequent API calls
     * @return $this
     */
    public function asUser(int $userId): self
    {
        $this->config->userId = $userId;
        return $this;
    }

    /**
     * Create a webhook handler for processing incoming webhook events.
     *
     * @return WebhookHandler
     */
    public function webhooks(): WebhookHandler
    {
        return new WebhookHandler($this->config->webhookSecret);
    }

    /**
     * Test the API connection.
     *
     * @return bool True if connection is successful
     * @throws \IRIS\SDK\Exceptions\IRISException
     */
    public function testConnection(): bool
    {
        $response = $this->http->get('/v1/health');
        return $response['status'] === 'ok';
    }

    /**
     * Get account information for the authenticated user.
     *
     * @return array Account details
     */
    public function account(): array
    {
        return $this->http->get('/v1/user');
    }

    /**
     * Get usage statistics for the current billing period.
     *
     * @return array Usage statistics
     */
    public function usage(): array
    {
        return $this->http->get('/v1/billing/usage');
    }
}
