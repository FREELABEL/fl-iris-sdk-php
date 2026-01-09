<?php
// Quick script to reset stuck jobs using existing API endpoints
require __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;

$iris = new IRIS([]);  // Uses .env config

// Get all jobs
$jobs = $iris->schedules->list(['agent_jobs_only' => true]);

echo "Found " . count($jobs) . " total jobs\n";

$stuckJobs = array_filter($jobs, function($job) {
    return ($job['status'] ?? '') === 'running' 
        && ($job['next_run_at'] ?? '') < date('c');
});

echo "Found " . count($stuckJobs) . " stuck jobs\n\n";

foreach ($stuckJobs as $job) {
    echo "Resetting job #{$job['id']}: {$job['task_name']}\n";
    
    try {
        // Calculate next run time based on frequency
        $now = new DateTime('now', new DateTimeZone($job['timezone'] ?? 'America/New_York'));
        $time = explode(':', $job['scheduled_time'] ?? '09:00');
        $now->setTime((int)$time[0], (int)$time[1]);
        
        // Add frequency offset
        switch ($job['frequency'] ?? 'daily') {
            case 'daily':
                $now->modify('+1 day');
                break;
            case 'weekly':
                $now->modify('+1 week');
                break;
            case 'monthly':
                $now->modify('+1 month');
                break;
            case 'once':
                // Don't reschedule
                echo "  → Skipping 'once' frequency job\n";
                continue 2;
        }
        
        // Update via PUT endpoint
        $result = $iris->schedules->update($job['id'], [
            'status' => 'scheduled',
            'next_run_at' => $now->format('Y-m-d\TH:i:s.000000\Z'),
        ]);
        
        echo "  ✅ Reset to: " . $now->format('Y-m-d H:i:s') . "\n";
    } catch (Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n";
    }
}

echo "\nDone!\n";
