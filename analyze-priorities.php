#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$iris = new IRIS([
    'api_key' => $_ENV['IRIS_API_KEY'],
    'user_id' => 193,
]);

echo "\n🎯 PRIORITY ACTION LIST FROM LATEST LEADS\n";
echo str_repeat('=', 60) . "\n\n";

// Get statistics first
$stats = $iris->leads->aggregation()->statistics();

echo "📊 OVERVIEW:\n";
echo "  • Total Leads: {$stats['total_leads']}\n";
echo "  • Total Tasks: {$stats['total_tasks']} ({$stats['incomplete_tasks']} incomplete)\n";
echo "  • Leads with Tasks: {$stats['leads_with_tasks']}\n";
echo "  • Recent Activity: {$stats['recent_activity']['notes_last_7d']} notes in last 7 days\n\n";

// Get recent leads
$leads = $iris->leads->aggregation()->getRecentLeads(15);

echo "Analyzed " . count($leads) . " most recently updated leads\n\n";

// Categorize
$with_tasks = array_filter($leads, fn($l) => $l['tasks_summary']['incomplete'] > 0);
$won = array_filter($leads, fn($l) => $l['status'] == 'Won');
$negotiation = array_filter($leads, fn($l) => $l['status'] == 'Negotiation');
$proposal = array_filter($leads, fn($l) => $l['status'] == 'Proposal');
$contacted = array_filter($leads, fn($l) => $l['status'] == 'Contacted');

echo str_repeat('━', 60) . "\n";
echo "🔴 CRITICAL: Active Tasks (" . count($with_tasks) . " leads)\n";
echo str_repeat('━', 60) . "\n\n";

if (count($with_tasks) > 0) {
    foreach ($with_tasks as $lead) {
        echo "• {$lead['nickname']} ({$lead['status']})\n";
        echo "  → {$lead['tasks_summary']['incomplete']} incomplete tasks\n";
        
        // Fetch full details to show task titles
        $fullLead = $iris->leads->aggregation()->get($lead['id']);
        if (!empty($fullLead['tasks'])) {
            foreach ($fullLead['tasks'] as $task) {
                if (!$task['is_completed']) {
                    echo "    ○ {$task['title']}\n";
                }
            }
        }
        echo "\n";
    }
} else {
    echo "✓ No leads with active tasks\n\n";
}

echo str_repeat('━', 60) . "\n";
echo "🟢 WON DEALS: " . count($won) . " clients (retention focus)\n";
echo str_repeat('━', 60) . "\n\n";

if (count($won) > 0) {
    foreach ($won as $lead) {
        $tasks = $lead['tasks_summary']['incomplete'];
        $taskInfo = $tasks > 0 ? " - {$tasks} tasks pending" : " - No active tasks";
        echo "• {$lead['nickname']}{$taskInfo}\n";
    }
} else {
    echo "No won deals in recent leads\n";
}

echo "\n" . str_repeat('━', 60) . "\n";
$total_deals = count($negotiation) + count($proposal);
echo "🟡 NEGOTIATION/PROPOSAL: {$total_deals} leads (close soon!)\n";
echo str_repeat('━', 60) . "\n\n";

if ($total_deals > 0) {
    foreach (array_merge($negotiation, $proposal) as $lead) {
        echo "• {$lead['nickname']} ({$lead['status']})\n";
    }
} else {
    echo "No deals in negotiation or proposal stage\n";
}

echo "\n" . str_repeat('━', 60) . "\n";
echo "🔵 CONTACTED: " . count($contacted) . " leads (follow up needed)\n";
echo str_repeat('━', 60) . "\n\n";

if (count($contacted) > 0) {
    foreach (array_slice($contacted, 0, 8) as $lead) {
        echo "• {$lead['nickname']}\n";
    }
    if (count($contacted) > 8) {
        echo "... and " . (count($contacted) - 8) . " more\n";
    }
} else {
    echo "No contacted leads\n";
}

// Detailed priorities from top_priority_leads
echo "\n" . str_repeat('━', 60) . "\n";
echo "💡 TOP PRIORITY ACTIONS (From Statistics)\n";
echo str_repeat('━', 60) . "\n\n";

if (!empty($stats['top_priority_leads'])) {
    $priority_count = 1;
    foreach (array_slice($stats['top_priority_leads'], 0, 3) as $lead) {
        echo "{$priority_count}. {$lead['nickname']} (Priority: {$lead['priority_score']})\n";
        echo "   Status: {$lead['status']}\n";
        echo "   Tasks: {$lead['tasks_summary']['incomplete']} incomplete / {$lead['tasks_summary']['total']} total\n";
        
        if (!empty($lead['tasks'])) {
            echo "   Action Items:\n";
            foreach ($lead['tasks'] as $task) {
                if (!$task['is_completed']) {
                    echo "     • {$task['title']}\n";
                }
            }
        }
        
        if (!empty($lead['notes']) && isset($lead['notes'][0])) {
            $latest_note = $lead['notes'][0];
            $preview = substr($latest_note['content'], 0, 100);
            echo "   Latest Note ({$latest_note['created_at_human']}): {$preview}...\n";
        }
        
        echo "\n";
        $priority_count++;
    }
}

echo "✅ Analysis complete!\n\n";
