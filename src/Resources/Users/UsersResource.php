<?php

declare(strict_types=1);

namespace IRIS\SDK\Resources\Users;

use IRIS\SDK\Config;
use IRIS\SDK\Http\Client;

/**
 * Users Resource
 *
 * Manage user accounts in FL-API.
 *
 * @example
 * ```php
 * // Search for users
 * $users = $iris->users->search('juan');
 *
 * // Get a specific user
 * $user = $iris->users->get(456);
 *
 * // Create a new user
 * $user = $iris->users->create([
 *     'email' => 'user@example.com',
 *     'full_name' => 'John Doe',
 *     'phone' => '555-1234',
 *     'password' => 'secure_password',
 * ]);
 *
 * // Update user
 * $user = $iris->users->update(456, [
 *     'full_name' => 'Jane Doe',
 * ]);
 * ```
 */
class UsersResource
{
    protected Client $http;
    protected Config $config;

    public function __construct(Client $http, Config $config)
    {
        $this->http = $http;
        $this->config = $config;
    }

    /**
     * Search for users by name or email.
     *
     * @param string $query Search query
     * @return array
     */
    public function search(string $query): array
    {
        $response = $this->http->get('/api/v1/users/search', [
            'query' => $query,
        ]);

        return $response['data'] ?? $response;
    }

    /**
     * Get a specific user by ID.
     *
     * @param int $userId User ID
     * @return array
     */
    public function get(int $userId): array
    {
        $response = $this->http->get("/api/v1/users/{$userId}");

        return $response['data'] ?? $response;
    }

    /**
     * Create a new user.
     *
     * @param array{
     *     email: string,
     *     full_name: string,
     *     phone?: string,
     *     password?: string,
     *     account_type?: string,
     *     dashboard_type?: string,
     *     status?: string
     * } $data User data
     * @return array
     */
    public function create(array $data): array
    {
        $payload = array_merge([
            'status' => 'active',
            'account_type' => 'business',
            'dashboard_type' => 'business',
        ], $data);

        $response = $this->http->post('/api/v1/users', $payload);

        return $response['data'] ?? $response;
    }

    /**
     * Update an existing user.
     *
     * @param int $userId User ID
     * @param array $data Updated user data
     * @return array
     */
    public function update(int $userId, array $data): array
    {
        $response = $this->http->put("/api/v1/users/{$userId}", $data);

        return $response['data'] ?? $response;
    }

    /**
     * Delete a user.
     *
     * @param int $userId User ID
     * @return bool
     */
    public function delete(int $userId): bool
    {
        $this->http->delete("/api/v1/users/{$userId}");

        return true;
    }

    /**
     * List all users (paginated).
     *
     * @param array{
     *     page?: int,
     *     per_page?: int,
     *     status?: string
     * } $filters Filter options
     * @return array
     */
    public function list(array $filters = []): array
    {
        $response = $this->http->get('/api/v1/users', $filters);

        return $response['data'] ?? $response;
    }

    /**
     * Get the current authenticated user.
     *
     * @return array
     */
    public function me(): array
    {
        $response = $this->http->get('/api/v1/user/me');

        return $response['data'] ?? $response;
    }
}
