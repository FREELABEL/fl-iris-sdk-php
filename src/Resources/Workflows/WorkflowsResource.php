<?php

declare(strict_types=1);

namespace IRIS\SDK\Resources\Workflows;

use IRIS\SDK\Config;
use IRIS\SDK\Http\Client;

/**
 * Workflows Resource
 *
 * Execute V5 multi-step workflows with real-time progress tracking
 * and human-in-the-loop support.
 *
 * @example
 * ```php
 * // Execute a workflow
 * $workflow = $fl->workflows->execute([
 *     'agent_id' => 'agent_123',
 *     'query' => 'Research competitors and create a report',
 * ]);
 *
 * // Track progress
 * foreach ($workflow->steps() as $step) {
 *     echo "[{$step->progress}%] {$step->description}\n";
 * }
 *
 * // Get final result
 * $result = $workflow->result();
 * ```
 */
class WorkflowsResource
{
    protected Client $http;
    protected Config $config;

    public function __construct(Client $http, Config $config)
    {
        $this->http = $http;
        $this->config = $config;
    }

    /**
     * Execute a workflow.
     *
     * @param array{
     *     agent_id?: int|string,
     *     workflow_id?: int,
     *     query: string,
     *     bloq_id?: int,
     *     conversation_history?: array,
     *     require_approval?: bool,
     *     variables?: array,
     *     metadata?: array
     * } $params Workflow parameters
     * @return WorkflowRun
     */
    public function execute(array $params): WorkflowRun
    {
        $userId = $this->config->requireUserId();

        $response = $this->http->post("/api/v1/users/{$userId}/bloqs/workflow-runs", [
            'agent_id' => $params['agent_id'] ?? null,
            'workflow_id' => $params['workflow_id'] ?? null,
            'query' => $params['query'],
            'bloq_id' => $params['bloq_id'] ?? null,
            'conversation_history' => $params['conversation_history'] ?? [],
            'require_approval' => $params['require_approval'] ?? false,
            'variables' => $params['variables'] ?? [],
            'metadata' => $params['metadata'] ?? [],
        ]);

        return new WorkflowRun($response, $this->http, $this->config);
    }

    /**
     * Get the status of a workflow run.
     *
     * @param string $runId Workflow run ID
     * @return WorkflowStatus
     */
    public function getStatus(string $runId): WorkflowStatus
    {
        $userId = $this->config->requireUserId();
        $response = $this->http->get("/api/v1/users/{$userId}/bloqs/workflow-runs/{$runId}");

        return new WorkflowStatus($response);
    }

    /**
     * Continue a paused workflow (after human input).
     *
     * @param string $runId Workflow run ID
     * @param array $input Input data to continue with
     * @return WorkflowRun
     */
    public function continue(string $runId, array $input = []): WorkflowRun
    {
        $response = $this->http->post("/api/v1/bloqs/workflow-runs/{$runId}/continue", [
            'input' => $input,
        ]);

        return new WorkflowRun($response, $this->http, $this->config);
    }

    /**
     * Process a specific workflow step.
     *
     * @param string $runId Workflow run ID
     * @param int $stepIndex Step index to process
     * @param array $data Step data
     * @return WorkflowStatus
     */
    public function processStep(string $runId, int $stepIndex, array $data = []): WorkflowStatus
    {
        $response = $this->http->post("/api/v1/bloqs/workflow-runs/{$runId}/process-step", [
            'step_index' => $stepIndex,
            'data' => $data,
        ]);

        return new WorkflowStatus($response);
    }

    /**
     * Complete a human task.
     *
     * @param string $taskId Human task ID
     * @param array{
     *     approved?: bool,
     *     feedback?: string,
     *     data?: array
     * } $response Human response
     * @return bool
     */
    public function completeTask(string $taskId, array $response): bool
    {
        $this->http->post("/api/v1/bloqs/workflow-human-tasks/{$taskId}/complete", $response);
        return true;
    }

    /**
     * Get a human task.
     *
     * @param string $taskId Human task ID
     * @return HumanTask
     */
    public function getTask(string $taskId): HumanTask
    {
        $response = $this->http->get("/api/v1/bloqs/workflow-human-tasks/{$taskId}");
        return new HumanTask($response);
    }

    /**
     * Generate a workflow from natural language description.
     *
     * @param string $description Natural language workflow description
     * @param array{
     *     agent_id?: int,
     *     bloq_id?: int,
     *     template_hints?: array
     * } $options Generation options
     * @return Workflow
     */
    public function generate(string $description, array $options = []): Workflow
    {
        $userId = $this->config->requireUserId();

        $response = $this->http->post("/api/v1/users/{$userId}/bloqs/workflows/generate", [
            'description' => $description,
            'agent_id' => $options['agent_id'] ?? null,
            'bloq_id' => $options['bloq_id'] ?? null,
            'template_hints' => $options['template_hints'] ?? [],
        ]);

        return new Workflow($response);
    }

    /**
     * Generate workflow with multi-agent team.
     *
     * @param string $description Workflow description
     * @param array $options Options
     * @return Workflow
     */
    public function generateWithAgents(string $description, array $options = []): Workflow
    {
        $response = $this->http->post('/api/v1/bloqs/workflows/generate-with-agents', [
            'description' => $description,
            'bloq_id' => $options['bloq_id'] ?? null,
            'agent_count' => $options['agent_count'] ?? 3,
        ]);

        return new Workflow($response);
    }

    /**
     * Generate clarifying questions for workflow.
     *
     * @param string $description Workflow description
     * @return array Questions
     */
    public function generateQuestions(string $description): array
    {
        return $this->http->post('/api/v1/bloqs/workflows/generate-questions', [
            'description' => $description,
        ]);
    }

    /**
     * List workflow templates.
     *
     * @param array{
     *     category?: string,
     *     search?: string,
     *     featured?: bool
     * } $filters Filter options
     * @return TemplateCollection
     */
    public function templates(array $filters = []): TemplateCollection
    {
        $endpoint = '/api/v1/templates';

        if (!empty($filters['featured'])) {
            $endpoint = '/api/v1/templates/featured';
            unset($filters['featured']);
        }

        $response = $this->http->get($endpoint, $filters);

        return new TemplateCollection(
            array_map(fn($data) => new WorkflowTemplate($data), $response['data'] ?? $response),
            $response['meta'] ?? []
        );
    }

    /**
     * Get template categories.
     *
     * @return array Categories
     */
    public function templateCategories(): array
    {
        return $this->http->get('/api/v1/templates/categories');
    }

    /**
     * Import a workflow template.
     *
     * @param string $slug Template slug
     * @param array $variables Template variables
     * @return Workflow
     */
    public function importTemplate(string $slug, array $variables = []): Workflow
    {
        $response = $this->http->post("/api/v1/templates/{$slug}/import", [
            'variables' => $variables,
        ]);

        return new Workflow($response);
    }

    /**
     * List user's workflows.
     *
     * @param array $options List options
     * @return WorkflowCollection
     */
    public function list(array $options = []): WorkflowCollection
    {
        $userId = $this->config->requireUserId();
        $response = $this->http->get("/api/v1/users/{$userId}/bloqs/workflows", $options);

        return new WorkflowCollection(
            array_map(fn($data) => new Workflow($data), $response['data'] ?? $response),
            $response['meta'] ?? []
        );
    }

    /**
     * List workflow runs for a user.
     *
     * @param array{
     *     status?: string,
     *     workflow_id?: int,
     *     page?: int,
     *     per_page?: int
     * } $options Filter options
     * @return WorkflowRunCollection
     */
    public function runs(array $options = []): WorkflowRunCollection
    {
        $userId = $this->config->requireUserId();
        $response = $this->http->get("/api/v1/users/{$userId}/bloqs/workflow-runs", $options);

        return new WorkflowRunCollection(
            array_map(fn($data) => new WorkflowRun($data, $this->http, $this->config), $response['data'] ?? $response),
            $response['meta'] ?? []
        );
    }

    /**
     * Get workflow run logs.
     *
     * @param string $runId Workflow run ID
     * @return array Logs
     */
    public function getLogs(string $runId): array
    {
        $userId = $this->config->requireUserId();
        return $this->http->get("/api/v1/users/{$userId}/bloqs/workflow-runs/{$runId}/logs");
    }

    /**
     * Generate a webhook URL for a workflow.
     *
     * @param int $workflowId Workflow ID
     * @return array{url: string, secret: string}
     */
    public function generateWebhook(int $workflowId): array
    {
        return $this->http->post("/api/v1/bloqs/workflow/{$workflowId}/webhook-url");
    }
}
