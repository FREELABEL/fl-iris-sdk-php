<?php
require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$iris = new IRIS\SDK\IRIS([
    'api_key' => $_ENV['IRIS_API_KEY'],
    'user_id' => (int)$_ENV['IRIS_USER_ID'],
]);

echo "Adding note to Juan's lead (ID: 412)...\n";

$note = $iris->leads->addNote(
    412, 
    '✅ AGENT PAGE LIVE - Email sent via Julissa (Email ID: 49) notifying Tha Juan that his agent page is now live at https://app.heyiris.io/agent/simple/369?bloq=40. Informed him that his agent\'s phone number is displayed on the landing page for customers to call. Thanked him for patience during UI improvements. Contact provided: support@heyiris.io, 713-912-7520, calendar link.',
    ['type' => 'outreach']
);

echo "✅ Note added successfully!\n";
echo json_encode($note, JSON_PRETTY_PRINT);
echo "\n";
