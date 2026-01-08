#!/usr/bin/env php
<?php
/**
 * Enable multiStep on agent 20
 */

require_once __DIR__ . '/autoload.php';

use IRIS\SDK\IRIS;

$iris = new IRIS([]);

$agentId = 20;

echo "Enabling multiStep on agent {$agentId}...\n";

// Get current settings
$agent = $iris->agents->get($agentId);
$settings = $agent->settings ?? [];

echo "Current settings: " . json_encode($settings, JSON_PRETTY_PRINT) . "\n\n";

// Add multiStep.enabled
$settings['multiStep'] = ['enabled' => true];

// Update agent
$iris->agents->update($agentId, [
    'settings' => $settings,
]);

// Verify
$updated = $iris->agents->get($agentId);
echo "Updated settings: " . json_encode($updated->settings ?? [], JSON_PRETTY_PRINT) . "\n";

echo "\n✅ Done!\n";
