<?php

require __DIR__ . '/vendor/autoload.php';

echo "=== Testing 'query' Alias for 'search' Parameter ===\n\n";

// Test 1: leads.list with query=
echo "Test 1: leads.list query=Dima\n";
$result1 = shell_exec('IRIS_ENV=production ./bin/iris sdk:call leads.list query=Dima --json 2>&1');
$data1 = json_decode($result1, true);
echo "Result: " . ($data1[0]['name'] ?? 'Not found') . "\n";
echo "Expected: Dima Semyansky\n";
echo ($data1[0]['name'] === 'Dima Semyansky' ? '✅ PASS' : '❌ FAIL') . "\n\n";

// Test 2: leads.list with search=
echo "Test 2: leads.list search=Dima\n";
$result2 = shell_exec('IRIS_ENV=production ./bin/iris sdk:call leads.list search=Dima --json 2>&1');
$data2 = json_decode($result2, true);
echo "Result: " . ($data2[0]['name'] ?? 'Not found') . "\n";
echo "Expected: Dima Semyansky\n";
echo ($data2[0]['name'] === 'Dima Semyansky' ? '✅ PASS' : '❌ FAIL') . "\n\n";

// Test 3: leads.search with query=
echo "Test 3: leads.search query=Martinez\n";
$result3 = shell_exec('IRIS_ENV=production ./bin/iris sdk:call leads.search query=Martinez --json 2>&1');
$data3 = json_decode($result3, true);
echo "Result: " . ($data3[0]['name'] ?? 'Not found') . "\n";
echo "Expected: Lisa Martinez\n";
echo ($data3[0]['name'] === 'Lisa Martinez' ? '✅ PASS' : '❌ FAIL') . "\n\n";

// Test 4: Precedence test (search should win)
echo "Test 4: leads.list query=John search=Dima (search should take precedence)\n";
$result4 = shell_exec('IRIS_ENV=production ./bin/iris sdk:call leads.list query=John search=Dima --json 2>&1');
$data4 = json_decode($result4, true);
echo "Result: " . ($data4[0]['name'] ?? 'Not found') . "\n";
echo "Expected: Dima Semyansky (not John)\n";
echo ($data4[0]['name'] === 'Dima Semyansky' ? '✅ PASS' : '❌ FAIL') . "\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "All tests completed successfully!\n";
