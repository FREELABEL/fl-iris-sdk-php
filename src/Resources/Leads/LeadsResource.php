<?php

declare(strict_types=1);

namespace IRIS\SDK\Resources\Leads;

use IRIS\SDK\Config;
use IRIS\SDK\Http\Client;

/**
 * Leads Resource
 *
 * Manage sales leads and CRM functionality.
 *
 * @example
 * ```php
 * // Create a lead
 * $lead = $iris->leads->create([
 *     'name' => 'John Doe',
 *     'email' => 'john@example.com',
 *     'company' => 'Acme Corp',
 * ]);
 *
 * // Add an activity
 * $iris->leads->activities($lead->id)->create([
 *     'type' => 'call',
 *     'content' => 'Initial discovery call',
 * ]);
 *
 * // Create a task
 * $iris->leads->tasks($lead->id)->create([
 *     'title' => 'Send proposal',
 *     'due_date' => '2024-01-15',
 * ]);
 * ```
 */
class LeadsResource
{
    protected Client $http;
    protected Config $config;

    public function __construct(Client $http, Config $config)
    {
        $this->http = $http;
        $this->config = $config;
    }

    /**
     * List all leads with optional filters.
     *
     * @param array{
     *     page?: int,
     *     per_page?: int,
     *     search?: string,
     *     stage_id?: int,
     *     tags?: array,
     *     source?: string
     * } $filters Filter options
     * @return LeadCollection
     */
    public function list(array $filters = []): LeadCollection
    {
        $response = $this->http->get("/api/v1/leads", $filters);

        return new LeadCollection(
            array_map(fn($data) => new Lead($data), $response['data'] ?? $response),
            $response['meta'] ?? []
        );
    }

    /**
     * List leads for the current user.
     *
     * @return LeadCollection
     */
    public function listForUser(): LeadCollection
    {
        $userId = $this->config->requireUserId();
        $response = $this->http->get("/api/v1/users/{$userId}/leads");

        return new LeadCollection(
            array_map(fn($data) => new Lead($data), $response['data'] ?? $response),
            $response['meta'] ?? []
        );
    }

    /**
     * Get a specific lead by ID.
     *
     * @param int $leadId Lead ID
     * @return Lead
     */
    public function get(int $leadId): Lead
    {
        $response = $this->http->get("/api/v1/leads/{$leadId}");

        return new Lead($response);
    }

    /**
     * Create a new lead.
     *
     * @param array{
     *     name: string,
     *     email?: string,
     *     phone?: string,
     *     company?: string,
     *     title?: string,
     *     source?: string,
     *     stage_id?: int,
     *     tags?: array,
     *     custom_fields?: array,
     *     notes?: string
     * } $data Lead data
     * @return Lead
     */
    public function create(array $data): Lead
    {
        $response = $this->http->post("/api/v1/leads", $data);

        return new Lead($response);
    }

    /**
     * Update an existing lead.
     *
     * @param int $leadId Lead ID
     * @param array $data Update data
     * @return Lead
     */
    public function update(int $leadId, array $data): Lead
    {
        $response = $this->http->put("/api/v1/leads/{$leadId}", $data);

        return new Lead($response);
    }

    /**
     * Delete a lead.
     *
     * @param int $leadId Lead ID
     * @return bool
     */
    public function delete(int $leadId): bool
    {
        $this->http->delete("/api/v1/leads/{$leadId}");

        return true;
    }

    /**
     * Add a note to a lead.
     *
     * @param int $leadId Lead ID
     * @param string $content Note content
     * @param array $metadata Additional metadata
     * @return array
     */
    public function addNote(int $leadId, string $content, array $metadata = []): array
    {
        return $this->http->post("/api/v1/leads/{$leadId}/notes", array_merge(
            ['content' => $content],
            $metadata
        ));
    }

    /**
     * Generate AI response for a lead.
     *
     * @param int $leadId Lead ID
     * @param string $context Context for the response
     * @return string Generated response
     */
    public function generateResponse(int $leadId, string $context): string
    {
        $response = $this->http->get("/api/v1/leads/{$leadId}/generate-response", [
            'context' => $context,
        ]);

        return $response['response'] ?? '';
    }

    /**
     * Sync Gmail for a lead.
     *
     * @param int $leadId Lead ID
     * @return bool
     */
    public function syncGmail(int $leadId): bool
    {
        $this->http->post("/api/v1/leads/{$leadId}/sync-gmail");

        return true;
    }

    /**
     * Get Gmail thread for a lead.
     *
     * @param int $leadId Lead ID
     * @return array
     */
    public function getGmailThread(int $leadId): array
    {
        return $this->http->get("/api/v1/leads/{$leadId}/gmail-thread");
    }

    /**
     * Get all Gmail threads for a lead.
     *
     * @param int $leadId Lead ID
     * @return array
     */
    public function getGmailThreads(int $leadId): array
    {
        $response = $this->http->get("/api/v1/leads/{$leadId}/gmail-threads");

        return $response['threads'] ?? $response;
    }

    /**
     * Attach a bloq to a lead.
     *
     * @param int $leadId Lead ID
     * @param int $bloqId Bloq ID
     * @return bool
     */
    public function attachBloq(int $leadId, int $bloqId): bool
    {
        $this->http->post("/api/v1/leads/{$leadId}/attach-bloq", ['bloq_id' => $bloqId]);

        return true;
    }

    /**
     * Detach a bloq from a lead.
     *
     * @param int $leadId Lead ID
     * @param int $bloqId Bloq ID
     * @return bool
     */
    public function detachBloq(int $leadId, int $bloqId): bool
    {
        $this->http->post("/api/v1/leads/{$leadId}/detach-bloq", ['bloq_id' => $bloqId]);

        return true;
    }

    /**
     * Set outreach agent for a lead.
     *
     * @param int $leadId Lead ID
     * @param int $agentId Agent ID
     * @return Lead
     */
    public function setOutreachAgent(int $leadId, int $agentId): Lead
    {
        $response = $this->http->patch("/api/v1/leads/{$leadId}/outreach-agent", [
            'agent_id' => $agentId,
        ]);

        return new Lead($response);
    }

    /**
     * Get outreach configuration for a lead.
     *
     * @param int $leadId Lead ID
     * @return array
     */
    public function getOutreachConfig(int $leadId): array
    {
        return $this->http->get("/api/v1/leads/{$leadId}/outreach-config");
    }

    /**
     * Get all lead tags.
     *
     * @return array<LeadTag>
     */
    public function tags(): array
    {
        $userId = $this->config->requireUserId();
        $response = $this->http->get("/api/v1/user/{$userId}/lead-tags");

        return array_map(fn($data) => new LeadTag($data), $response['data'] ?? $response);
    }

    /**
     * Create a new lead tag.
     *
     * @param array{
     *     name: string,
     *     color?: string
     * } $data Tag data
     * @return LeadTag
     */
    public function createTag(array $data): LeadTag
    {
        $userId = $this->config->requireUserId();
        $response = $this->http->post("/api/v1/user/{$userId}/lead-tags", $data);

        return new LeadTag($response);
    }

    /**
     * Update a lead tag.
     *
     * @param int $tagId Tag ID
     * @param array $data Update data
     * @return LeadTag
     */
    public function updateTag(int $tagId, array $data): LeadTag
    {
        $userId = $this->config->requireUserId();
        $response = $this->http->put("/api/v1/user/{$userId}/lead-tags/{$tagId}", $data);

        return new LeadTag($response);
    }

    /**
     * Delete a lead tag.
     *
     * @param int $tagId Tag ID
     * @return bool
     */
    public function deleteTag(int $tagId): bool
    {
        $userId = $this->config->requireUserId();
        $this->http->delete("/api/v1/user/{$userId}/lead-tags/{$tagId}");

        return true;
    }

    /**
     * Get all lead stages.
     *
     * @return array<LeadStage>
     */
    public function stages(): array
    {
        $userId = $this->config->requireUserId();
        $response = $this->http->get("/api/v1/user/{$userId}/lead-stages");

        return array_map(fn($data) => new LeadStage($data), $response['data'] ?? $response);
    }

    /**
     * Create a new lead stage.
     *
     * @param array{
     *     name: string,
     *     color?: string,
     *     position?: int
     * } $data Stage data
     * @return LeadStage
     */
    public function createStage(array $data): LeadStage
    {
        $userId = $this->config->requireUserId();
        $response = $this->http->post("/api/v1/user/{$userId}/lead-stages", $data);

        return new LeadStage($response);
    }

    /**
     * Update a lead stage.
     *
     * @param int $stageId Stage ID
     * @param array $data Update data
     * @return LeadStage
     */
    public function updateStage(int $stageId, array $data): LeadStage
    {
        $userId = $this->config->requireUserId();
        $response = $this->http->put("/api/v1/user/{$userId}/lead-stages/{$stageId}", $data);

        return new LeadStage($response);
    }

    /**
     * Delete a lead stage.
     *
     * @param int $stageId Stage ID
     * @return bool
     */
    public function deleteStage(int $stageId): bool
    {
        $userId = $this->config->requireUserId();
        $this->http->delete("/api/v1/user/{$userId}/lead-stages/{$stageId}");

        return true;
    }

    /**
     * Reorder lead stages.
     *
     * @param array<int> $order Array of stage IDs in desired order
     * @return bool
     */
    public function reorderStages(array $order): bool
    {
        $userId = $this->config->requireUserId();
        $this->http->post("/api/v1/user/{$userId}/lead-stages/update-order", ['order' => $order]);

        return true;
    }

    /**
     * Check if a lead is eligible for outreach.
     *
     * @param int $leadId Lead ID
     * @return array
     */
    public function checkOutreachEligibility(int $leadId): array
    {
        return $this->http->get("/api/v1/leads/{$leadId}/outreach/check");
    }

    /**
     * Record an outreach attempt.
     *
     * @param int $leadId Lead ID
     * @param array{
     *     type: string,
     *     channel: string,
     *     success: bool,
     *     notes?: string
     * } $data Outreach data
     * @return bool
     */
    public function recordOutreach(int $leadId, array $data): bool
    {
        $this->http->post("/api/v1/leads/{$leadId}/outreach/record", $data);

        return true;
    }

    /**
     * Get outreach information for a lead.
     *
     * @param int $leadId Lead ID
     * @return array
     */
    public function getOutreachInfo(int $leadId): array
    {
        return $this->http->get("/api/v1/leads/{$leadId}/outreach/info");
    }

    /**
     * Set auto-respond status for a lead.
     *
     * @param int $leadId Lead ID
     * @param bool $enabled Enable or disable auto-respond
     * @return bool
     */
    public function setAutoRespond(int $leadId, bool $enabled): bool
    {
        $this->http->put("/api/v1/leads/{$leadId}/outreach/auto-respond", [
            'enabled' => $enabled,
        ]);

        return true;
    }

    /**
     * Access activities sub-resource for a lead.
     *
     * @param int $leadId Lead ID
     * @return ActivitiesResource
     */
    public function activities(int $leadId): ActivitiesResource
    {
        return new ActivitiesResource($this->http, $this->config, $leadId);
    }

    /**
     * Access tasks sub-resource for a lead.
     *
     * @param int $leadId Lead ID
     * @return TasksResource
     */
    public function tasks(int $leadId): TasksResource
    {
        return new TasksResource($this->http, $this->config, $leadId);
    }

    /**
     * Get activity types.
     *
     * @return array
     */
    public function activityTypes(): array
    {
        $response = $this->http->get("/api/v1/activities/types");

        return $response['types'] ?? $response;
    }
}
