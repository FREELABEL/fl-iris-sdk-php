#!/usr/bin/env php
<?php
/**
 * Debug Agent #387 File Attachments
 */

require __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;
use IRIS\SDK\Auth\CredentialStore;

$store = new CredentialStore();
$config = $store->toConfigArray();
$iris = new IRIS($config);

echo "=== Agent #387 Debug ===\n\n";

// Get agent
$agent = $iris->agents->get(387);

echo "Agent Details:\n";
echo "  ID: {$agent->id}\n";
echo "  Name: {$agent->name}\n";
echo "  Bloq ID: " . ($agent->bloqId ?? 'null') . "\n\n";

echo "File Attachments:\n";
echo "  Count: " . count($agent->fileAttachments) . "\n";
echo "  Raw Data:\n";
print_r($agent->fileAttachments);

echo "\n\nRaw Agent JSON:\n";
echo json_encode($agent->toArray(), JSON_PRETTY_PRINT) . "\n";

// Test vector query
echo "\n\n=== Test Vector Query ===\n";
try {
    $results = $iris->rag->query('medications morning', ['agent_id' => 387], 3);
    echo "Found " . count($results) . " results\n";
    foreach ($results as $result) {
        echo "\n---\n";
        echo "Score: {$result->score}\n";
        echo "Content: " . substr($result->content, 0, 200) . "...\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
