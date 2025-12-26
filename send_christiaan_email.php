<?php
require 'vendor/autoload.php';

use IRIS\SDK\IRIS;

// Use production environment
putenv('IRIS_ENV=production');

// Initialize IRIS with empty options (it will auto-load from .env)
$iris = new IRIS([]);

$emailContent = "Hi Christiaan,

We've been hard at work building out the specialized AI ecosystem we discussed for your pilot crew and travel tech business. I'm excited to let you know that your first set of AI agents is now live and ready for testing!

Here’s what we’ve deployed for you:

1. AirPolly AI (Airline Crew Specialist)
This agent is specifically tuned for airline and pilot crew workflows. It can assist with travel logistics, location-based info, and streamlining communication for crews on the move.

2. Voyage Lead Scout (Hospitality Growth Agent)
To support your growth in the hotel Wi-Fi and travel software space, we built this custom lead generation agent. It is pre-configured to scout for hotel and hospitality prospects in Mexico and other key hubs, generating detailed reports with contact info automatically.

You can access both of these agents now through your FreeLabel dashboard under \"Deliverables\".

I’d love to set up a quick 10-minute demo to show you how to trigger the Voyage Lead Scout to find your next set of prospects.

Let me know when you have a moment to connect!

Best,
Alex Mayo
alex@freelabel.net";

try {
    echo "Sending email to info@sgmdigital.com...\n";
    $result = $iris->leads->deliverables(75)->send([
        'deliverable_ids' => [361, 362],
        'recipient_emails' => ['info@sgmdigital.com'],
        'subject' => 'Your AI Ecosystem is Live: Specialized Agents for your Pilot Crew & Hospitality Lead Gen',
        'email_content' => $emailContent
    ]);

    echo "Email sent successfully!\n";
    print_r($result);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
