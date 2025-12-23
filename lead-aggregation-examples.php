#!/usr/bin/env php
<?php

/**
 * Lead Aggregation CLI Examples
 * 
 * Demonstrates clean ways to fetch and display leads with the IRIS SDK
 */

require_once __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;
use Dotenv\Dotenv;

// Load environment
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['IRIS_API_KEY'];
$env = $_ENV['IRIS_ENV'] ?? 'production';

echo "\n🎯 IRIS Lead Aggregation Examples\n";
echo str_repeat("=", 60) . "\n\n";
echo "Environment: {$env}\n\n";

// Initialize SDK
$iris = new IRIS([
    'api_key' => $apiKey,
    'user_id' => 193,
]);

// Example 1: Get 10 most recently updated leads
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📅 EXAMPLE 1: Recent Leads (Last 10 Updated)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$recentLeads = $iris->leads->aggregation()->getRecentLeads(10);

foreach ($recentLeads as $i => $lead) {
    $tasks = $lead['tasks_summary']['incomplete'];
    $taskInfo = $tasks > 0 ? "✓ {$tasks} incomplete tasks" : "○ No tasks";
    echo sprintf(
        "%2d. %-30s [%-12s] %s\n",
        $i + 1,
        $lead['nickname'],
        $lead['status'],
        $taskInfo
    );
}

// Example 2: Priority Statuses (Won, Negotiation, Proposal)
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🔥 EXAMPLE 2: High-Priority Statuses (Won/Negotiation/Proposal)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Note: Due to current implementation, we fetch each status separately
$priorityStatuses = ['Won', 'Negotiation', 'Proposal'];
$priorityLeads = [];

foreach ($priorityStatuses as $status) {
    $leads = $iris->leads->aggregation()->list([
        'status' => $status,
        'sort' => 'updated_at',
        'order' => 'desc',
        'per_page' => 50,
    ]);
    
    if (isset($leads['data'])) {
        $priorityLeads = array_merge($priorityLeads, $leads['data']);
    }
}

// Sort by updated_at
usort($priorityLeads, function($a, $b) {
    return strtotime($b['updated_at'] ?? '1970-01-01') - strtotime($a['updated_at'] ?? '1970-01-01');
});

$priorityLeads = array_slice($priorityLeads, 0, 10);

foreach ($priorityLeads as $i => $lead) {
    $tasks = $lead['tasks_summary']['incomplete'];
    echo sprintf(
        "%2d. %-30s [%-12s] %d incomplete tasks\n",
        $i + 1,
        $lead['nickname'],
        $lead['status'],
        $tasks
    );
}

// Example 3: Leads with Incomplete Tasks
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ EXAMPLE 3: Leads with Incomplete Tasks (Action Required)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$leadsWithTasks = $iris->leads->aggregation()->getRecentLeads(10, [
    'has_incomplete_tasks' => 1,
]);

foreach ($leadsWithTasks as $i => $lead) {
    echo sprintf(
        "%2d. %-30s [%-12s] %d tasks\n",
        $i + 1,
        $lead['nickname'],
        $lead['status'],
        $lead['tasks_summary']['incomplete']
    );
    
    // Fetch full details to show tasks
    $fullLead = $iris->leads->aggregation()->get($lead['id']);
    if (!empty($fullLead['tasks'])) {
        foreach (array_slice($fullLead['tasks'], 0, 3) as $task) {
            $status = $task['is_completed'] ? '✓' : '○';
            echo "    {$status} {$task['title']}\n";
        }
        if (count($fullLead['tasks']) > 3) {
            echo "    ... +" . (count($fullLead['tasks']) - 3) . " more tasks\n";
        }
    }
}

// Example 4: Custom Sorting and Filtering
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🎯 EXAMPLE 4: Custom Sorting (By Priority Score)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$prioritySorted = $iris->leads->aggregation()->list([
    'sort' => 'priority',
    'order' => 'desc',
    'per_page' => 10,
]);

if (isset($prioritySorted['data'])) {
    foreach ($prioritySorted['data'] as $i => $lead) {
        echo sprintf(
            "%2d. %-30s [Score: %3d] [%-12s] %d tasks\n",
            $i + 1,
            $lead['nickname'],
            $lead['priority_score'],
            $lead['status'],
            $lead['tasks_summary']['incomplete']
        );
    }
}

// Summary
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "💡 CLI COMMANDS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "# Get 10 most recent leads:\n";
echo "./bin/iris sdk:call leads.aggregation.getRecentLeads 10\n\n";

echo "# Get recent Won leads:\n";
echo "./bin/iris sdk:call leads.aggregation.getRecentLeads 10 status=Won\n\n";

echo "# Get leads sorted by updated_at:\n";
echo "./bin/iris sdk:call leads.aggregation.list sort=updated_at order=desc per_page=10\n\n";

echo "# Get leads with incomplete tasks:\n";
echo "./bin/iris sdk:call leads.aggregation.list has_incomplete_tasks=1 sort=updated_at per_page=10\n\n";

echo "# Get leads by status:\n";
echo "./bin/iris sdk:call leads.aggregation.list status=Won sort=updated_at per_page=10\n\n";

echo "# Get high-priority leads:\n";
echo "./bin/iris sdk:call leads.aggregation.list sort=priority order=desc per_page=10\n\n";

echo "✅ Examples complete!\n\n";
