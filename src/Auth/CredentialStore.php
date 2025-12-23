<?php

declare(strict_types=1);

namespace IRIS\SDK\Auth;

/**
 * Persistent Credential Storage
 *
 * Stores SDK credentials in ~/.iris/credentials.json for persistent access.
 * This eliminates the need to provide credentials on every CLI command.
 *
 * Security Notes:
 * - File is stored with 0600 permissions (owner read/write only)
 * - Directory is created with 0700 permissions (owner only)
 * - Never commit this file to version control
 *
 * @example Save credentials
 * ```php
 * $store = new CredentialStore();
 * $store->set('api_key', 'your-token');
 * $store->set('client_id', 'oauth-client-id');
 * $store->set('client_secret', 'oauth-client-secret');
 * $store->save();
 * ```
 *
 * @example Load credentials
 * ```php
 * $store = new CredentialStore();
 * $config = $store->toConfigArray();
 * $iris = new IRIS($config);
 * ```
 */
class CredentialStore
{
    /**
     * Default storage directory name
     */
    private const STORAGE_DIR = '.iris';

    /**
     * Default credentials filename
     */
    private const CREDENTIALS_FILE = 'credentials.json';

    /**
     * Path to credentials file
     */
    protected string $filePath;

    /**
     * Cached credentials
     */
    protected array $credentials = [];

    /**
     * Available credential keys with descriptions
     */
    public const CREDENTIAL_KEYS = [
        'api_key' => 'User API token (Bearer token for authenticated requests)',
        'user_id' => 'User ID for API context',
        'client_id' => 'OAuth2 Client ID (for management operations)',
        'client_secret' => 'OAuth2 Client Secret',
        'base_url' => 'Main API URL (default: https://apiv2.heyiris.io)',
        'iris_url' => 'IRIS API URL (default: https://iris.freelabel.net)',
        'webhook_secret' => 'Webhook signing secret',
    ];

    /**
     * Create a new credential store instance.
     *
     * @param string|null $filePath Custom path to credentials file
     */
    public function __construct(?string $filePath = null)
    {
        $this->filePath = $filePath ?? $this->getDefaultPath();
        $this->load();
    }

    /**
     * Get the default credentials file path.
     */
    protected function getDefaultPath(): string
    {
        $homeDir = getenv('HOME') ?: getenv('USERPROFILE') ?: '/tmp';
        return $homeDir . '/' . self::STORAGE_DIR . '/' . self::CREDENTIALS_FILE;
    }

    /**
     * Load credentials from file.
     */
    public function load(): self
    {
        if (file_exists($this->filePath)) {
            $content = file_get_contents($this->filePath);
            $this->credentials = json_decode($content, true) ?? [];
        }

        // Also load from environment variables as fallback
        $this->loadFromEnvironment();

        return $this;
    }

    /**
     * Load credentials from environment variables.
     * Environment variables take precedence over stored values.
     */
    protected function loadFromEnvironment(): void
    {
        $envMappings = [
            'IRIS_API_KEY' => 'api_key',
            'IRIS_USER_ID' => 'user_id',
            'IRIS_CLIENT_ID' => 'client_id',
            'IRIS_CLIENT_SECRET' => 'client_secret',
            'IRIS_URL' => 'iris_url',
            'IRIS_BASE_URL' => 'base_url',
            'IRIS_WEBHOOK_SECRET' => 'webhook_secret',
        ];

        foreach ($envMappings as $envKey => $credKey) {
            $value = getenv($envKey);
            if ($value !== false && $value !== '') {
                $this->credentials[$credKey] = $value;
            }
        }
    }

    /**
     * Save credentials to file.
     */
    public function save(): self
    {
        $dir = dirname($this->filePath);

        // Create directory if it doesn't exist
        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        // Save with pretty print for readability
        $json = json_encode($this->credentials, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($this->filePath, $json);

        // Ensure secure permissions
        chmod($this->filePath, 0600);

        return $this;
    }

    /**
     * Get a credential value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->credentials[$key] ?? $default;
    }

    /**
     * Set a credential value.
     */
    public function set(string $key, mixed $value): self
    {
        if ($value === null || $value === '') {
            unset($this->credentials[$key]);
        } else {
            $this->credentials[$key] = $value;
        }

        return $this;
    }

    /**
     * Check if a credential exists.
     */
    public function has(string $key): bool
    {
        return isset($this->credentials[$key]) && $this->credentials[$key] !== '';
    }

    /**
     * Remove a credential.
     */
    public function remove(string $key): self
    {
        unset($this->credentials[$key]);
        return $this;
    }

    /**
     * Clear all credentials.
     */
    public function clear(): self
    {
        $this->credentials = [];

        if (file_exists($this->filePath)) {
            unlink($this->filePath);
        }

        return $this;
    }

    /**
     * Get all stored credentials.
     */
    public function all(): array
    {
        return $this->credentials;
    }

    /**
     * Convert credentials to SDK config array format.
     */
    public function toConfigArray(): array
    {
        $config = [];

        if ($this->has('api_key')) {
            $config['api_key'] = $this->get('api_key');
        }

        if ($this->has('user_id')) {
            $config['user_id'] = (int) $this->get('user_id');
        }

        if ($this->has('client_id')) {
            $config['client_id'] = $this->get('client_id');
        }

        if ($this->has('client_secret')) {
            $config['client_secret'] = $this->get('client_secret');
        }

        if ($this->has('base_url')) {
            $config['base_url'] = $this->get('base_url');
        }

        if ($this->has('iris_url')) {
            $config['iris_url'] = $this->get('iris_url');
        }

        if ($this->has('webhook_secret')) {
            $config['webhook_secret'] = $this->get('webhook_secret');
        }

        return $config;
    }

    /**
     * Check if minimum required credentials exist for SDK usage.
     */
    public function hasMinimumCredentials(): bool
    {
        return $this->has('api_key') && $this->has('user_id');
    }

    /**
     * Check if OAuth credentials exist for management operations.
     */
    public function hasOAuthCredentials(): bool
    {
        return $this->has('client_id') && $this->has('client_secret');
    }

    /**
     * Get the credentials file path.
     */
    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * Create a masked version of credentials for display.
     */
    public function getMaskedCredentials(): array
    {
        $masked = [];

        foreach ($this->credentials as $key => $value) {
            if (is_string($value) && strlen($value) > 8) {
                // Show first 4 and last 4 characters
                $masked[$key] = substr($value, 0, 4) . str_repeat('*', 8) . substr($value, -4);
            } elseif (is_string($value)) {
                $masked[$key] = str_repeat('*', strlen($value));
            } else {
                $masked[$key] = $value;
            }
        }

        return $masked;
    }

    /**
     * Factory method to create from environment variables only.
     */
    public static function fromEnvironment(): self
    {
        $store = new self('/dev/null'); // Don't use file storage
        $store->credentials = []; // Clear any loaded data
        $store->loadFromEnvironment();
        return $store;
    }
}
