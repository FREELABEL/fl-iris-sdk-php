#!/usr/bin/env php
<?php

/**
 * Add Agent Deliverable for Rodney Mayo
 * 
 * This script helps add his trained AI agent as a deliverable to his lead.
 */

require __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$iris = new IRIS([
    'api_key' => $_ENV['IRIS_API_KEY'],
    'user_id' => 193,
]);

$rodneyLeadId = 24; // Rodney Mayo's lead ID

echo "\n🎯 Managing Deliverables for Rodney Mayo (Lead ID: {$rodneyLeadId})\n";
echo str_repeat('=', 60) . "\n\n";

// Step 1: List existing deliverables
echo "📦 Current Deliverables:\n";
echo str_repeat('-', 60) . "\n";

try {
    $deliverables = $iris->leads->deliverables($rodneyLeadId)->list();
    
    if (empty($deliverables)) {
        echo "No deliverables found.\n\n";
    } else {
        foreach ($deliverables as $i => $del) {
            echo ($i + 1) . ". {$del['title']}\n";
            echo "   Type: {$del['type']}\n";
            echo "   URL: {$del['url']}\n";
            if ($del['custom_request']) {
                echo "   Linked to: {$del['custom_request']['title']} (\${$del['custom_request']['price']})\n";
            }
            echo "   Created: {$del['created_at']}\n\n";
        }
    }
    
    // Step 2: Add new agent deliverable
    echo str_repeat('━', 60) . "\n";
    echo "➕ Adding AI Agent Deliverable\n";
    echo str_repeat('━', 60) . "\n\n";
    
    // You can customize these values:
    $agentUrl = 'https://app.heyiris.io/agents/123'; // Replace with actual agent URL
    $agentTitle = 'Trained AI Agent - NCMA Assistant';
    $description = 'Your custom-trained AI agent is ready! It has been trained on all your reports and can provide testimonial recommendations.';
    
    echo "Agent Details:\n";
    echo "  • Title: {$agentTitle}\n";
    echo "  • URL: {$agentUrl}\n";
    echo "  • Description: {$description}\n\n";
    
    $response = readline("Add this deliverable? (yes/no): ");
    
    if (strtolower(trim($response)) === 'yes') {
        $newDeliverable = $iris->leads->deliverables($rodneyLeadId)->create([
            'type' => 'link',
            'title' => $agentTitle,
            'external_url' => $agentUrl,
        ]);
        
        echo "\n✅ Deliverable added successfully!\n";
        echo "   ID: {$newDeliverable['id']}\n";
        echo "   Title: {$newDeliverable['title']}\n";
        echo "   URL: {$newDeliverable['url']}\n\n";
        
        // Step 3: List updated deliverables
        echo str_repeat('━', 60) . "\n";
        echo "📦 Updated Deliverables:\n";
        echo str_repeat('-', 60) . "\n";
        
        $updatedDeliverables = $iris->leads->deliverables($rodneyLeadId)->list();
        foreach ($updatedDeliverables as $i => $del) {
            $new = $del['id'] === $newDeliverable['id'] ? ' [NEW]' : '';
            echo ($i + 1) . ". {$del['title']}{$new}\n";
            echo "   Type: {$del['type']}\n";
            echo "   URL: {$del['url']}\n\n";
        }
        
        // Step 4: Option to send email notification
        echo str_repeat('━', 60) . "\n";
        echo "📧 Email Notification\n";
        echo str_repeat('━', 60) . "\n\n";
        
        $sendEmail = readline("Send email notification to Rodney? (yes/no): ");
        
        if (strtolower(trim($sendEmail)) === 'yes') {
            try {
                $emailResult = $iris->leads->deliverables($rodneyLeadId)->send([
                    'deliverable_ids' => [$newDeliverable['id']],
                    'subject' => 'Your AI Agent is Ready!',
                    'message' => $description,
                ]);
                
                echo "\n✅ Email sent successfully!\n";
            } catch (\Exception $e) {
                echo "\n⚠️  Email sending may not be fully implemented yet: {$e->getMessage()}\n";
                echo "   You can manually notify Rodney about the new deliverable.\n";
            }
        }
        
    } else {
        echo "\n❌ Cancelled. No deliverable added.\n";
    }
    
    echo "\n✅ Complete!\n\n";
    
} catch (\Exception $e) {
    echo "\n❌ Error: {$e->getMessage()}\n";
    echo "   File: {$e->getFile()}:{$e->getLine()}\n\n";
    exit(1);
}

// Print CLI examples
echo str_repeat('━', 60) . "\n";
echo "💡 CLI Commands for Deliverables\n";
echo str_repeat('━', 60) . "\n\n";

echo "# List deliverables:\n";
echo "./bin/iris sdk:call leads.deliverables.list {$rodneyLeadId}\n\n";

echo "# Add link deliverable:\n";
echo "./bin/iris sdk:call leads.deliverables.create {$rodneyLeadId} type=link title='AI Agent' external_url='https://app.heyiris.io/agents/123'\n\n";

echo "# Get deliverable count:\n";
echo "./bin/iris sdk:call leads.aggregation.get {$rodneyLeadId} --json | grep deliverables\n\n";
