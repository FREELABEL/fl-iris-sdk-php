<?php

declare(strict_types=1);

namespace IRIS\SDK\Resources\Leads;

use IRIS\SDK\Config;
use IRIS\SDK\Http\Client;

/**
 * Lead Deliverables Resource
 *
 * Manage deliverables (files and links) for leads.
 *
 * @example
 * ```php
 * // List deliverables
 * $deliverables = $iris->leads->deliverables(123)->list();
 *
 * // Add external link deliverable
 * $deliverable = $iris->leads->deliverables(123)->create([
 *     'type' => 'link',
 *     'title' => 'Trained AI Agent',
 *     'external_url' => 'https://app.heyiris.io/agents/456',
 * ]);
 *
 * // Upload file deliverable
 * $deliverable = $iris->leads->deliverables(123)->createFile([
 *     'title' => 'Monthly Report',
 *     'file_path' => '/path/to/report.pdf',
 * ]);
 * ```
 */
class DeliverablesResource
{
    protected Client $http;
    protected Config $config;
    protected int $leadId;

    public function __construct(Client $http, Config $config, int $leadId)
    {
        $this->http = $http;
        $this->config = $config;
        $this->leadId = $leadId;
    }

    /**
     * List all deliverables for the lead.
     *
     * @return array
     *
     * @example
     * ```php
     * $deliverables = $iris->leads->deliverables(123)->list();
     *
     * foreach ($deliverables as $deliverable) {
     *     echo "{$deliverable['title']} ({$deliverable['type']})\n";
     *     echo "  URL: {$deliverable['url']}\n";
     * }
     * ```
     */
    public function list(): array
    {
        $response = $this->http->get("/api/v1/leads/{$this->leadId}/deliverables");

        return $response['deliverables'] ?? [];
    }

    /**
     * Create a new deliverable (external link or file upload).
     *
     * @param array $data Deliverable data
     *   - type: 'link' or 'file' (required)
     *   - title: Deliverable title (required)
     *   - external_url: URL for link type (required if type=link)
     *   - file: File resource for file type (required if type=file)
     *   - custom_request_id: Optional invoice/custom request ID
     *
     * @return array Created deliverable
     *
     * @example
     * ```php
     * // Create link deliverable
     * $deliverable = $iris->leads->deliverables(123)->create([
     *     'type' => 'link',
     *     'title' => 'Trained AI Agent Dashboard',
     *     'external_url' => 'https://app.heyiris.io/agents/456',
     * ]);
     *
     * // Create with invoice link
     * $deliverable = $iris->leads->deliverables(123)->create([
     *     'type' => 'link',
     *     'title' => 'December Newsletter',
     *     'external_url' => 'https://example.com/newsletter',
     *     'custom_request_id' => 789, // Link to invoice
     * ]);
     * ```
     */
    public function create(array $data): array
    {
        $response = $this->http->post("/api/v1/leads/{$this->leadId}/deliverables", $data);

        return $response['data']['deliverable'] ?? $response;
    }

    /**
     * Upload a file as a deliverable.
     *
     * @param string $filePath Absolute path to the file
     * @param array $options Additional options
     *   - title: Deliverable title (defaults to filename)
     *   - custom_request_id: Optional invoice/custom request ID
     *
     * @return array Created deliverable
     *
     * @example
     * ```php
     * // Upload file
     * $deliverable = $iris->leads->deliverables(123)->uploadFile(
     *     '/path/to/report.pdf',
     *     ['title' => 'Q4 2025 Report']
     * );
     * ```
     */
    public function uploadFile(string $filePath, array $options = []): array
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        $data = [
            'type' => 'file',
            'title' => $options['title'] ?? basename($filePath),
            'file' => new \CURLFile($filePath),
        ];

        if (isset($options['custom_request_id'])) {
            $data['custom_request_id'] = $options['custom_request_id'];
        }

        $response = $this->http->post("/api/v1/leads/{$this->leadId}/deliverables", $data);

        return $response['data']['deliverable'] ?? $response;
    }

    /**
     * Update a deliverable.
     *
     * @param int $deliverableId Deliverable ID
     * @param array $data Update data
     *   - title: New title
     *   - external_url: New URL (for link type only)
     *
     * @return array Updated deliverable
     *
     * @example
     * ```php
     * $deliverable = $iris->leads->deliverables(123)->update(456, [
     *     'title' => 'Updated AI Agent Dashboard',
     *     'external_url' => 'https://app.heyiris.io/agents/789',
     * ]);
     * ```
     */
    public function update(int $deliverableId, array $data): array
    {
        $response = $this->http->patch(
            "/api/v1/leads/{$this->leadId}/deliverables/{$deliverableId}",
            $data
        );

        return $response['data']['deliverable'] ?? $response;
    }

    /**
     * Delete a deliverable.
     *
     * @param int $deliverableId Deliverable ID
     * @return bool
     *
     * @example
     * ```php
     * $success = $iris->leads->deliverables(123)->delete(456);
     * ```
     */
    public function delete(int $deliverableId): bool
    {
        $response = $this->http->delete("/api/v1/leads/{$this->leadId}/deliverables/{$deliverableId}");

        return $response['success'] ?? false;
    }

    /**
     * Send deliverable email notification to the lead.
     *
     * @param array $options Email options
     *   - deliverable_ids: Array of deliverable IDs to send
     *   - subject: Optional custom email subject
     *   - message: Optional custom email message
     *
     * @return array Email send result
     *
     * @example
     * ```php
     * // Send specific deliverables
     * $result = $iris->leads->deliverables(123)->send([
     *     'deliverable_ids' => [456, 789],
     *     'subject' => 'Your AI Agent is Ready!',
     *     'message' => 'Here are your deliverables...',
     * ]);
     * ```
     */
    public function send(array $options = []): array
    {
        $response = $this->http->post("/api/v1/leads/{$this->leadId}/deliverables/send", $options);

        return $response['data'] ?? $response;
    }
}
