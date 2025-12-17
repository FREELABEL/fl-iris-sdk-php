<?php

declare(strict_types=1);

namespace IRIS\SDK\Auth;

use IRIS\SDK\Config;
use IRIS\SDK\Exceptions\AuthenticationException;

/**
 * Authentication Manager for IRIS SDK
 *
 * Handles dual authentication strategy:
 * - Client Credentials (Machine-to-Machine) for management routes
 * - User Token (Bearer) for interaction routes
 *
 * The FL-API uses different middleware for different route groups:
 * - 'client' middleware: Requires OAuth2 client credentials token
 * - 'auth:api' middleware: Requires user OAuth token
 * - No middleware: Public endpoints
 */
class AuthManager
{
    protected Config $config;

    /**
     * OAuth2 Client ID for client credentials flow
     */
    protected ?string $clientId = null;

    /**
     * OAuth2 Client Secret for client credentials flow
     */
    protected ?string $clientSecret = null;

    /**
     * Cached client credentials token
     */
    protected ?string $clientToken = null;

    /**
     * Token expiration timestamp
     */
    protected ?int $tokenExpiresAt = null;

    /**
     * User Bearer token for interaction routes
     */
    protected ?string $userToken = null;

    /**
     * Routes that require client credentials (machine-to-machine)
     * These routes use Laravel Passport's 'client' middleware
     */
    protected const CLIENT_CREDENTIAL_PATTERNS = [
        '/users/{userId}/bloqs/agents',      // Agent management
        '/user/{userId}/bloqs',              // Bloq management
        '/users/{userId}/bloqs/{bloqId}',    // Bloq item management
        '/content/',                          // Content management
        '/youtube/',                          // YouTube endpoints
        '/services/',                         // Services endpoints
    ];

    /**
     * Routes that are public (no authentication required)
     */
    protected const PUBLIC_PATTERNS = [
        '/api/health',
        '/api/v1/leads',
        '/api/v1/integrations/types',
        '/api/v1/bloqs/agents/generate-response',
        '/api/v1/bloqs/agents/ask',
        '/api/v1/public/',
    ];

    /**
     * Create a new AuthManager instance.
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->userToken = $config->apiKey;
    }

    /**
     * Configure client credentials for management operations.
     */
    public function setClientCredentials(string $clientId, string $clientSecret): self
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->clientToken = null; // Reset cached token
        $this->tokenExpiresAt = null;

        return $this;
    }

    /**
     * Check if client credentials are configured.
     */
    public function hasClientCredentials(): bool
    {
        return $this->clientId !== null && $this->clientSecret !== null;
    }

    /**
     * Get the appropriate token for an endpoint.
     */
    public function getTokenForEndpoint(string $endpoint): string
    {
        $authStrategy = $this->determineAuthStrategy($endpoint);

        switch ($authStrategy) {
            case 'client_credentials':
                return $this->getClientCredentialsToken();

            case 'user_token':
                return $this->userToken ?? throw new AuthenticationException(
                    'User token required but not configured'
                );

            case 'public':
            default:
                // For public routes, still send token if available for better rate limits
                return $this->userToken ?? '';
        }
    }

    /**
     * Get HTTP headers for an endpoint.
     */
    public function getHeadersForEndpoint(string $endpoint): array
    {
        $token = $this->getTokenForEndpoint($endpoint);

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => 'IRIS-PHP-SDK/1.0.0',
        ];

        if (!empty($token)) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        return $headers;
    }

    /**
     * Determine which authentication strategy to use for an endpoint.
     *
     * @return string 'client_credentials' | 'user_token' | 'public'
     */
    public function determineAuthStrategy(string $endpoint): string
    {
        // Normalize endpoint
        $endpoint = '/' . ltrim($endpoint, '/');

        // Check if public
        foreach (self::PUBLIC_PATTERNS as $pattern) {
            if (str_contains($endpoint, $pattern)) {
                return 'public';
            }
        }

        // Check if requires client credentials
        // Replace {userId} and {bloqId} placeholders with actual values for matching
        $normalizedEndpoint = preg_replace('/\/\d+\//', '/{id}/', $endpoint);

        foreach (self::CLIENT_CREDENTIAL_PATTERNS as $pattern) {
            // Convert pattern to regex-friendly format
            $regexPattern = str_replace(
                ['{userId}', '{bloqId}', '{id}'],
                '\d+',
                preg_quote($pattern, '/')
            );

            if (preg_match('/' . $regexPattern . '/', $endpoint)) {
                // Only use client credentials if configured
                if ($this->hasClientCredentials()) {
                    return 'client_credentials';
                }
                // Fall back to user token and hope for the best
                // (will likely get 401, but provides clear error)
                return 'user_token';
            }
        }

        // Default to user token for authenticated routes
        return 'user_token';
    }

    /**
     * Get or fetch a client credentials token.
     */
    protected function getClientCredentialsToken(): string
    {
        // Check if we have a valid cached token
        if ($this->clientToken !== null && $this->tokenExpiresAt !== null) {
            // Add 60 second buffer for safety
            if (time() < ($this->tokenExpiresAt - 60)) {
                return $this->clientToken;
            }
        }

        // Need to fetch a new token
        if (!$this->hasClientCredentials()) {
            throw new AuthenticationException(
                'Client credentials required but not configured. ' .
                'Call $iris->auth()->setClientCredentials($clientId, $clientSecret) first. ' .
                'See docs/AUTH_GUIDE.md for details.'
            );
        }

        $this->fetchClientCredentialsToken();

        return $this->clientToken;
    }

    /**
     * Fetch a new client credentials token from the OAuth server.
     */
    protected function fetchClientCredentialsToken(): void
    {
        $url = $this->config->baseUrl . '/oauth/token';

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope' => '*',
            ]),
            CURLOPT_TIMEOUT => $this->config->timeout,
            CURLOPT_SSL_VERIFYPEER => !$this->config->isDebug(),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            throw new AuthenticationException(
                'Failed to connect to OAuth server: ' . $error
            );
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200) {
            $errorMessage = $data['error_description']
                ?? $data['message']
                ?? $data['error']
                ?? 'Unknown error';

            throw new AuthenticationException(
                "Failed to obtain client credentials token (HTTP {$httpCode}): {$errorMessage}"
            );
        }

        if (empty($data['access_token'])) {
            throw new AuthenticationException(
                'OAuth server returned success but no access_token'
            );
        }

        $this->clientToken = $data['access_token'];
        $this->tokenExpiresAt = time() + ($data['expires_in'] ?? 31536000);

        if ($this->config->isDebug()) {
            error_log("[IRIS SDK] Obtained client credentials token, expires in {$data['expires_in']}s");
        }
    }

    /**
     * Invalidate cached tokens (useful for testing or on 401 errors).
     */
    public function invalidateTokens(): void
    {
        $this->clientToken = null;
        $this->tokenExpiresAt = null;
    }

    /**
     * Get the user token.
     */
    public function getUserToken(): ?string
    {
        return $this->userToken;
    }

    /**
     * Set a new user token.
     */
    public function setUserToken(string $token): self
    {
        $this->userToken = $token;
        return $this;
    }
}
