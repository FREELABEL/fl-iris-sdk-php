<?php

namespace IRIS\SDK\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Style\SymfonyStyle;
use IRIS\SDK\IRIS;
use IRIS\SDK\Config;

/**
 * ScheduleCommand - Manage agent scheduled jobs and view execution history
 *
 * Commands:
 *   iris schedule:list [--agent-id=X]     - List scheduled jobs
 *   iris schedule:create <agent-id>       - Create a new scheduled job
 *   iris schedule:run <job-id>            - Run a job immediately
 *   iris schedule:history <job-id>        - View execution history
 *   iris schedule:agent-history <agent>   - View all executions for an agent
 */
class ScheduleCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('schedule')
            ->setDescription('Manage agent scheduled jobs and execution history')
            ->setHelp('Commands: list, create, run, history, agent-history')
            ->addArgument('action', InputArgument::REQUIRED, 'Action: list|create|run|history|agent-history')
            ->addArgument('id', InputArgument::OPTIONAL, 'Job ID or Agent ID (depending on action)')
            ->addOption('agent-id', 'a', InputOption::VALUE_REQUIRED, 'Filter by agent ID')
            ->addOption('task-name', 't', InputOption::VALUE_REQUIRED, 'Task name (for create)')
            ->addOption('prompt', 'p', InputOption::VALUE_REQUIRED, 'Prompt/task description (for create)')
            ->addOption('time', null, InputOption::VALUE_REQUIRED, 'Scheduled time HH:MM (for create)', '09:00')
            ->addOption('frequency', 'f', InputOption::VALUE_REQUIRED, 'Frequency: daily|weekly|monthly|once', 'daily')
            ->addOption('status', 's', InputOption::VALUE_REQUIRED, 'Filter by status: completed|failed')
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Limit results', 20)
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output as JSON')
            ->addOption('api-key', null, InputOption::VALUE_REQUIRED, 'API key')
            ->addOption('user-id', null, InputOption::VALUE_REQUIRED, 'User ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $action = $input->getArgument('action');

        try {
            $configOptions = [];
            if ($apiKey = $input->getOption('api-key')) {
                $configOptions['api_key'] = $apiKey;
            }
            if ($userId = $input->getOption('user-id')) {
                $configOptions['user_id'] = (int) $userId;
            }

            $sdkConfig = new Config($configOptions);
            $iris = new IRIS($configOptions);

            switch ($action) {
                case 'list':
                    return $this->listJobs($iris, $input, $io);

                case 'create':
                    return $this->createJob($iris, $input, $io);

                case 'run':
                    return $this->runJob($iris, $input, $io);

                case 'history':
                    return $this->jobHistory($iris, $input, $io);

                case 'agent-history':
                    return $this->agentHistory($iris, $input, $io);

                default:
                    $io->error("Unknown action: {$action}. Use: list|create|run|history|agent-history");
                    return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    protected function listJobs(IRIS $iris, InputInterface $input, SymfonyStyle $io): int
    {
        $params = [];

        if ($agentId = $input->getOption('agent-id')) {
            $params['agent_id'] = $agentId;
        }

        $params['agent_jobs_only'] = true;

        $response = $iris->schedules()->list($params);
        $jobs = $response['data'] ?? [];

        if ($input->getOption('json')) {
            $io->writeln(json_encode($jobs, JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        if (empty($jobs)) {
            $io->info('No scheduled jobs found.');
            return Command::SUCCESS;
        }

        $io->title('Scheduled Jobs');

        $table = new Table($io);
        $table->setHeaders(['ID', 'Agent', 'Task', 'Frequency', 'Status', 'Next Run', 'Run Count']);

        foreach ($jobs as $job) {
            $table->addRow([
                $job['id'],
                $job['agent']['name'] ?? $job['agent_id'] ?? '-',
                substr($job['task_name'] ?? '-', 0, 30),
                $job['frequency'] ?? '-',
                $job['status'] ?? '-',
                $job['next_run_at'] ?? '-',
                $job['run_count'] ?? 0,
            ]);
        }

        $table->render();
        $io->text(sprintf('Total: %d jobs', count($jobs)));

        return Command::SUCCESS;
    }

    protected function createJob(IRIS $iris, InputInterface $input, SymfonyStyle $io): int
    {
        $agentId = $input->getArgument('id');

        if (!$agentId) {
            $io->error('Agent ID is required. Usage: iris schedule create <agent-id>');
            return Command::FAILURE;
        }

        $taskName = $input->getOption('task-name');
        $prompt = $input->getOption('prompt');

        if (!$taskName) {
            $taskName = $io->ask('Task name');
        }

        if (!$prompt) {
            $prompt = $io->ask('Prompt/task description', $taskName);
        }

        $response = $iris->schedules()->create([
            'agent_id' => $agentId,
            'task_name' => $taskName,
            'prompt' => $prompt,
            'time' => $input->getOption('time'),
            'frequency' => $input->getOption('frequency'),
        ]);

        if ($input->getOption('json')) {
            $io->writeln(json_encode($response, JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        $job = $response['data'] ?? $response;

        $io->success("Scheduled job created!");
        $io->table(
            ['Property', 'Value'],
            [
                ['ID', $job['id'] ?? '-'],
                ['Task Name', $job['task_name'] ?? '-'],
                ['Frequency', $job['frequency'] ?? '-'],
                ['Next Run', $job['next_run_at'] ?? '-'],
                ['Status', $job['status'] ?? '-'],
            ]
        );

        return Command::SUCCESS;
    }

    protected function runJob(IRIS $iris, InputInterface $input, SymfonyStyle $io): int
    {
        $jobId = $input->getArgument('id');

        if (!$jobId) {
            $io->error('Job ID is required. Usage: iris schedule run <job-id>');
            return Command::FAILURE;
        }

        $io->text("Dispatching job #{$jobId} to queue...");

        $response = $iris->schedules()->run($jobId);

        if ($input->getOption('json')) {
            $io->writeln(json_encode($response, JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        $io->success($response['message'] ?? 'Job dispatched successfully');
        $io->text("Job ID: " . ($response['job_id'] ?? $jobId));
        $io->text("Status: " . ($response['status'] ?? 'dispatched'));

        $io->note("The job is now running in the background. Use 'iris schedule history {$jobId}' to check results.");

        return Command::SUCCESS;
    }

    protected function jobHistory(IRIS $iris, InputInterface $input, SymfonyStyle $io): int
    {
        $jobId = $input->getArgument('id');

        if (!$jobId) {
            $io->error('Job ID is required. Usage: iris schedule history <job-id>');
            return Command::FAILURE;
        }

        $params = [
            'limit' => $input->getOption('limit'),
        ];

        if ($status = $input->getOption('status')) {
            $params['status'] = $status;
        }

        $response = $iris->schedules()->executions($jobId, $params);

        if ($input->getOption('json')) {
            $io->writeln(json_encode($response, JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        $executions = $response['data'] ?? [];
        $job = $response['job'] ?? [];

        $io->title("Execution History for Job #{$jobId}");

        if (!empty($job)) {
            $io->table(
                ['Property', 'Value'],
                [
                    ['Task Name', $job['task_name'] ?? '-'],
                    ['Status', $job['status'] ?? '-'],
                    ['Run Count', $job['run_count'] ?? 0],
                    ['Next Run', $job['next_run_at'] ?? '-'],
                ]
            );
        }

        if (empty($executions)) {
            $io->info('No executions found yet.');
            return Command::SUCCESS;
        }

        $table = new Table($io);
        $table->setHeaders(['Run #', 'Status', 'Started', 'Duration', 'Model', 'Tokens', 'Result Link']);

        foreach ($executions as $exec) {
            $duration = isset($exec['duration_seconds'])
                ? number_format($exec['duration_seconds'], 1) . 's'
                : '-';

            $table->addRow([
                $exec['run_number'] ?? '-',
                $this->formatStatus($exec['status'] ?? '-'),
                $exec['started_at'] ?? '-',
                $duration,
                $exec['model_used'] ?? '-',
                $exec['tokens_used'] ?? '-',
                $exec['public_url'] ? $this->shortenUrl($exec['public_url']) : '-',
            ]);
        }

        $table->render();

        return Command::SUCCESS;
    }

    protected function agentHistory(IRIS $iris, InputInterface $input, SymfonyStyle $io): int
    {
        $agentId = $input->getArgument('id');

        if (!$agentId) {
            $io->error('Agent ID is required. Usage: iris schedule agent-history <agent-id>');
            return Command::FAILURE;
        }

        $params = [
            'limit' => $input->getOption('limit'),
        ];

        if ($status = $input->getOption('status')) {
            $params['status'] = $status;
        }

        $response = $iris->schedules()->agentExecutions($agentId, $params);

        if ($input->getOption('json')) {
            $io->writeln(json_encode($response, JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        $executions = $response['data'] ?? [];
        $stats = $response['stats'] ?? [];
        $agent = $response['agent'] ?? [];

        $io->title("Execution History for Agent: " . ($agent['name'] ?? "#{$agentId}"));

        if (!empty($stats)) {
            $io->section('Statistics');
            $io->table(
                ['Metric', 'Value'],
                [
                    ['Total Executions', $stats['total_executions'] ?? 0],
                    ['Successful', $stats['successful'] ?? 0],
                    ['Failed', $stats['failed'] ?? 0],
                    ['Total Tokens Used', number_format($stats['total_tokens_used'] ?? 0)],
                ]
            );
        }

        if (empty($executions)) {
            $io->info('No executions found yet.');
            return Command::SUCCESS;
        }

        $io->section('Recent Executions');

        $table = new Table($io);
        $table->setHeaders(['ID', 'Task', 'Run #', 'Status', 'Started', 'Tokens', 'Result Link']);

        foreach ($executions as $exec) {
            $table->addRow([
                $exec['id'] ?? '-',
                substr($exec['task_name'] ?? '-', 0, 25),
                $exec['run_number'] ?? '-',
                $this->formatStatus($exec['status'] ?? '-'),
                $exec['started_at'] ?? '-',
                $exec['tokens_used'] ?? '-',
                isset($exec['public_url']) ? $this->shortenUrl($exec['public_url']) : '-',
            ]);
        }

        $table->render();

        return Command::SUCCESS;
    }

    protected function formatStatus(string $status): string
    {
        return match ($status) {
            'completed' => '<fg=green>✓ completed</>',
            'failed' => '<fg=red>✗ failed</>',
            'running' => '<fg=yellow>⟳ running</>',
            'pending' => '<fg=gray>○ pending</>',
            default => $status,
        };
    }

    protected function shortenUrl(string $url): string
    {
        // Extract just the UUID part for display
        if (preg_match('/([a-f0-9-]{36})$/', $url, $matches)) {
            return '...' . substr($matches[1], 0, 8);
        }

        return strlen($url) > 40 ? substr($url, 0, 37) . '...' : $url;
    }
}
