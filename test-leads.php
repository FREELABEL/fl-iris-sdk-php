<?php
require_once __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;
use Dotenv\Dotenv;

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
}

$iris = new IRIS([
    'api_key' => $_ENV['IRIS_API_KEY'] ?? getenv('IRIS_API_KEY'),
    'user_id' => (int) ($_ENV['IRIS_USER_ID'] ?? getenv('IRIS_USER_ID') ?? 193),
]);

// Get specific leads we know about
$leadIds = [510, 460, 511, 512, 513, 514, 515];

echo "=== Leads Status ===\n\n";

foreach ($leadIds as $leadId) {
    try {
        $lead = $iris->leads->get($leadId);
        $contact = (array)($lead->contact_info ?? []);
        $hasEmail = !empty($contact['email']) ? '✓' : '✗';
        $hasPhone = !empty($contact['phone']) ? '✓' : '✗';
        $hasCompany = !empty($contact['company']) ? '✓' : '✗';
        
        $name = $lead->nickname ?? $lead->full_name ?? 'Unknown';
        
        echo "#{$leadId} - {$name}\n";
        echo "   Email: {$hasEmail} " . ($contact['email'] ?? 'MISSING') . "\n";
        echo "   Phone: {$hasPhone} " . ($contact['phone'] ?? 'MISSING') . "\n";
        echo "   Company: {$hasCompany} " . ($contact['company'] ?? 'MISSING') . "\n";
        echo "\n";
    } catch (Exception $e) {
        echo "#{$leadId} - Not found or error: " . $e->getMessage() . "\n\n";
    }
}
