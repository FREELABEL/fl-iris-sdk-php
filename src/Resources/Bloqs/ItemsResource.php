<?php

declare(strict_types=1);

namespace IRIS\SDK\Resources\Bloqs;

use IRIS\SDK\Config;
use IRIS\SDK\Http\Client;

/**
 * Items Sub-Resource
 *
 * Manage items within a list.
 */
class ItemsResource
{
    protected Client $http;
    protected Config $config;
    protected int $listId;

    public function __construct(Client $http, Config $config, int $listId)
    {
        $this->http = $http;
        $this->config = $config;
        $this->listId = $listId;
    }

    /**
     * Create a new item in this list.
     *
     * @param array{
     *     title: string,
     *     content?: string,
     *     type?: string,
     *     position?: int,
     *     is_public?: bool,
     *     metadata?: array
     * } $data Item data
     * @return BloqItem
     */
    public function create(array $data): BloqItem
    {
        $userId = $this->config->requireUserId();
        $response = $this->http->post(
            "/api/v1/user/{$userId}/bloqs/lists/{$this->listId}/items",
            $data
        );

        return new BloqItem($response);
    }

    /**
     * Get chat messages for an item.
     *
     * @param int $itemId Item ID
     * @return array
     */
    public function getMessages(int $itemId): array
    {
        $userId = $this->config->requireUserId();
        $response = $this->http->get(
            "/api/v1/user/{$userId}/bloqs/list/{$itemId}/chat/messages"
        );

        return $response['messages'] ?? $response;
    }

    /**
     * Add a chat message to an item.
     *
     * @param int $itemId Item ID
     * @param array{
     *     role: string,
     *     content: string
     * } $message Message data
     * @return array
     */
    public function addMessage(int $itemId, array $message): array
    {
        $userId = $this->config->requireUserId();
        return $this->http->post(
            "/api/v1/user/{$userId}/bloqs/list/{$itemId}/chat/messages",
            $message
        );
    }

    /**
     * Delete an item.
     *
     * @param int $itemId Item ID
     * @return bool
     */
    public function delete(int $itemId): bool
    {
        $userId = $this->config->requireUserId();
        $this->http->delete("/api/v1/user/{$userId}/bloqs/list/item/{$itemId}");

        return true;
    }

    /**
     * Update an item.
     *
     * @param int $itemId Item ID
     * @param array $data Update data
     * @return BloqItem
     */
    public function update(int $itemId, array $data): BloqItem
    {
        $userId = $this->config->requireUserId();
        $response = $this->http->patch(
            "/api/v1/user/{$userId}/bloqs/list/item/{$itemId}",
            $data
        );

        return new BloqItem($response);
    }

    /**
     * Make an item public.
     *
     * @param int $itemId Item ID
     * @return BloqItem
     */
    public function makePublic(int $itemId): BloqItem
    {
        $userId = $this->config->requireUserId();
        $response = $this->http->post(
            "/api/v1/user/{$userId}/bloqs/list/item/{$itemId}/make-public"
        );

        return new BloqItem($response);
    }

    /**
     * Make an item private.
     *
     * @param int $itemId Item ID
     * @return BloqItem
     */
    public function makePrivate(int $itemId): BloqItem
    {
        $userId = $this->config->requireUserId();
        $response = $this->http->post(
            "/api/v1/user/{$userId}/bloqs/list/item/{$itemId}/make-private"
        );

        return new BloqItem($response);
    }
}
