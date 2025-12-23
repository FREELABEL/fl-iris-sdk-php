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
     * Search leads for the current user with advanced filters.
     *
     * @param array{
     *     search?: string,
     *     bloq_id?: int,
     *     status?: string,
     *     lead_type?: string,
     *     page?: int,
     *     per_page?: int,
     *     sort?: string,
     *     order?: string,
     *     include_notes?: bool,
     *     include_events?: bool
     * } $filters Search and filter options
     * @return array Raw API response with leads and metadata
     *
     * @example
     * ```php
     * // Search for leads by name
     * $results = $iris->leads->search(['search' => 'ayala']);
     *
     * // Search with bloq filter and pagination
     * $results = $iris->leads->search([
     *     'bloq_id' => 40,
     *     'search' => 'john',
     *     'status' => 'Won',
     *     'per_page' => 50,
     *     'include_notes' => true,
     *     'include_events' => true
     * ]);
     *
     * foreach ($results['data'] as $lead) {
     *     echo "{$lead['name']} - {$lead['status']}\n";
     * }
     * ```
     */
    public function search(array $filters = []): array
    {
        $userId = $this->config->requireUserId();
        
        // Convert boolean values to strings for query parameters
        if (isset($filters['include_notes'])) {
            $filters['include_notes'] = $filters['include_notes'] ? 'true' : 'false';
        }
        if (isset($filters['include_events'])) {
            $filters['include_events'] = $filters['include_events'] ? 'true' : 'false';
        }
        
        $response = $this->http->get("/api/v1/users/{$userId}/leads", $filters);
        
        return $response;
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
            ['message' => $content],
            $metadata
        ));
    }

    /**
     * Delete a note from a lead.
     *
     * @param int $leadId Lead ID
     * @param int $noteId Note ID
     * @return bool
     */
    public function deleteNote(int $leadId, int $noteId): bool
    {
        $this->http->delete("/api/v1/webhooks/leads/{$leadId}/notes/{$noteId}");
        
        return true;
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
     * Access deliverables sub-resource for a lead.
     *
     * @param int $leadId Lead ID
     * @return DeliverablesResource
     *
     * @example
     * ```php
     * // List deliverables
     * $deliverables = $iris->leads->deliverables(123)->list();
     *
     * // Add agent link
     * $deliverable = $iris->leads->deliverables(123)->create([
     *     'type' => 'link',
     *     'title' => 'Trained AI Agent',
     *     'external_url' => 'https://app.heyiris.io/agents/456',
     * ]);
     * ```
     */
    public function deliverables(int $leadId): DeliverablesResource
    {
        return new DeliverablesResource($this->http, $this->config, $leadId);
    }

    /**
     * Access lead aggregation sub-resource.
     *
     * Get aggregated lead data for autonomous AI agent pipeline.
     *
     * @return LeadAggregationResource
     *
     * @example
     * ```php
     * // Get statistics
     * $stats = $iris->leads->aggregation()->statistics();
     * echo "Incomplete tasks: {$stats['incomplete_tasks']}\n";
     *
     * // List high-priority leads
     * $leads = $iris->leads->aggregation()->list([
     *     'has_incomplete_tasks' => 1,
     *     'min_priority' => 50,
     * ]);
     *
     * // Get specific lead with aggregated data
     * $lead = $iris->leads->aggregation()->get(123);
     * ```
     */
    public function aggregation(): LeadAggregationResource
    {
        return new LeadAggregationResource($this->http, $this->config);
    }

    /**
     * Access outreach sub-resource for a lead.
     *
     * Generate AI-powered emails and send outreach to leads.
     *
     * @param int $leadId Lead ID
     * @return OutreachResource
     *
     * @example
     * ```php
     * // Generate an email draft
     * $draft = $iris->leads->outreach(123)->generateEmail(
     *     'Follow up on our meeting last week',
     *     ['tone' => 'professional']
     * );
     *
     * // Send the email
     * $result = $iris->leads->outreach(123)->sendEmail([
     *     'to_email' => 'john@example.com',
     *     'subject' => $draft['draft']['subject'],
     *     'body_html' => $draft['draft']['body'],
     * ]);
     *
     * // Or generate and send in one step
     * $result = $iris->leads->outreach(123)->generateAndSend(
     *     'john@example.com',
     *     'Initial cold outreach'
     * );
     * ```
     */
    public function outreach(int $leadId): OutreachResource
    {
        return new OutreachResource($this->http, $this->config, $leadId);
    }

    /**
     * Access outreach steps sub-resource for a lead.
     *
     * Manage the outreach checklist/strategy for engaging with leads.
     *
     * @param int $leadId Lead ID
     * @return OutreachStepsResource
     *
     * @example
     * ```php
     * // List all steps
     * $result = $iris->leads->outreachSteps(123)->list();
     * echo "Progress: {$result['data']['stats']['progress_percent']}%\n";
     *
     * // Create a step
     * $step = $iris->leads->outreachSteps(123)->create([
     *     'title' => 'Send introduction email',
     *     'type' => 'email',
     * ]);
     *
     * // Mark step as completed
     * $iris->leads->outreachSteps(123)->complete($stepId, 'Email sent successfully');
     *
     * // Initialize default strategy
     * $iris->leads->outreachSteps(123)->initializeDefault();
     * ```
     */
    public function outreachSteps(int $leadId): OutreachStepsResource
    {
        return new OutreachStepsResource($this->http, $this->config, $leadId);
    }

    /**
     * Access invoices sub-resource for a lead.
     *
     * Create, manage, and send invoices associated with leads.
     *
     * @param int $leadId Lead ID
     * @return InvoicesResource
     *
     * @example
     * ```php
     * // List invoices for a lead
     * $invoices = $iris->leads->invoices(16)->list();
     *
     * // Create an invoice
     * $invoice = $iris->leads->invoices(16)->create([
     *     'price' => 25000,  // $250.00
     *     'description' => 'AI Agent Development',
     * ]);
     *
     * // Send invoice to lead
     * $result = $iris->leads->invoices(16)->send($invoice['id'], [
     *     'subject' => 'Invoice for your project',
     * ]);
     *
     * // Mark as paid
     * $iris->leads->invoices(16)->markPaid($invoice['id']);
     * ```
     */
    public function invoices(int $leadId): InvoicesResource
    {
        return new InvoicesResource($this->http, $this->config, $leadId);
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

    /**
     * Enrich a lead with external data.
     *
     * Uses AI and external sources to gather additional information
     * about the lead (company info, social profiles, etc.)
     *
     * @param int $leadId Lead ID
     * @param array{
     *     auto_update?: bool,
     *     sources?: array
     * } $options Enrichment options
     * @return array Enrichment result
     *
     * @example
     * ```php
     * // Enrich lead without auto-updating
     * $result = $iris->leads->enrich(17, ['auto_update' => false]);
     *
     * // Enrich and auto-update lead fields
     * $result = $iris->leads->enrich(17, ['auto_update' => true]);
     * ```
     */
    public function enrich(int $leadId, array $options = []): array
    {
        return $this->http->post("/api/v1/leads/{$leadId}/enrich", $options);
    }

    /**
     * Get enrichment status for a lead.
     *
     * Check if enrichment is in progress, completed, or has new data available.
     *
     * @param int $leadId Lead ID
     * @return array Enrichment status
     *
     * @example
     * ```php
     * $status = $iris->leads->enrichmentStatus(17);
     *
     * if ($status['status'] === 'completed') {
     *     echo "Enrichment complete! Found {$status['fields_enriched']} fields\n";
     * }
     * ```
     */
    public function enrichmentStatus(int $leadId): array
    {
        return $this->http->get("/api/v1/leads/{$leadId}/enrichment-status");
    }

    /**
     * Parse a lead description using AI to extract structured data.
     *
     * This endpoint uses AI to parse freeform text and extract lead information
     * like name, email, phone, company, budget, notes, and suggested tags.
     *
     * @param string $description Freeform text description of the lead
     * @param int $bloqId Bloq ID where the lead will be added
     * @param array{
     *     available_tags?: array,
     *     lifecycle_stages?: array,
     *     check_duplicates?: bool,
     *     enhance_notes?: bool,
     *     use_existing_tags_only?: bool,
     *     images?: array
     * } $options Parsing options
     * @return array Parsed lead data ready for create()
     *
     * @example
     * ```php
     * // Parse a lead from natural language
     * $parsed = $iris->leads->parseDescription(
     *     "David Park, freelance consultant, david.park@gmail.com, (555) 123-9876.
     *      Needs professional headshots. Budget $500-$1500.",
     *     40,
     *     [
     *         'check_duplicates' => true,
     *         'enhance_notes' => true,
     *     ]
     * );
     *
     * // Result contains structured data:
     * // $parsed['name'] = 'David Park'
     * // $parsed['email'] = 'david.park@gmail.com'
     * // $parsed['phone'] = '5551239876'
     * // $parsed['price_min'] = 500
     * // $parsed['price_max'] = 1500
     * // $parsed['notes'] = 'Enhanced notes...'
     * // $parsed['tags'] = [5, 1, 3]
     *
     * // Then create the lead
     * $lead = $iris->leads->create($parsed);
     * ```
     */
    public function parseDescription(string $description, int $bloqId, array $options = []): array
    {
        $userId = $this->config->requireUserId();

        $data = array_merge([
            'description' => $description,
            'bloq_id' => (string) $bloqId,
            'user_id' => $userId,
            'check_duplicates' => $options['check_duplicates'] ?? true,
            'enhance_notes' => $options['enhance_notes'] ?? true,
            'use_existing_tags_only' => $options['use_existing_tags_only'] ?? false,
            'images' => $options['images'] ?? null,
        ], $options);

        return $this->http->post("/api/v1/openai/process-lead-description", $data);
    }

    /**
     * Parse a lead description and create the lead in one step.
     *
     * Convenience method that combines parseDescription() and create().
     *
     * @param string $description Freeform text description
     * @param int $bloqId Bloq ID
     * @param array $options Parsing and creation options
     * @return Lead Created lead
     *
     * @example
     * ```php
     * // Create lead from natural language in one call
     * $lead = $iris->leads->createFromDescription(
     *     "John Smith, CEO of TechCorp, john@techcorp.com, interested in AI agents. Budget 5k-10k.",
     *     40
     * );
     *
     * echo "Created lead: {$lead->name} ({$lead->email})\n";
     * ```
     */
    public function createFromDescription(string $description, int $bloqId, array $options = []): Lead
    {
        // Parse the description
        $parsed = $this->parseDescription($description, $bloqId, $options);

        // Ensure bloqId is set
        $parsed['bloqId'] = (string) $bloqId;
        $parsed['user_id'] = $this->config->requireUserId();

        // Create the lead
        return $this->create($parsed);
    }

    /**
     * Get available tags for a bloq.
     *
     * Useful for providing available_tags to parseDescription().
     *
     * @param int $bloqId Bloq ID
     * @return array List of available tags with id, name, color
     */
    public function getAvailableTags(int $bloqId): array
    {
        $userId = $this->config->requireUserId();
        return $this->http->get("/api/v1/users/{$userId}/bloqs/{$bloqId}/tags");
    }

    /**
     * Get lifecycle stages for leads.
     *
     * Useful for providing lifecycle_stages to parseDescription().
     *
     * @return array List of lifecycle stages with id, name, color
     */
    public function getLifecycleStages(): array
    {
        return $this->http->get("/api/v1/leads/lifecycle-stages");
    }

    /**
     * Check for duplicate leads.
     *
     * @param string $email Email to check
     * @param int $bloqId Bloq ID
     * @return array Duplicate check result
     */
    public function checkDuplicate(string $email, int $bloqId): array
    {
        $userId = $this->config->requireUserId();
        return $this->http->post("/api/v1/leads/check-duplicate", [
            'email' => $email,
            'bloq_id' => $bloqId,
            'user_id' => $userId,
        ]);
    }

    /**
     * Bulk create leads from descriptions.
     *
     * Parse and create multiple leads from an array of descriptions.
     *
     * @param array $descriptions Array of description strings
     * @param int $bloqId Bloq ID
     * @param array $options Parsing options
     * @return array Results with created leads and any errors
     *
     * @example
     * ```php
     * $results = $iris->leads->bulkCreateFromDescriptions([
     *     "John Doe, john@example.com, needs website redesign",
     *     "Jane Smith, jane@corp.com, interested in AI agents",
     *     "Bob Wilson, bob@startup.io, mobile app development",
     * ], 40);
     *
     * echo "Created {$results['success_count']} leads\n";
     * if ($results['errors']) {
     *     echo "Errors: " . count($results['errors']) . "\n";
     * }
     * ```
     */
    public function bulkCreateFromDescriptions(array $descriptions, int $bloqId, array $options = []): array
    {
        $results = [
            'leads' => [],
            'errors' => [],
            'success_count' => 0,
            'error_count' => 0,
        ];

        foreach ($descriptions as $index => $description) {
            try {
                $lead = $this->createFromDescription($description, $bloqId, $options);
                $results['leads'][] = $lead;
                $results['success_count']++;
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'index' => $index,
                    'description' => substr($description, 0, 100) . '...',
                    'error' => $e->getMessage(),
                ];
                $results['error_count']++;
            }
        }

        return $results;
    }
}
