#!/usr/bin/env php
<?php
/**
 * Debug: Chat Response Structure
 */

require_once __DIR__ . '/autoload.php';

use IRIS\SDK\IRIS;

// Load .env
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            [$key, $value] = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

// IRIS SDK auto-loads from .env (respects IRIS_ENV=local)
$iris = new IRIS([]);

$agentId = $argv[1] ?? 402;

echo "🔍 Debug Chat Response for Agent {$agentId}\n\n";

// Test web search prompt
$prompt = $argv[2] ?? "What is the latest AI news this week? Search the web and give me 3 specific headlines.";
echo "Prompt: {$prompt}\n\n";

try {
    $response = $iris->agents->chat($agentId, [
        ['role' => 'user', 'content' => $prompt],
    ], ['bloq_id' => 40]);

    echo "Response type: " . gettype($response) . "\n";
    echo "Response class: " . (is_object($response) ? get_class($response) : 'N/A') . "\n\n";

    // Dump all properties
    echo "Properties:\n";
    if (is_object($response)) {
        foreach (get_object_vars($response) as $key => $value) {
            $display = is_array($value) ? json_encode($value) : (string) $value;
            $display = strlen($display) > 200 ? substr($display, 0, 200) . '...' : $display;
            echo "  {$key}: {$display}\n";
        }
    }

    echo "\nJSON dump:\n";
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

} catch (Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
