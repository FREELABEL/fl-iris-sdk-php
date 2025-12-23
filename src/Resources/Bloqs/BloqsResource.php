<?php

declare(strict_types=1);

namespace IRIS\SDK\Resources\Bloqs;

use IRIS\SDK\Config;
use IRIS\SDK\Http\Client;

/**
 * Bloqs Resource
 *
 * Manage Bloqs - containers for organizing lists and items.
 *
 * @example
 * ```php
 * // Create a bloq
 * $bloq = $iris->bloqs->create('Project Planning', [
 *     'description' => 'Q1 2024 planning',
 *     'color' => '#FF5733',
 * ]);
 *
 * // Create a list in the bloq
 * $list = $iris->bloqs->lists($bloq->id)->create([
 *     'title' => 'Todo List',
 *     'type' => 'checklist',
 * ]);
 *
 * // Add an item to the list
 * $item = $iris->bloqs->items($list->id)->create([
 *     'title' => 'Complete documentation',
 *     'content' => 'Write user guide',
 * ]);
 * ```
 */
class BloqsResource
{
    protected Client $http;
    protected Config $config;

    public function __construct(Client $http, Config $config)
    {
        $this->http = $http;
        $this->config = $config;
    }

    /**
     * List all bloqs for the current user.
     *
     * @param array{
     *     page?: int,
     *     per_page?: int,
     *     search?: string
     * } $options List options
     * @return BloqCollection
     */
    public function list(array $options = []): BloqCollection
    {
        $userId = $this->config->requireUserId();
        $response = $this->http->get("/api/v1/user/{$userId}/bloqs", $options);

        return new BloqCollection(
            array_map(fn($data) => new Bloq($data), $response['data'] ?? $response),
            $response['meta'] ?? []
        );
    }

    /**
     * Create a new bloq.
     *
     * @param string $title Bloq title
     * @param array{
     *     description?: string,
     *     color?: string,
     *     icon?: string
     * } $options Optional bloq properties
     * @return Bloq
     */
    public function create(string $title, array $options = []): Bloq
    {
        $userId = $this->config->requireUserId();
        $data = array_merge(['title' => $title], $options);
        
        $response = $this->http->post("/api/v1/user/{$userId}/bloqs", $data);

        return new Bloq($response);
    }

    /**
     * Get a specific bloq by ID.
     *
     * @param int $bloqId Bloq ID
     * @return Bloq
     */
    public function get(int $bloqId): Bloq
    {
        $userId = $this->config->requireUserId();
        $response = $this->http->get("/api/v1/user/{$userId}/bloqs/{$bloqId}");

        return new Bloq($response);
    }

    /**
     * Update a bloq.
     *
     * @param int $bloqId Bloq ID
     * @param array $data Update data
     * @return Bloq
     */
    public function update(int $bloqId, array $data): Bloq
    {
        $userId = $this->config->requireUserId();
        $response = $this->http->put("/api/v1/user/{$userId}/bloqs/{$bloqId}", $data);

        return new Bloq($response);
    }

    /**
     * Delete a bloq.
     *
     * @param int $bloqId Bloq ID
     * @return bool
     */
    public function delete(int $bloqId): bool
    {
        $userId = $this->config->requireUserId();
        $this->http->delete("/api/v1/user/{$userId}/bloqs/{$bloqId}");

        return true;
    }

    /**
     * Get the count of bloqs.
     *
     * @return int
     */
    public function count(): int
    {
        $userId = $this->config->requireUserId();
        $response = $this->http->get("/api/v1/user/{$userId}/bloqs/count");

        return (int) ($response['count'] ?? 0);
    }

    /**
     * Get bloqs overview with statistics.
     *
     * @param array{
     *     sort_by?: string,
     *     sort_direction?: string
     * } $options Sorting options
     * @return array Overview data with bloqs and statistics
     *
     * @example
     * ```php
     * // Get overview sorted by recently used
     * $overview = $iris->bloqs->overview([
     *     'sort_by' => 'recently_used',
     *     'sort_direction' => 'desc'
     * ]);
     *
     * // Sort options: recently_used, created_at, updated_at, name
     * ```
     */
    public function overview(array $options = []): array
    {
        $userId = $this->config->requireUserId();
        return $this->http->get("/api/v1/users/{$userId}/bloqs/overview", $options);
    }

    /**
     * List all agents across all bloqs for the user.
     *
     * @param array{
     *     search?: string,
     *     per_page?: int,
     *     page?: int
     * } $options Filter options
     * @return array List of agents
     *
     * @example
     * ```php
     * // List all user agents
     * $agents = $iris->bloqs->agents();
     *
     * // Search agents
     * $agents = $iris->bloqs->agents(['search' => 'recruiter']);
     * ```
     */
    public function agents(array $options = []): array
    {
        $userId = $this->config->requireUserId();
        return $this->http->get("/api/v1/users/{$userId}/bloqs/agents", $options);
    }

    /**
     * List agents for a specific bloq.
     *
     * @param int $bloqId Bloq ID
     * @param array $options Filter options
     * @return array List of agents for the bloq
     *
     * @example
     * ```php
     * // List agents for bloq 32
     * $agents = $iris->bloqs->bloqAgents(32);
     * ```
     */
    public function bloqAgents(int $bloqId, array $options = []): array
    {
        $userId = $this->config->requireUserId();
        return $this->http->get("/api/v1/users/{$userId}/bloqs/{$bloqId}/agents", $options);
    }

    /**
     * List all workflows for the user.
     *
     * @param array $options Filter options
     * @return array List of workflows
     *
     * @example
     * ```php
     * $workflows = $iris->bloqs->workflows();
     * ```
     */
    public function workflows(array $options = []): array
    {
        $userId = $this->config->requireUserId();
        return $this->http->get("/api/v1/users/{$userId}/bloqs/workflows", $options);
    }

    /**
     * Get recent items across all bloqs.
     *
     * @return BloqItemCollection
     */
    public function recentItems(): BloqItemCollection
    {
        $userId = $this->config->requireUserId();
        $response = $this->http->get("/api/v1/users/{$userId}/bloqs/recent-items");

        $items = array_map(fn($data) => new BloqItem($data), $response['data'] ?? $response);
        
        return new BloqItemCollection($items, $response['meta'] ?? []);
    }

    /**
     * Get detailed information about a bloq.
     *
     * @param int $bloqId Bloq ID
     * @return array
     */
    public function getDetails(int $bloqId): array
    {
        $userId = $this->config->requireUserId();
        return $this->http->get("/api/v1/users/{$userId}/bloqs/{$bloqId}/details");
    }

    /**
     * Track bloq access (analytics).
     *
     * @param int $bloqId Bloq ID
     * @return bool
     */
    public function trackAccess(int $bloqId): bool
    {
        $userId = $this->config->requireUserId();
        $this->http->post("/api/v1/users/{$userId}/bloqs/{$bloqId}/access");

        return true;
    }

    /**
     * Toggle pin status of a bloq.
     *
     * @param int $bloqId Bloq ID
     * @return Bloq
     */
    public function togglePin(int $bloqId): Bloq
    {
        $userId = $this->config->requireUserId();
        $response = $this->http->post("/api/v1/users/{$userId}/bloqs/{$bloqId}/pin");

        return new Bloq($response);
    }

    /**
     * Access lists sub-resource for a bloq.
     *
     * @param int $bloqId Bloq ID
     * @return ListsResource
     */
    public function lists(int $bloqId): ListsResource
    {
        return new ListsResource($this->http, $this->config, $bloqId);
    }

    /**
     * Access items sub-resource for a list.
     *
     * @param int $listId List ID
     * @return ItemsResource
     */
    public function items(int $listId): ItemsResource
    {
        return new ItemsResource($this->http, $this->config, $listId);
    }

    /**
     * Get all cloud files.
     *
     * @return CloudFileCollection
     */
    public function files(): CloudFileCollection
    {
        $response = $this->http->get("/api/v1/cloud-files");

        $files = array_map(fn($data) => new CloudFile($data), $response['data'] ?? $response);
        
        return new CloudFileCollection($files, $response['meta'] ?? []);
    }

    /**
     * Upload a file to a bloq.
     *
     * @param int $bloqId Bloq ID
     * @param string $filePath Path to file
     * @param array $metadata Additional metadata
     * @return CloudFile
     */
    public function uploadFile(int $bloqId, string $filePath, array $metadata = []): CloudFile
    {
        $data = array_merge(['bloq_id' => $bloqId], $metadata);
        $response = $this->http->upload("/api/v1/cloud-files/upload", $filePath, $data);

        return new CloudFile($response);
    }

    /**
     * Get a specific file.
     *
     * @param int $fileId File ID
     * @return CloudFile
     */
    public function getFile(int $fileId): CloudFile
    {
        $response = $this->http->get("/api/v1/cloud-files/{$fileId}");

        return new CloudFile($response);
    }

    /**
     * Get download URL for a file.
     *
     * @param int $fileId File ID
     * @return string Download URL
     */
    public function downloadFile(int $fileId): string
    {
        $response = $this->http->get("/api/v1/cloud-files/{$fileId}/download");

        return $response['url'] ?? '';
    }

    /**
     * Get file processing status.
     *
     * @param int $fileId File ID
     * @return array
     */
    public function getFileStatus(int $fileId): array
    {
        return $this->http->get("/api/v1/cloud-files/{$fileId}/status");
    }

    /**
     * Delete a file.
     *
     * @param int $fileId File ID
     * @return bool
     */
    public function deleteFile(int $fileId): bool
    {
        $this->http->delete("/api/v1/cloud-files/{$fileId}");

        return true;
    }

    /**
     * Get files for a specific bloq.
     *
     * @param int $bloqId Bloq ID
     * @return CloudFileCollection
     */
    public function getBloqFiles(int $bloqId): CloudFileCollection
    {
        $response = $this->http->get("/api/v1/bloqs/{$bloqId}/files");

        $files = array_map(fn($data) => new CloudFile($data), $response['data'] ?? $response);
        
        return new CloudFileCollection($files, $response['meta'] ?? []);
    }

    /**
     * Get supported file types.
     *
     * @return array
     */
    public function supportedFileTypes(): array
    {
        $response = $this->http->get("/api/v1/cloud-files/supported-types");

        return $response['types'] ?? $response;
    }

    /**
     * Get custom fields configuration for a bloq.
     *
     * Custom fields allow you to define additional data fields for leads
     * in this bloq (e.g., company name, phone, service type).
     *
     * @param int $bloqId Bloq ID
     * @return array Custom fields configuration
     *
     * @example
     * ```php
     * $config = $iris->bloqs->getCustomFieldsConfig(32);
     * foreach ($config['fields'] as $field) {
     *     echo "{$field['label']} ({$field['type']})\n";
     * }
     * ```
     */
    public function getCustomFieldsConfig(int $bloqId): array
    {
        return $this->http->get("/api/v1/bloqs/{$bloqId}/custom-fields-config");
    }

    /**
     * Update custom fields configuration for a bloq.
     *
     * Supported field types:
     * - text: Single-line text input
     * - textarea: Multi-line text input
     * - email: Email input with validation
     * - tel: Phone number input
     * - url: URL input with validation
     * - number: Numeric input
     * - date: Date picker
     * - select: Dropdown selection (requires options array)
     * - radio: Radio button group (requires options array)
     * - checkbox: Checkbox (single or multiple with options)
     *
     * @param int $bloqId Bloq ID
     * @param array $config Configuration with 'fields' array
     * @return array Updated configuration
     *
     * @example
     * ```php
     * $config = $iris->bloqs->updateCustomFieldsConfig(32, [
     *     'fields' => [
     *         [
     *             'id' => 'company_name',
     *             'label' => 'Company Name',
     *             'type' => 'text',
     *             'required' => true,
     *             'placeholder' => 'Enter company name'
     *         ],
     *         [
     *             'id' => 'contact_phone',
     *             'label' => 'Phone Number',
     *             'type' => 'tel',
     *             'required' => true,
     *             'placeholder' => '(555) 123-4567'
     *         ],
     *         [
     *             'id' => 'service_type',
     *             'label' => 'Service Type',
     *             'type' => 'radio',
     *             'required' => true,
     *             'options' => ['Web Development', 'Mobile App', 'Consulting']
     *         ],
     *         [
     *             'id' => 'newsletter',
     *             'label' => 'Subscribe to newsletter',
     *             'type' => 'checkbox',
     *             'required' => false
     *         ]
     *     ]
     * ]);
     * ```
     */
    public function updateCustomFieldsConfig(int $bloqId, array $config): array
    {
        return $this->http->put("/api/v1/bloqs/{$bloqId}/custom-fields-config", [
            'config' => $config,
        ]);
    }

    /**
     * Add a custom field to a bloq's configuration.
     *
     * Convenience method to add a single field without overwriting existing ones.
     *
     * @param int $bloqId Bloq ID
     * @param array $field Field definition
     * @return array Updated configuration
     *
     * @example
     * ```php
     * $iris->bloqs->addCustomField(32, [
     *     'id' => 'budget',
     *     'label' => 'Budget Range',
     *     'type' => 'select',
     *     'required' => false,
     *     'options' => ['Under $5k', '$5k-$10k', '$10k-$25k', '$25k+']
     * ]);
     * ```
     */
    public function addCustomField(int $bloqId, array $field): array
    {
        // Get existing config
        $existing = $this->getCustomFieldsConfig($bloqId);
        $fields = $existing['config']['fields'] ?? $existing['fields'] ?? [];

        // Add new field
        $fields[] = $field;

        return $this->updateCustomFieldsConfig($bloqId, ['fields' => $fields]);
    }

    /**
     * Remove a custom field from a bloq's configuration.
     *
     * @param int $bloqId Bloq ID
     * @param string $fieldId Field ID to remove
     * @return array Updated configuration
     *
     * @example
     * ```php
     * $iris->bloqs->removeCustomField(32, 'newsletter');
     * ```
     */
    public function removeCustomField(int $bloqId, string $fieldId): array
    {
        // Get existing config
        $existing = $this->getCustomFieldsConfig($bloqId);
        $fields = $existing['config']['fields'] ?? $existing['fields'] ?? [];

        // Filter out the field
        $fields = array_values(array_filter($fields, fn($f) => ($f['id'] ?? '') !== $fieldId));

        return $this->updateCustomFieldsConfig($bloqId, ['fields' => $fields]);
    }

    /**
     * Clear all custom fields from a bloq.
     *
     * @param int $bloqId Bloq ID
     * @return array Empty configuration
     */
    public function clearCustomFields(int $bloqId): array
    {
        return $this->updateCustomFieldsConfig($bloqId, ['fields' => []]);
    }

    // =========================================================================
    // SHARING & COLLABORATION
    // =========================================================================

    /**
     * Share a bloq with another user.
     *
     * @param int $bloqId Bloq ID
     * @param int $targetUserId User ID to share with
     * @param string $permission Permission level: 'read', 'write', 'admin'
     * @return array Share result
     *
     * @example
     * ```php
     * // Share bloq with read access
     * $iris->bloqs->share(40, 456, 'read');
     *
     * // Share with write access
     * $iris->bloqs->share(40, 456, 'write');
     * ```
     */
    public function share(int $bloqId, int $targetUserId, string $permission = 'read'): array
    {
        $userId = $this->config->requireUserId();
        return $this->http->post("/api/v1/user/bloqs/{$bloqId}/share", [
            'user_id' => $targetUserId,
            'permission' => $permission,
        ]);
    }

    /**
     * Get users who have access to a bloq.
     *
     * @param int $bloqId Bloq ID
     * @return array List of shared users with permissions
     */
    public function getSharedUsers(int $bloqId): array
    {
        return $this->http->get("/api/v1/user/bloqs/{$bloqId}/shared-users");
    }

    /**
     * Update sharing permissions for a user.
     *
     * @param int $bloqId Bloq ID
     * @param int $targetUserId User ID to update
     * @param string $permission New permission level
     * @return array Updated share info
     */
    public function updateSharePermission(int $bloqId, int $targetUserId, string $permission): array
    {
        return $this->http->put("/api/v1/user/bloqs/{$bloqId}/share/{$targetUserId}", [
            'permission' => $permission,
        ]);
    }

    /**
     * Remove sharing access for a user.
     *
     * @param int $bloqId Bloq ID
     * @param int $targetUserId User ID to remove
     * @return bool
     */
    public function unshare(int $bloqId, int $targetUserId): bool
    {
        $this->http->delete("/api/v1/user/bloqs/{$bloqId}/share/{$targetUserId}");
        return true;
    }

    // =========================================================================
    // CONTENT MANAGEMENT (for RAG/Knowledge Base)
    // =========================================================================

    /**
     * Get all content in a bloq (for knowledge base/RAG).
     *
     * @param int $bloqId Bloq ID
     * @return array Content items with metadata
     */
    public function getContent(int $bloqId): array
    {
        return $this->http->get("/api/v1/user/bloqs/{$bloqId}/content");
    }

    /**
     * Add content to a bloq (automatically indexed for RAG).
     *
     * @param int $bloqId Bloq ID
     * @param array $content Content to add
     * @return array Added content
     *
     * @example
     * ```php
     * // Add text content (auto-vectorized for RAG)
     * $iris->bloqs->addContent(40, [
     *     'title' => 'Company Policy',
     *     'content' => 'Our vacation policy allows 20 days PTO...',
     *     'type' => 'document',
     * ]);
     * ```
     */
    public function addContent(int $bloqId, array $content): array
    {
        return $this->http->post("/api/v1/user/bloqs/{$bloqId}/content", $content);
    }

    /**
     * Remove content from a bloq.
     *
     * @param int $bloqId Bloq ID
     * @param int $contentId Content ID to remove
     * @return bool
     */
    public function removeContent(int $bloqId, int $contentId): bool
    {
        $this->http->delete("/api/v1/user/bloqs/{$bloqId}/content/{$contentId}");
        return true;
    }

    // =========================================================================
    // PUBLIC SHARING (Items)
    // =========================================================================

    /**
     * Make a bloq item publicly accessible.
     *
     * @param int $itemId Item ID
     * @return array Public share info with UUID
     *
     * @example
     * ```php
     * $result = $iris->bloqs->makeItemPublic(123);
     * echo "Public URL: {$result['public_url']}";
     * ```
     */
    public function makeItemPublic(int $itemId): array
    {
        $userId = $this->config->requireUserId();
        return $this->http->post("/api/v1/users/{$userId}/bloqs/list/item/{$itemId}/make-public");
    }

    /**
     * Revoke public access to a bloq item.
     *
     * @param int $itemId Item ID
     * @return bool
     */
    public function makeItemPrivate(int $itemId): bool
    {
        $userId = $this->config->requireUserId();
        $this->http->post("/api/v1/users/{$userId}/bloqs/list/item/{$itemId}/make-private");
        return true;
    }

    /**
     * Get a public item by UUID (no authentication required).
     *
     * @param string $uuid Public UUID
     * @return BloqItem
     */
    public function getPublicItem(string $uuid): BloqItem
    {
        $response = $this->http->get("/api/bloq/item/{$uuid}");
        return new BloqItem($response);
    }

    // =========================================================================
    // CHAT MESSAGES (for conversational memory)
    // =========================================================================

    /**
     * Store a chat message for an item (conversation history).
     *
     * @param int $itemId Item ID
     * @param array $message Message data
     * @return array Stored message
     *
     * @example
     * ```php
     * $iris->bloqs->storeChatMessage(123, [
     *     'role' => 'user',
     *     'content' => 'What is our refund policy?',
     * ]);
     * ```
     */
    public function storeChatMessage(int $itemId, array $message): array
    {
        $userId = $this->config->requireUserId();
        return $this->http->post("/api/v1/users/{$userId}/bloqs/list/item/{$itemId}/chat/messages", $message);
    }

    /**
     * Get chat messages for an item.
     *
     * @param int $itemId Item ID
     * @return array Chat messages
     */
    public function getChatMessages(int $itemId): array
    {
        $userId = $this->config->requireUserId();
        return $this->http->get("/api/v1/users/{$userId}/bloqs/list/item/{$itemId}/chat/messages");
    }

    /**
     * Clear chat messages for an item.
     *
     * @param int $itemId Item ID
     * @return bool
     */
    public function clearChatMessages(int $itemId): bool
    {
        $userId = $this->config->requireUserId();
        $this->http->delete("/api/v1/users/{$userId}/bloqs/list/item/{$itemId}/chat/messages");
        return true;
    }
}
