<?php

/**
 * BLOQ Ingestion System - Complete Testing Script
 * 
 * This script demonstrates how to use the BLOQ ingestion system
 * to bulk-ingest files from cloud storage (Dropbox, Google Drive).
 * 
 * Prerequisites:
 * 1. User must have active Dropbox or Google Drive integration
 * 2. User must have created a BLOQ (knowledge base)
 * 3. Redis must be running for progress tracking
 * 4. Laravel queue worker must be running: php artisan queue:work
 * 
 * Usage:
 *   php test-bloq-ingestion.php
 */

require __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $env = parse_ini_file(__DIR__ . '/.env');
    foreach ($env as $key => $value) {
        putenv("{$key}={$value}");
    }
}

// Configuration
$apiKey = getenv('IRIS_API_KEY');
$userId = (int) getenv('IRIS_USER_ID');
$bloqId = (int) ($argv[1] ?? 0); // Pass BLOQ ID as first argument

if (!$apiKey || !$userId) {
    echo "❌ Error: Missing API credentials\n";
    echo "Please set IRIS_API_KEY and IRIS_USER_ID in .env file\n";
    exit(1);
}

if (!$bloqId) {
    echo "❌ Error: Missing BLOQ ID\n";
    echo "Usage: php test-bloq-ingestion.php <bloq_id>\n";
    echo "Example: php test-bloq-ingestion.php 40\n";
    exit(1);
}

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  BLOQ Ingestion System - Test Script                      ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Initialize SDK
$iris = new IRIS([
    'api_key' => $apiKey,
    'user_id' => $userId,
]);

echo "✓ SDK initialized\n";
echo "  API Key: " . substr($apiKey, 0, 10) . "...\n";
echo "  User ID: {$userId}\n";
echo "  BLOQ ID: {$bloqId}\n";
echo "\n";

// =============================================================================
// TEST 1: List existing ingestion jobs
// =============================================================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 1: List Existing Ingestion Jobs\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    $jobsResponse = $iris->bloqs->listIngestionJobs($bloqId, [
        'limit' => 10,
        'page' => 1,
    ]);
    
    $jobs = $jobsResponse['jobs'] ?? [];
    
    if (empty($jobs)) {
        echo "📭 No ingestion jobs found for this BLOQ\n";
    } else {
        echo "📥 Found " . count($jobs) . " ingestion job(s):\n\n";
        
        foreach ($jobs as $job) {
            $status = $job['status'];
            $statusIcon = getStatusIcon($status);
            
            echo "  Job #{$job['id']}:\n";
            echo "    Status: {$statusIcon} {$status}\n";
            echo "    Source: {$job['source_type']}\n";
            echo "    Path: {$job['source_path']}\n";
            echo "    Progress: {$job['progress_percent']}%\n";
            echo "    Files: {$job['successful_files']}/{$job['total_files']} successful";
            
            if ($job['failed_files'] > 0) {
                echo ", {$job['failed_files']} failed";
            }
            
            echo "\n\n";
        }
    }
    
    echo "✓ Test 1 passed\n";
    
} catch (Exception $e) {
    echo "❌ Test 1 failed: {$e->getMessage()}\n";
    exit(1);
}

echo "\n";

// =============================================================================
// TEST 2: Start a new ingestion job
// =============================================================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 2: Start New Ingestion Job\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "Choose ingestion source:\n";
echo "1. Dropbox\n";
echo "2. Google Drive\n";
echo "3. Skip this test\n";
echo "\n";
echo "Enter choice (1-3): ";

$choice = trim(fgets(STDIN));

if ($choice === '3') {
    echo "⏭ Skipping test 2\n\n";
} elseif (in_array($choice, ['1', '2'])) {
    
    $source = $choice === '1' ? 'dropbox' : 'google_drive';
    
    echo "\n";
    if ($source === 'dropbox') {
        echo "Enter Dropbox folder path (e.g., /Test Folder): ";
    } else {
        echo "Enter Google Drive folder ID: ";
    }
    
    $path = trim(fgets(STDIN));
    
    if (empty($path)) {
        echo "❌ Path cannot be empty. Skipping test 2\n\n";
    } else {
        echo "\nInclude subfolders? (y/n): ";
        $recursive = strtolower(trim(fgets(STDIN))) === 'y';
        
        echo "\nStarting ingestion...\n";
        
        try {
            $job = $iris->bloqs->ingestFolder($bloqId, [
                'source' => $source,
                'path' => $path,
                'recursive' => $recursive,
                'file_types' => ['pdf', 'docx', 'txt', 'md', 'csv', 'json'],
                'list_name' => 'Test Ingestion - ' . date('Y-m-d H:i:s'),
            ]);
            
            echo "\n✓ Ingestion job started!\n";
            echo "  Job ID: {$job['job_id']}\n";
            echo "  Status: {$job['status']}\n";
            echo "\n";
            
            echo "Wait for completion? (y/n): ";
            $shouldWait = strtolower(trim(fgets(STDIN))) === 'y';
            
            if ($shouldWait) {
                echo "\n⏳ Waiting for ingestion to complete...\n\n";
                
                $lastProgress = 0;
                
                $final = $iris->bloqs->waitForIngestion(
                    $job['job_id'],
                    function($status) use (&$lastProgress) {
                        $progress = $status['progress_percent'] ?? 0;
                        $current = $status['current_file'] ?? '';
                        $processed = $status['processed_files'] ?? 0;
                        $total = $status['total_files'] ?? 0;
                        
                        // Only print when progress changes
                        if ($progress !== $lastProgress) {
                            $bar = str_repeat('█', (int)($progress / 2));
                            $spaces = str_repeat('░', 50 - (int)($progress / 2));
                            
                            echo "\r  [{$bar}{$spaces}] {$progress}% - {$processed}/{$total} files";
                            
                            if ($current) {
                                echo " - " . truncate($current, 40);
                            }
                            
                            $lastProgress = $progress;
                        }
                    }
                );
                
                echo "\n\n";
                
                // Show final results
                $status = $final['status'];
                $statusIcon = getStatusIcon($status);
                
                echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                echo "Final Results\n";
                echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
                
                echo "Status: {$statusIcon} {$status}\n";
                echo "Total Files: {$final['total_files']}\n";
                echo "Successful: {$final['successful_files']}\n";
                echo "Failed: {$final['failed_files']}\n";
                
                if (!empty($final['error_log'])) {
                    echo "\n⚠ Errors:\n";
                    
                    foreach (array_slice($final['error_log'], 0, 5) as $error) {
                        echo "  • {$error['file']}: {$error['error']}\n";
                    }
                    
                    $errorCount = count($final['error_log']);
                    if ($errorCount > 5) {
                        echo "  ... and " . ($errorCount - 5) . " more errors\n";
                    }
                }
                
                echo "\n✓ Test 2 completed\n";
            } else {
                echo "Job is running in the background.\n";
                echo "Monitor progress with:\n";
                echo "  php test-bloq-ingestion-status.php {$job['job_id']}\n";
                echo "\n✓ Test 2 passed\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Test 2 failed: {$e->getMessage()}\n";
            exit(1);
        }
    }
} else {
    echo "❌ Invalid choice. Skipping test 2\n\n";
}

echo "\n";

// =============================================================================
// TEST 3: Check job status
// =============================================================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 3: Check Job Status\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "Enter job ID to check (or press Enter to skip): ";
$checkJobId = trim(fgets(STDIN));

if (!empty($checkJobId)) {
    try {
        $status = $iris->bloqs->getIngestionStatus((int)$checkJobId);
        
        $statusIcon = getStatusIcon($status['status']);
        
        echo "\n📊 Job Status:\n\n";
        echo "  Job ID: {$status['job_id']}\n";
        echo "  Status: {$statusIcon} {$status['status']}\n";
        echo "  Progress: {$status['progress_percent']}%\n";
        echo "  Total Files: {$status['total_files']}\n";
        echo "  Processed: {$status['processed_files']}\n";
        echo "  Successful: {$status['successful_files']}\n";
        echo "  Failed: {$status['failed_files']}\n";
        
        if ($status['status'] === 'processing') {
            echo "  Current File: {$status['current_file']}\n";
            echo "  Speed: {$status['processing_speed']} files/min\n";
            echo "  ETA: {$status['estimated_remaining']}\n";
        }
        
        if (!empty($status['error_log'])) {
            echo "\n  ⚠ Recent Errors:\n";
            foreach (array_slice($status['error_log'], 0, 3) as $error) {
                echo "    • {$error['file']}: {$error['error']}\n";
            }
        }
        
        echo "\n✓ Test 3 passed\n";
        
    } catch (Exception $e) {
        echo "❌ Test 3 failed: {$e->getMessage()}\n";
        exit(1);
    }
} else {
    echo "⏭ Skipping test 3\n";
}

echo "\n";

// =============================================================================
// Summary
// =============================================================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✓ All Tests Completed Successfully!\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "Available CLI Commands:\n";
echo "  iris bloq:ingest <bloq_id> <source> <path> [options]\n";
echo "  iris bloq:ingestion-status <job_id> [--watch]\n";
echo "  iris bloq:ingestion-jobs <bloq_id> [--status=<status>]\n";
echo "  iris bloq:cancel-ingestion <job_id>\n";
echo "\n";

echo "For more help:\n";
echo "  php bin/iris bloq:ingest --help\n";
echo "\n";

// =============================================================================
// Helper Functions
// =============================================================================

function getStatusIcon(string $status): string
{
    $icons = [
        'pending' => '⏱',
        'processing' => '⚙',
        'completed' => '✓',
        'partial' => '⚠',
        'failed' => '✗',
        'cancelled' => '✗',
    ];
    
    return $icons[$status] ?? '•';
}

function truncate(string $text, int $length): string
{
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length - 3) . '...';
}
