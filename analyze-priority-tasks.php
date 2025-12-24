<?php

require_once __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;
use IRIS\SDK\Exceptions\IRISException;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Configuration
$env = $_ENV['IRIS_ENV'] ?? 'local';
$userId = 193;

// Display banner
echo "\n🎯 Lead Priority & Task Analysis\n";
echo str_repeat("=", 60) . "\n\n";
echo "🔧 Configuration:\n";
echo "   Environment: {$env}\n";
echo "   User ID: {$userId}\n\n";

// Initialize SDK
$apiKey = $_ENV['IRIS_API_KEY'];
$baseUrl = $env === 'production' ? ($_ENV['FL_API_URL'] ?? 'https://apiv2.heyiris.io') : ($_ENV['FL_API_LOCAL_URL'] ?? 'https://local.raichu.freelabel.net');

$iris = new IRIS([
    'api_key' => $apiKey,
    'base_url' => $baseUrl,
]);

try {
    // Get HTTP client via reflection
    $reflection = new ReflectionClass($iris);
    $httpProperty = $reflection->getProperty('http');
    $httpProperty->setAccessible(true);
    $http = $httpProperty->getValue($iris);
    
    // Try to get aggregation data first
    $leads = [];
    $useAggregation = false;
    
    try {
        echo "📊 Fetching lead aggregation data...\n";
        $stats = $http->get('/api/v1/leads/aggregation/statistics', ['user_id' => $userId]);
        $aggregationData = $http->get('/api/v1/leads/aggregation', ['per_page' => 50, 'user_id' => $userId]);

        // Extract data from paginated response
        if (isset($aggregationData['data'])) {
            $leads = $aggregationData['data'];
            $useAggregation = true;
            $totalLeads = $stats['total_leads'] ?? count($leads);
            echo "✓ Retrieved {$totalLeads} leads with aggregation data\n\n";
        } elseif (is_array($aggregationData) && !isset($aggregationData['data'])) {
            // Direct array response (non-paginated)
            $leads = $aggregationData;
            $useAggregation = true;
            $totalLeads = $stats['total_leads'] ?? count($leads);
            echo "✓ Retrieved {$totalLeads} leads with aggregation data\n\n";
        }
    } catch (IRISException $e) {
        if ($e->getCode() === 404) {
            echo "⚠️  Aggregation endpoint not available, using basic leads...\n";
            $basicLeadsData = $http->get('/api/v1/leads', ['per_page' => 50, 'user_id' => $userId]);
            // Extract data from paginated response
            $leads = $basicLeadsData['data'] ?? $basicLeadsData;
            echo "✓ Retrieved " . count($leads) . " basic leads\n\n";
        } else {
            throw $e;
        }
    }
    
    // Analyze and categorize leads
    $priorityTasks = [];
    $urgentLeads = [];
    $activeLeads = [];
    $prospectedLeads = [];
    
    foreach ($leads as $lead) {
        $leadData = [
            'id' => $lead['id'],
            'name' => $lead['nickname'] ?? 'Unnamed Lead',
            'status' => $lead['status'] ?? 'Unknown',
            'priority_score' => $lead['priority_score'] ?? 0,
            'tasks' => [],
            'notes_count' => $lead['notes_count'] ?? 0,
            'deliverables_count' => $lead['deliverables_count'] ?? 0,
            'invoices_count' => $lead['invoices_count'] ?? 0,
        ];
        
        // Get tasks if available
        if ($useAggregation && isset($lead['tasks'])) {
            foreach ($lead['tasks'] as $task) {
                $leadData['tasks'][] = [
                    'id' => $task['id'],
                    'description' => $task['description'] ?? 'No description',
                    'completed' => $task['completed'] ?? false,
                    'due_date' => $task['due_date'] ?? null,
                ];
            }
        }
        
        // Categorize leads
        if (count($leadData['tasks']) > 0) {
            $priorityTasks[] = $leadData;
        }
        
        if ($leadData['status'] === 'New' || $leadData['status'] === 'Active') {
            $activeLeads[] = $leadData;
        } elseif ($leadData['status'] === 'Prospected') {
            $prospectedLeads[] = $leadData;
        }
        
        if ($leadData['priority_score'] >= 50) {
            $urgentLeads[] = $leadData;
        }
    }
    
    // Sort by priority
    usort($priorityTasks, fn($a, $b) => $b['priority_score'] <=> $a['priority_score']);
    usort($urgentLeads, fn($a, $b) => $b['priority_score'] <=> $a['priority_score']);
    usort($activeLeads, fn($a, $b) => $b['priority_score'] <=> $a['priority_score']);
    
    // Display Priority Analysis
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📈 PRIORITY ANALYSIS\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    // Critical Tasks (leads with tasks)
    if (count($priorityTasks) > 0) {
        echo "🔴 CRITICAL: Leads with Active Tasks (" . count($priorityTasks) . ")\n";
        echo str_repeat("-", 60) . "\n";
        
        foreach ($priorityTasks as $lead) {
            $incompleteTasks = array_filter($lead['tasks'], fn($t) => !$t['completed']);
            $taskCount = count($incompleteTasks);
            
            echo "\n• {$lead['name']} (Priority: {$lead['priority_score']})\n";
            echo "  Status: {$lead['status']} | {$taskCount} incomplete task(s)\n";
            
            if (count($lead['tasks']) > 0) {
                echo "  Tasks:\n";
                foreach ($lead['tasks'] as $task) {
                    $status = $task['completed'] ? '✓' : '○';
                    $dueInfo = $task['due_date'] ? " (Due: {$task['due_date']})" : '';
                    echo "    {$status} {$task['description']}{$dueInfo}\n";
                }
            }
            
            if ($lead['notes_count'] > 0) {
                echo "  📝 {$lead['notes_count']} notes";
            }
            if ($lead['deliverables_count'] > 0) {
                echo " | 📦 {$lead['deliverables_count']} deliverables";
            }
            if ($lead['invoices_count'] > 0) {
                echo " | 💰 {$lead['invoices_count']} invoices";
            }
            if ($lead['notes_count'] > 0 || $lead['deliverables_count'] > 0 || $lead['invoices_count'] > 0) {
                echo "\n";
            }
        }
        echo "\n";
    } else {
        echo "🔴 CRITICAL: Leads with Active Tasks\n";
        echo str_repeat("-", 60) . "\n";
        echo "✓ No leads with active tasks\n\n";
    }
    
    // High Priority Leads (score >= 50)
    if (count($urgentLeads) > 0) {
        echo "🟠 HIGH PRIORITY: Urgent Leads (Score ≥ 50) (" . count($urgentLeads) . ")\n";
        echo str_repeat("-", 60) . "\n";
        
        foreach (array_slice($urgentLeads, 0, 10) as $lead) {
            echo "\n• {$lead['name']} (Priority: {$lead['priority_score']})\n";
            echo "  Status: {$lead['status']}";
            if ($lead['notes_count'] > 0) echo " | {$lead['notes_count']} notes";
            if ($lead['deliverables_count'] > 0) echo " | {$lead['deliverables_count']} deliverables";
            echo "\n";
        }
        echo "\n";
    }
    
    // Active Leads
    if (count($activeLeads) > 0) {
        echo "🟡 ACTIVE: New & Active Leads (" . count($activeLeads) . ")\n";
        echo str_repeat("-", 60) . "\n";
        
        foreach (array_slice($activeLeads, 0, 10) as $lead) {
            echo "\n• {$lead['name']} (Priority: {$lead['priority_score']})\n";
            echo "  Status: {$lead['status']}";
            if ($lead['notes_count'] > 0) echo " | {$lead['notes_count']} notes";
            echo "\n";
        }
        echo "\n";
    }
    
    // Prospected Leads Summary
    if (count($prospectedLeads) > 0) {
        echo "🔵 PROSPECTED: Leads in Pipeline (" . count($prospectedLeads) . ")\n";
        echo str_repeat("-", 60) . "\n";
        echo "Top 5 by priority:\n";
        
        foreach (array_slice($prospectedLeads, 0, 5) as $lead) {
            echo "  • {$lead['name']} (Priority: {$lead['priority_score']})";
            if ($lead['notes_count'] > 0) echo " | {$lead['notes_count']} notes";
            echo "\n";
        }
        echo "\n";
    }
    
    // Action Recommendations
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "💡 RECOMMENDED ACTIONS\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $recommendations = [];
    
    if (count($priorityTasks) > 0) {
        $recommendations[] = [
            'priority' => 1,
            'action' => 'Complete active tasks for leads with existing work',
            'count' => count($priorityTasks),
            'details' => 'These leads have tasks assigned and are waiting for completion'
        ];
    }
    
    if (count($urgentLeads) > 0) {
        $recommendations[] = [
            'priority' => 2,
            'action' => 'Follow up with high-priority leads (score ≥ 50)',
            'count' => count($urgentLeads),
            'details' => 'These leads are hot and need immediate attention'
        ];
    }
    
    if (count($activeLeads) > 0) {
        $recommendations[] = [
            'priority' => 3,
            'action' => 'Engage with new/active leads',
            'count' => count($activeLeads),
            'details' => 'Recent leads that need initial outreach or continued engagement'
        ];
    }
    
    if (count($prospectedLeads) > 0) {
        $recommendations[] = [
            'priority' => 4,
            'action' => 'Nurture prospected leads',
            'count' => count($prospectedLeads),
            'details' => 'Keep these leads warm with regular check-ins'
        ];
    }
    
    foreach ($recommendations as $rec) {
        echo "{$rec['priority']}. {$rec['action']} ({$rec['count']})\n";
        echo "   → {$rec['details']}\n\n";
    }
    
    // Summary
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📊 SUMMARY\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    echo "Total Leads: " . count($leads) . "\n";
    echo "Leads with Tasks: " . count($priorityTasks) . "\n";
    echo "High Priority (≥50): " . count($urgentLeads) . "\n";
    echo "Active/New Leads: " . count($activeLeads) . "\n";
    echo "Prospected Leads: " . count($prospectedLeads) . "\n";
    
    echo "\n✅ Analysis complete!\n\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: {$e->getMessage()}\n";
    echo "   File: {$e->getFile()}:{$e->getLine()}\n\n";
    exit(1);
}
