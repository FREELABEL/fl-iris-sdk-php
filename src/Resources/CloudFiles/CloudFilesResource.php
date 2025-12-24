<?php

declare(strict_types=1);

namespace IRIS\SDK\Resources\CloudFiles;

use IRIS\SDK\Config;
use IRIS\SDK\Http\Client;
use IRIS\SDK\Resources\Bloqs\CloudFile;
use IRIS\SDK\Resources\Bloqs\CloudFileCollection;

/**
 * Cloud Files Resource
 *
 * Manage cloud files across all bloqs.
 *
 * @example
 * ```php
 * // List all files for user
 * $files = $iris->cloudFiles->list();
 *
 * // Upload a file
 * $file = $iris->cloudFiles->upload('/path/to/document.pdf', [
 *     'bloq_id' => 32,
 *     'title' => 'Project Brief',
 * ]);
 *
 * // Get file download URL
 * $url = $iris->cloudFiles->downloadUrl(123);
 * ```
 */
class CloudFilesResource
{
    protected Client $http;
    protected Config $config;

    public function __construct(Client $http, Config $config)
    {
        $this->http = $http;
        $this->config = $config;
    }

    /**
     * List all cloud files for the user.
     *
     * @param array{
     *     bloq_id?: int,
     *     agent_id?: int,
     *     type?: string,
     *     per_page?: int,
     *     page?: int
     * } $options Filter options
     * @return array Files with pagination
     *
     * @example
     * ```php
     * // List all files
     * $files = $iris->cloudFiles->list();
     *
     * // Filter by bloq
     * $files = $iris->cloudFiles->list(['bloq_id' => 32]);
     *
     * // Filter by type
     * $files = $iris->cloudFiles->list(['type' => 'pdf']);
     * ```
     */
    public function list(array $options = []): array
    {
        $userId = $this->config->requireUserId();
        $params = array_merge(['user_id' => $userId], $options);

        return $this->http->get("/api/v1/cloud-files", $params);
    }

    /**
     * Get a specific file by ID.
     *
     * @param int $fileId File ID
     * @return array File details
     */
    public function get(int $fileId): array
    {
        return $this->http->get("/api/v1/cloud-files/{$fileId}");
    }

    /**
     * Upload a new file.
     *
     * @param string $filePath Path to the file
     * @param array{
     *     bloq_id?: int,
     *     agent_id?: int,
     *     title?: string,
     *     description?: string
     * } $options Upload options
     * @return array Uploaded file details
     *
     * @example
     * ```php
     * $file = $iris->cloudFiles->upload('/path/to/resume.pdf', [
     *     'bloq_id' => 32,
     *     'title' => 'John Doe Resume',
     * ]);
     * ```
     */
    public function upload(string $filePath, array $options = []): array
    {
        // Ensure user_id is included in the upload (required by FL-API)
        if (!isset($options['user_id']) && $this->config->userId) {
            $options['user_id'] = $this->config->userId;
        }

        return $this->http->upload("/api/v1/cloud-files/upload", $filePath, $options);
    }

    /**
     * Update file metadata.
     *
     * @param int $fileId File ID
     * @param array $data Update data
     * @return array Updated file
     */
    public function update(int $fileId, array $data): array
    {
        return $this->http->put("/api/v1/cloud-files/{$fileId}", $data);
    }

    /**
     * Delete a file.
     *
     * @param int $fileId File ID
     * @return bool
     */
    public function delete(int $fileId): bool
    {
        $this->http->delete("/api/v1/cloud-files/{$fileId}");
        return true;
    }

    /**
     * Get download URL for a file.
     *
     * @param int $fileId File ID
     * @return string Download URL
     */
    public function downloadUrl(int $fileId): string
    {
        $response = $this->http->get("/api/v1/cloud-files/{$fileId}/download");
        return $response['url'] ?? '';
    }

    /**
     * Get file processing status.
     *
     * @param int $fileId File ID
     * @return array Status info (processing, ready, failed)
     */
    public function status(int $fileId): array
    {
        return $this->http->get("/api/v1/cloud-files/{$fileId}/status");
    }

    /**
     * Get extracted content from a file.
     *
     * For PDFs and documents, returns the extracted text content.
     *
     * @param int $fileId File ID
     * @return array Content and metadata
     */
    public function content(int $fileId): array
    {
        return $this->http->get("/api/v1/cloud-files/{$fileId}/content");
    }

    /**
     * Get supported file types.
     *
     * @return array List of supported MIME types and extensions
     */
    public function supportedTypes(): array
    {
        $response = $this->http->get("/api/v1/cloud-files/supported-types");
        return $response['types'] ?? $response;
    }

    /**
     * Get files for a specific bloq.
     *
     * @param int $bloqId Bloq ID
     * @param array $options Filter options
     * @return array Files for the bloq
     */
    public function forBloq(int $bloqId, array $options = []): array
    {
        return $this->http->get("/api/v1/bloqs/{$bloqId}/files", $options);
    }

    /**
     * Get files for a specific agent.
     *
     * @param int $agentId Agent ID
     * @param array $options Filter options
     * @return array Files attached to the agent
     */
    public function forAgent(int $agentId, array $options = []): array
    {
        return $this->http->get("/api/v1/agents/{$agentId}/files", $options);
    }

    /**
     * Attach a file to an agent for RAG.
     *
     * @param int $fileId File ID
     * @param int $agentId Agent ID
     * @return array Result
     */
    public function attachToAgent(int $fileId, int $agentId): array
    {
        return $this->http->post("/api/v1/cloud-files/{$fileId}/attach-agent", [
            'agent_id' => $agentId,
        ]);
    }

    /**
     * Detach a file from an agent.
     *
     * @param int $fileId File ID
     * @param int $agentId Agent ID
     * @return bool
     */
    public function detachFromAgent(int $fileId, int $agentId): bool
    {
        $this->http->post("/api/v1/cloud-files/{$fileId}/detach-agent", [
            'agent_id' => $agentId,
        ]);
        return true;
    }

    /**
     * Re-index a file for vector search.
     *
     * @param int $fileId File ID
     * @return array Indexing status
     */
    public function reindex(int $fileId): array
    {
        return $this->http->post("/api/v1/cloud-files/{$fileId}/reindex", []);
    }

    /**
     * Upload a file and format it for agent attachment.
     *
     * This is a convenience method that uploads a file and returns the data
     * in the format needed for the agent's fileAttachments array.
     *
     * @param string $filePath Path to the file
     * @param int $bloqId Bloq ID to upload to
     * @param array{
     *     title?: string,
     *     description?: string
     * } $options Upload options
     * @return array File attachment data ready for agent update
     *
     * @example
     * ```php
     * // Upload file and get attachment data
     * $attachment = $iris->cloudFiles->uploadForAgent('/path/to/data.csv', 40, [
     *     'title' => 'Training Data',
     *     'description' => 'Agent training document'
     * ]);
     *
     * // Returns data like:
     * // [
     * //     'cloud_file_id' => 336,
     * //     'name' => 'data.csv',
     * //     'size' => 38936,
     * //     'type' => 'text/csv',
     * //     'filepath' => 'https://...',
     * //     'processingStatus' => 'completed',
     * //     'uploadedAt' => '2025-12-23T04:57:31.844Z'
     * // ]
     * ```
     */
    public function uploadForAgent(string $filePath, int $bloqId, array $options = []): array
    {
        $userId = $this->config->requireUserId();

        // Set default title from filename if not provided
        $filename = basename($filePath);
        $options['title'] = $options['title'] ?? $filename;
        $options['description'] = $options['description'] ?? 'Agent training document';
        $options['bloq_id'] = $bloqId;
        $options['user_id'] = $userId;

        // Upload the file
        $result = $this->upload($filePath, $options);

        // Format for agent's fileAttachments array
        return [
            'cloud_file_id' => $result['id'] ?? $result['cloud_file_id'],
            'name' => $result['name'] ?? $result['title'] ?? $filename,
            'size' => $result['size'] ?? filesize($filePath),
            'type' => $result['mime_type'] ?? $result['type'] ?? mime_content_type($filePath),
            'filepath' => $result['url'] ?? $result['filepath'] ?? '',
            'processingStatus' => $result['processing_status'] ?? $result['status'] ?? 'completed',
            'uploadedAt' => $result['created_at'] ?? date('c'),
        ];
    }

    /**
     * Upload multiple files for agent attachment.
     *
     * @param array $files Array of file paths
     * @param int $bloqId Bloq ID to upload to
     * @param array $options Options applied to all files
     * @return array Array of file attachment data
     *
     * @example
     * ```php
     * $attachments = $iris->cloudFiles->uploadMultipleForAgent([
     *     '/path/to/file1.pdf',
     *     '/path/to/file2.csv',
     * ], 40);
     * ```
     */
    public function uploadMultipleForAgent(array $files, int $bloqId, array $options = []): array
    {
        $attachments = [];
        foreach ($files as $filePath) {
            $attachments[] = $this->uploadForAgent($filePath, $bloqId, $options);
        }
        return $attachments;
    }
}
