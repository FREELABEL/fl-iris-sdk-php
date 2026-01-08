#!/usr/bin/env php
<?php
/**
 * Simple V5 test
 */

require_once __DIR__ . '/autoload.php';

use IRIS\SDK\IRIS;

$iris = new IRIS([]);

echo "Testing V5 multiStep...\n";

try {
    $response = $iris->agents->multiStep(20, "What is 2+2?", [
        'bloq_id' => 40,
    ]);

    echo "Response type: " . gettype($response) . "\n";
    echo "Response class: " . get_class($response) . "\n";

    // Dump raw response
    print_r($response);

} catch (Exception $e) {
    echo "Error: {$e->getMessage()}\n";
}
