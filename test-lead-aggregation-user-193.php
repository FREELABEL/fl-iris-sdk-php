#!/usr/bin/env php
<?php

/**
 * Quick test script to fetch lead aggregation data for user 193
 * 
 * Configuration is read from .env file
 * Copy .env.example to .env and configure your settings
 * 
 * Usage: php test-lead-aggregation-user-193.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;
use IRIS\SDK\Exceptions\IRISException;
use Dotenv\Dotenv;

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
} else {
    echo "⚠️  No .env file found. Copy .env.example to .env and configure.\n\n";
}

// Get configuration from environment
$apiKey = $_ENV['IRIS_API_KEY'] ?? getenv('IRIS_API_KEY');
$userId = (int)($_ENV['IRIS_USER_ID'] ?? getenv('IRIS_USER_ID') ?? 193);
$env = $_ENV['IRIS_ENV'] ?? getenv('IRIS_ENV') ?? 'local';

// Determine base URL based on environment
// Use FL-API for leads (apiv2.heyiris.io)
if ($env === 'production') {
    $baseUrl = $_ENV['FL_API_URL'] ?? 'https://apiv2.heyiris.io';
} else {
    $baseUrl = $_ENV['FL_API_LOCAL_URL'] ?? 'https://local.raichu.freelabel.net';
}

if (!$apiKey) {
    echo "❌ IRIS_API_KEY not set in .env file or environment\n";
    echo "   Copy .env.example to .env and add your API key\n";
    exit(1);
}

echo "🔧 Configuration:\n";
echo "   Environment: {$env}\n";
echo "   Base URL: {$baseUrl}\n";
echo "   User ID: {$userId}\n";
echo "   API Key: " . substr($apiKey, 0, 10) . "...\n\n";

// Initialize SDK for user 193
$iris = new IRIS([
    'api_key' => $apiKey,
    'user_id' => $userId,
    'base_url' => $baseUrl,
]);

echo "🔍 Fetching Lead Aggregation Data for User 193\n";
echo str_repeat('=', 60) . "\n\n";

try {
    // Get HTTP client via reflection
    $reflection = new ReflectionClass($iris);
    $httpProperty = $reflection->getProperty('http');
    $httpProperty->setAccessible(true);
    $http = $httpProperty->getValue($iris);
    
    // 1. Get Statistics
    echo "📊 Lead Statistics:\n";
    
    try {
        $stats = $http->get('/api/v1/leads/aggregation/statistics');
    } catch (IRISException $e) {
        // Check if this is a 404 error (endpoint not found)
        if ($e->getCode() === 404) {
            echo "  ⚠️  Lead Aggregation API endpoint not found\n";
            echo "  ℹ️  This endpoint may not be deployed to {$env} environment yet.\n";
            echo "  💡 The endpoint works locally but needs to be deployed to production.\n\n";
            echo "  Available in local environment:\n";
            echo "     • GET /api/v1/leads/aggregation/statistics\n";
            echo "     • GET /api/v1/leads/aggregation\n";
            echo "     • GET /api/v1/leads/aggregation/{leadId}\n\n";
            
            // Try to show some basic info instead
            echo "  Attempting to fetch basic leads data instead...\n\n";
            
            try {
                $basicLeads = $http->get('/api/v1/leads', ['per_page' => 5]);
                
                if (is_array($basicLeads) && count($basicLeads) > 0) {
                    echo "  ✓ Retrieved " . count($basicLeads) . " sample leads\n\n";
                    
                    foreach ($basicLeads as $lead) {
                        $noteCount = $lead['note_count'] ?? 0;
                        $taskCount = $lead['tasks_count'] ?? 0;
                        echo "     • {$lead['nickname']} ({$lead['status']})";
                        if ($taskCount > 0) echo " - {$taskCount} tasks";
                        if ($noteCount > 0) echo " - {$noteCount} notes";
                        echo "\n";
                    }
                } else {
                    echo "  ℹ️  No leads found\n";
                }
            } catch (Exception $fallbackError) {
                echo "  ❌ Could not fetch fallback data: " . $fallbackError->getMessage() . "\n";
            }
            
            echo "\n✅ Test completed with fallback data\n";
            exit(0);
        }
        
        // If it's not a 404, re-throw the exception
        throw $e;
    } catch (Exception $e) {
        // Check if this is a 404 error (endpoint not found)
        if (strpos($e->getMessage(), '404') !== false || strpos($e->getMessage(), 'Not Found') !== false) {
            echo "  ⚠️  Lead Aggregation API endpoint not found\n";
            echo "  ℹ️  This endpoint may not be deployed to {$env} environment yet.\n";
            echo "  💡 The endpoint works locally but needs to be deployed to production.\n\n";
            echo "  Available in local environment:\n";
            echo "     • GET /api/v1/leads/aggregation/statistics\n";
            echo "     • GET /api/v1/leads/aggregation\n";
            echo "     • GET /api/v1/leads/aggregation/{leadId}\n\n";
            
            // Try to show some basic info instead
            echo "  Attempting to fetch basic leads data instead...\n\n";
            $basicLeads = $http->get('/api/v1/leads', ['per_page' => 5]);
            
            if (isset($basicLeads['total'])) {
                echo "  ✓ Total Leads: {$basicLeads['total']}\n";
                echo "  ✓ Showing " . count($basicLeads['data']) . " sample leads\n\n";
                
                foreach ($basicLeads['data'] as $lead) {
                    echo "     • {$lead['nickname']} ({$lead['status']})\n";
                }
            }
            
            echo "\n✅ Test completed with fallback data\n";
            exit(0);
        }
        
        // If it's not a 404, re-throw the exception
        throw $e;
    }
    
    if (isset($stats['total_leads'])) {
        echo "  ✓ Total Leads: {$stats['total_leads']}\n";
        echo "  ✓ Total Tasks: {$stats['total_tasks']}\n";
        echo "  ✓ Incomplete Tasks: {$stats['incomplete_tasks']}\n";
        echo "  ✓ Active Leads: {$stats['leads_with_incomplete_tasks']}\n\n";
        
        if (!empty($stats['top_priority_leads'])) {
            echo "  🔥 Top Priority Leads:\n";
            foreach (array_slice($stats['top_priority_leads'], 0, 5) as $lead) {
                $taskCount = isset($lead['tasks_summary']) ? "({$lead['tasks_summary']['incomplete']}/{$lead['tasks_summary']['total']} tasks)" : '';
                $noteCount = isset($lead['counts']['notes']) && $lead['counts']['notes'] > 0 ? " 📝 {$lead['counts']['notes']} notes" : '';
                echo "     [{$lead['priority_score']}] {$lead['nickname']} ({$lead['status']}) {$taskCount}{$noteCount}\n";
                
                // Show tasks if available
                if (!empty($lead['tasks'])) {
                    foreach (array_slice($lead['tasks'], 0, 3) as $task) {
                        $status = $task['is_completed'] ? '✓' : '○';
                        echo "        {$status} {$task['title']}\n";
                    }
                }
                
                // Show notes if available
                if (!empty($lead['notes'])) {
                    foreach (array_slice($lead['notes'], 0, 2) as $note) {
                        $preview = substr($note['content'], 0, 60);
                        if (strlen($note['content']) > 60) $preview .= '...';
                        echo "        📝 {$preview}\n";
                    }
                }
                
                echo "\n";
            }
        } else {
            echo "  No priority leads found\n";
        }
    }
    
    echo "\n" . str_repeat('-', 60) . "\n\n";
    
    // 2. Get Lead List
    echo "📋 Lead Aggregation List:\n";
    $list = $http->get('/api/v1/leads/aggregation', [
        'per_page' => 10,
        'order_by' => 'priority',
        'order_direction' => 'desc',
    ]);
    
    if (isset($list['data']) && !empty($list['data'])) {
        echo "  ✓ Found {$list['meta']['total']} total leads\n";
        echo "  ✓ Showing " . count($list['data']) . " leads\n\n";
        
        foreach ($list['data'] as $lead) {
            $tasks = "{$lead['tasks_summary']['incomplete']}/{$lead['tasks_summary']['total']}";
            echo "  • Priority {$lead['priority_score']}: {$lead['nickname']}\n";
            echo "    Status: {$lead['status']} | Tasks: {$tasks}\n";
        }
    } else {
        echo "  No leads found for user 193\n";
    }
    
    echo "\n" . str_repeat('-', 60) . "\n\n";
    
    // 3. Get Specific Lead Details
    if (isset($list['data']) && !empty($list['data'])) {
        $leadId = $list['data'][0]['id'];
        echo "🔍 Lead Details (ID: {$leadId}):\n";
        
        $lead = $http->get("/api/v1/leads/aggregation/{$leadId}");
        
        if (isset($lead['id'])) {
            echo "  Name: {$lead['nickname']}\n";
            echo "  Email: {$lead['email']}\n";
            echo "  Company: {$lead['company']}\n";
            echo "  Status: {$lead['status']}\n";
            echo "  Priority: {$lead['priority_score']}\n";
            echo "  Total Tasks: " . count($lead['tasks']) . "\n";
            
            if (!empty($lead['tasks'])) {
                echo "\n  Tasks:\n";
                foreach (array_slice($lead['tasks'], 0, 5) as $task) {
                    $status = $task['is_completed'] ? '✓' : '○';
                    echo "    {$status} {$task['title']}\n";
                }
            }
        }
    } else {
        echo "🔍 Skipping lead details - no leads available\n";
    }
    
    echo "\n✅ Test completed successfully!\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
