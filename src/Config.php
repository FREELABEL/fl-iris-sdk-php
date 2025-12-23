<?php

declare(strict_types=1);

namespace IRIS\SDK;

use IRIS\SDK\Auth\CredentialStore;

/**
 * SDK Configuration
 *
 * Holds all configuration options for the IRIS SDK.
 * Can auto-load credentials from ~/.iris/credentials.json or environment variables.
 */
class Config
{
    /**
     * API key for authentication
     */
    public string $apiKey;

    /**
     * Base URL for the main API
     */
    public string $baseUrl = 'https://apiv2.heyiris.io';

    /**
     * Base URL for the IRIS API (V5 workflows)
     */
    public string $irisUrl = 'https://iris.freelabel.net';

    /**
     * Request timeout in seconds
     */
    public int $timeout = 30;

    /**
     * Number of retry attempts for failed requests
     */
    public int $retries = 3;

    /**
     * Webhook secret for verifying incoming webhooks
     */
    public ?string $webhookSecret = null;

    /**
     * OAuth2 Client ID for client credentials flow
     * Required for management operations (agents, bloqs, content)
     */
    public ?string $clientId = null;

    /**
     * OAuth2 Client Secret for client credentials flow
     */
    public ?string $clientSecret = null;

    /**
     * Enable debug mode for verbose logging
     */
    public bool $debug = false;

    /**
     * Current user context for API calls
     */
    public ?int $userId = null;

    /**
     * Polling interval for workflow status checks (milliseconds)
     */
    public int $pollingInterval = 500;

    /**
     * Maximum polling duration before timeout (seconds)
     */
    public int $maxPollingDuration = 300;

    /**
     * Create a new configuration instance.
     *
     * @param array{
     *     api_key: string,
     *     base_url?: string,
     *     iris_url?: string,
     *     user_id?: int,
     *     timeout?: int,
     *     retries?: int,
     *     webhook_secret?: string,
     *     client_id?: string,
     *     client_secret?: string,
     *     debug?: bool,
     *     polling_interval?: int,
     *     max_polling_duration?: int
     * } $options
     *
     * @throws \InvalidArgumentException If api_key is not provided
     */
    public function __construct(array $options)
    {
        if (empty($options['api_key'])) {
            throw new \InvalidArgumentException(
                'api_key is required. Get your API key from https://app.freelabel.net/settings/api'
            );
        }

        $this->apiKey = $options['api_key'];
        $this->baseUrl = rtrim($options['base_url'] ?? $this->baseUrl, '/');
        $this->irisUrl = rtrim($options['iris_url'] ?? $this->irisUrl, '/');
        $this->userId = $options['user_id'] ?? null;
        $this->timeout = $options['timeout'] ?? $this->timeout;
        $this->retries = $options['retries'] ?? $this->retries;
        $this->webhookSecret = $options['webhook_secret'] ?? null;
        $this->clientId = $options['client_id'] ?? null;
        $this->clientSecret = $options['client_secret'] ?? null;
        $this->debug = $options['debug'] ?? false;
        $this->pollingInterval = $options['polling_interval'] ?? $this->pollingInterval;
        $this->maxPollingDuration = $options['max_polling_duration'] ?? $this->maxPollingDuration;
    }

    /**
     * Check if client credentials are configured.
     */
    public function hasClientCredentials(): bool
    {
        return $this->clientId !== null && $this->clientSecret !== null;
    }

    /**
     * Get default HTTP headers for API requests.
     *
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'FreeLABEL-PHP-SDK/' . FreeLABEL::VERSION,
        ];
    }

    /**
     * Check if user context is set.
     *
     * @throws \RuntimeException If user_id is not set
     */
    public function requireUserId(): int
    {
        if ($this->userId === null) {
            throw new \RuntimeException(
                'user_id is required for this operation. ' .
                'Set it in constructor options or use $fl->asUser($userId)'
            );
        }

        return $this->userId;
    }

    /**
     * Check if the SDK is in debug mode.
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * Create a Config instance by auto-loading from credential store.
     *
     * Loads credentials from:
     * 1. ~/.iris/credentials.json (persistent storage)
     * 2. Environment variables (take precedence)
     * 3. Provided options array (highest precedence)
     *
     * @param array $options Additional options to merge
     * @return static
     */
    public static function fromCredentialStore(array $options = []): static
    {
        $store = new CredentialStore();
        $storedConfig = $store->toConfigArray();

        // Merge: stored < options (options take precedence)
        $mergedOptions = array_merge($storedConfig, $options);

        return new static($mergedOptions);
    }

    /**
     * Check if stored credentials exist for auto-loading.
     */
    public static function hasStoredCredentials(): bool
    {
        $store = new CredentialStore();
        return $store->hasMinimumCredentials();
    }
}
