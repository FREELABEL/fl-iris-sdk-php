<?php

declare(strict_types=1);

namespace IRIS\SDK\Resources\Integrations;

use IRIS\SDK\Config;
use IRIS\SDK\Http\Client;

/**
 * Integrations Resource
 *
 * Manage third-party integrations and MCP services.
 *
 * @example
 * ```php
 * // List all integrations
 * $integrations = $iris->integrations->list();
 *
 * // Get OAuth URL
 * $url = $iris->integrations->getOAuthUrl('google-drive');
 *
 * // Execute an integration function
 * $result = $iris->integrations->execute('gmail', 'send_email', [
 *     'to' => 'user@example.com',
 *     'subject' => 'Hello',
 *     'body' => 'Test message',
 * ]);
 * ```
 */
class IntegrationsResource
{
    /**
     * Supported integration types
     */
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

    protected Client $http;
    protected Config $config;

    public function __construct(Client $http, Config $config)
    {
        $this->http = $http;
        $this->config = $config;
    }

    /**
     * List all integrations.
     *
     * @return IntegrationCollection
     */
    public function list(): IntegrationCollection
    {
        $response = $this->http->get("/api/v1/integrations");

        return new IntegrationCollection(
            array_map(fn($data) => new Integration($data), $response['data'] ?? $response),
            $response['meta'] ?? []
        );
    }

    /**
     * Create a new integration.
     *
     * @param array{
     *     type: string,
     *     name: string,
     *     config?: array
     * } $data Integration data
     * @return Integration
     */
    public function create(array $data): Integration
    {
        $response = $this->http->post("/api/v1/integrations", $data);

        return new Integration($response);
    }

    /**
     * Get a specific integration by ID.
     *
     * @param int $integrationId Integration ID
     * @return Integration
     */
    public function get(int $integrationId): Integration
    {
        $response = $this->http->get("/api/v1/integrations/{$integrationId}");

        return new Integration($response);
    }

    /**
     * Update an integration.
     *
     * @param int $integrationId Integration ID
     * @param array $data Update data
     * @return Integration
     */
    public function update(int $integrationId, array $data): Integration
    {
        $response = $this->http->put("/api/v1/integrations/{$integrationId}", $data);

        return new Integration($response);
    }

    /**
     * Delete an integration.
     *
     * @param int $integrationId Integration ID
     * @return bool
     */
    public function delete(int $integrationId): bool
    {
        $this->http->delete("/api/v1/integrations/{$integrationId}");

        return true;
    }

    /**
     * Test an integration.
     *
     * @param int $integrationId Integration ID
     * @return TestResult
     */
    public function test(int $integrationId): TestResult
    {
        $response = $this->http->post("/api/v1/integrations/{$integrationId}/test");

        return new TestResult($response);
    }

    /**
     * Get available integration types.
     *
     * @return array
     */
    public function types(): array
    {
        $response = $this->http->get("/api/v1/integrations/types");

        return $response['types'] ?? $response;
    }

    /**
     * Get OAuth URL for an integration type.
     *
     * @param string $type Integration type
     * @return string OAuth URL
     */
    public function getOAuthUrl(string $type): string
    {
        $response = $this->http->get("/api/v1/integrations/oauth-url/{$type}");

        return $response['url'] ?? '';
    }

    /**
     * Handle OAuth callback.
     *
     * @param string $type Integration type
     * @param array{
     *     code?: string,
     *     state?: string,
     *     error?: string
     * } $params Callback parameters
     * @return Integration
     */
    public function handleCallback(string $type, array $params): Integration
    {
        $response = $this->http->get("/api/v1/integrations/oauth-callback/{$type}", $params);

        return new Integration($response);
    }

    /**
     * Get integration metadata.
     *
     * @return array
     */
    public function getMetadata(): array
    {
        return $this->http->get("/api/v1/integrations/metadata");
    }

    /**
     * Get enabled integrations.
     *
     * @return IntegrationCollection
     */
    public function enabled(): IntegrationCollection
    {
        $response = $this->http->get("/api/v1/integrations/enabled");

        return new IntegrationCollection(
            array_map(fn($data) => new Integration($data), $response['data'] ?? $response),
            $response['meta'] ?? []
        );
    }

    /**
     * Execute an integration function.
     *
     * @param string $type Integration type
     * @param string $function Function name
     * @param array $params Function parameters
     * @return array
     */
    public function execute(string $type, string $function, array $params = []): array
    {
        return $this->http->post("/api/v1/integrations/execute", [
            'type' => $type,
            'function' => $function,
            'params' => $params,
        ]);
    }

    /**
     * Get AI context from integrations.
     *
     * @return array
     */
    public function getAiContext(): array
    {
        return $this->http->get("/api/v1/integrations/ai-context");
    }

    /**
     * Get MCP integrations.
     *
     * @return array
     */
    public function mcpIntegrations(): array
    {
        $response = $this->http->get("/api/v1/mcp/integrations");

        return $response['integrations'] ?? $response;
    }

    /**
     * Get functions for an MCP integration type.
     *
     * @param string $type Integration type
     * @return array<IntegrationFunction>
     */
    public function getFunctions(string $type): array
    {
        $response = $this->http->get("/api/v1/mcp/{$type}/functions");

        $functions = $response['functions'] ?? $response;
        
        return array_map(fn($data) => new IntegrationFunction($data), $functions);
    }

    /**
     * Execute an MCP function.
     *
     * @param string $type Integration type
     * @param string $function Function name
     * @param array $params Function parameters
     * @return array
     */
    public function executeFunction(string $type, string $function, array $params = []): array
    {
        return $this->http->post("/api/v1/mcp/{$type}/execute", [
            'function' => $function,
            'params' => $params,
        ]);
    }

    /**
     * Test an MCP service.
     *
     * @param string $type Integration type
     * @return TestResult
     */
    public function testService(string $type): TestResult
    {
        $response = $this->http->post("/api/v1/mcp/test/{$type}");

        return new TestResult($response);
    }
}
