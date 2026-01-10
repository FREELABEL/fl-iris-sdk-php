<?php

/**
 * BLOQ Ingestion Status Checker
 * 
 * Simple script to check the status of an ingestion job
 * and watch it in real-time until completion.
 * 
 * Usage:
 *   php test-bloq-ingestion-status.php <job_id>
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
$jobId = (int) ($argv[1] ?? 0);

if (!$apiKey || !$userId) {
    echo "❌ Error: Missing API credentials\n";
    echo "Please set IRIS_API_KEY and IRIS_USER_ID in .env file\n";
    exit(1);
}

if (!$jobId) {
    echo "❌ Error: Missing job ID\n";
    echo "Usage: php test-bloq-ingestion-status.php <job_id>\n";
    echo "Example: php test-bloq-ingestion-status.php 123\n";
    exit(1);
}

// Initialize SDK
$iris = new IRIS([
    'api_key' => $apiKey,
    'user_id' => $userId,
]);

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  BLOQ Ingestion Status Monitor                            ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "Job ID: {$jobId}\n";
echo "\n";

try {
    // Get initial status
    $status = $iris->bloqs->getIngestionStatus($jobId);
    
    if (in_array($status['status'], ['completed', 'partial', 'failed', 'cancelled'])) {
        // Job is finished, just show final status
        displayStatus($status);
    } else {
        // Job is still running, watch progress
        echo "⏳ Watching progress in real-time (Ctrl+C to stop)...\n\n";
        
        $lastProgress = 0;
        
        $final = $iris->bloqs->waitForIngestion(
            $jobId,
            function($status) use (&$lastProgress) {
                $progress = $status['progress_percent'] ?? 0;
                $current = $status['current_file'] ?? '';
                $processed = $status['processed_files'] ?? 0;
                $total = $status['total_files'] ?? 0;
                $speed = $status['processing_speed'] ?? 0;
                $eta = $status['estimated_remaining'] ?? '';
                
                // Progress bar
                $bar = str_repeat('█', (int)($progress / 2));
                $spaces = str_repeat('░', 50 - (int)($progress / 2));
                
                echo "\r  [{$bar}{$spaces}] {$progress}%";
                echo " | {$processed}/{$total} files";
                echo " | {$speed} files/min";
                
                if ($eta) {
                    echo " | ETA: {$eta}";
                }
                
                echo "                    "; // Clear any leftover text
                
                if ($current) {
                    echo "\n  Current: " . truncate($current, 70) . "                    \r";
                    echo "\033[1A"; // Move cursor up one line
                }
                
                $lastProgress = $progress;
            }
        );
        
        echo "\n\n";
        displayStatus($final);
    }
    
} catch (Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
    exit(1);
}

// =============================================================================
// Helper Functions
// =============================================================================

function displayStatus(array $status): void
{
    $statusIcon = getStatusIcon($status['status']);
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Final Status\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    echo "Job ID: {$status['job_id']}\n";
    echo "Status: {$statusIcon} {$status['status']}\n";
    echo "Progress: {$status['progress_percent']}%\n";
    echo "Total Files: {$status['total_files']}\n";
    echo "Processed: {$status['processed_files']}\n";
    echo "Successful: {$status['successful_files']}\n";
    echo "Failed: {$status['failed_files']}\n";
    
    if (!empty($status['error_log'])) {
        echo "\n⚠ Errors:\n";
        
        foreach (array_slice($status['error_log'], 0, 10) as $error) {
            echo "  • {$error['file']}: {$error['error']}\n";
        }
        
        $errorCount = count($status['error_log']);
        if ($errorCount > 10) {
            echo "  ... and " . ($errorCount - 10) . " more errors\n";
        }
    }
    
    echo "\n";
}

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
