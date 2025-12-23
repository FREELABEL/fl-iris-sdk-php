#!/usr/bin/env php
<?php
/**
 * Agent Management Example
 * 
 * Demonstrates full CRUD operations for AI Agents via the IRIS SDK.
 * 
 * Usage:
 *   IRIS_ENV=production php examples/agent-management.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use IRIS\SDK\IRIS;
use IRIS\SDK\Resources\Agents\AgentConfig;

// Initialize SDK
$iris = new IRIS([
    'api_key' => getenv('IRIS_API_KEY'),
    'user_id' => (int) getenv('IRIS_USER_ID') ?: 193,
]);

echo "🤖 IRIS Agent Management Demo\n";
echo "================================\n\n";

try {
    // 1. List Agents
    echo "📋 Step 1: List All Agents\n";
    echo "----------------------------\n";
    $agents = $iris->agents->list(['per_page' => 5]);
    
    foreach ($agents as $agent) {
        echo "  • #{$agent->id}: {$agent->name} ({$agent->model})\n";
    }
    echo "\n";

    // 2. Create a New Agent
    echo "✨ Step 2: Create New Agent\n";
    echo "----------------------------\n";
    
    $config = new AgentConfig(
        name: 'Demo Marketing Agent',
        prompt: 'You are a professional marketing assistant specializing in email campaigns and social media content.',
        model: 'gpt-4o-mini',
        type: 'assistant',
        integrations: ['gmail'],
    );
    
    $newAgent = $iris->agents->create($config);
    echo "✅ Created: #{$newAgent->id} - {$newAgent->name}\n";
    echo "   Model: {$newAgent->model}\n";
    echo "   Type: {$newAgent->type}\n\n";
    
    $agentId = $newAgent->id;

    // 3. Update Agent Configuration
    echo "📝 Step 3: Update Agent Settings\n";
    echo "---------------------------------\n";
    
    $updatedAgent = $iris->agents->update($agentId, [
        'name' => 'Advanced Marketing Agent',
        'prompt' => 'You are an expert marketing strategist with deep knowledge of SEO, content marketing, and conversion optimization.',
        'config' => [
            'model' => 'gpt-4o-mini-2024-07-18',
            'temperature' => 0.7,
            'maxTokens' => 2048,
        ],
        'settings' => [
            'communicationStyle' => 'professional',
            'responseMode' => 'balanced',
            'responseLength' => 'medium',
            'webAccess' => false,
            'functionCalling' => true,
        ],
    ]);
    
    echo "✅ Updated: {$updatedAgent->name}\n";
    echo "   Prompt: " . substr($updatedAgent->prompt, 0, 80) . "...\n\n";

    // 4. Chat with Agent
    echo "💬 Step 4: Chat with Agent\n";
    echo "---------------------------\n";
    
    $response = $iris->agents->chat($agentId, [
        ['role' => 'user', 'content' => 'Write a catchy subject line for our new product launch email.']
    ]);
    
    echo "User: Write a catchy subject line for our new product launch email.\n";
    echo "Agent: {$response->content}\n\n";

    // 5. Update Recruiter Agent (Example from User Request)
    echo "🎯 Step 5: Update Existing Agent #358\n";
    echo "---------------------------------------\n";
    
    $recruiterAgent = $iris->agents->update(358, [
        'name' => 'Talent Recruiter Agent',
        'type' => 'content',
        'icon' => 'fas fa-user-tie',
        'initial_prompt' => 'You are an AI recruitment assistant designed to support recruiters and hiring professionals in sourcing, evaluating, and onboarding top talent for various organizations.

## ROLE & COMMUNICATION STYLE
You are a professional, knowledgeable, and helpful recruitment assistant. Your tone is courteous, clear, and precise, aiming to facilitate a seamless recruitment experience.

## CORE KNOWLEDGE
- Understanding job descriptions across multiple industries
- Generating candidate profiles based on role requirements
- Screening criteria and sourcing strategies
- Onboarding best practices

## INTERACTION FLOW
- When a user provides a job description, initiate the recruiter tool process
- Generate candidate profiles with qualification summaries
- Provide next steps for screening or outreach',
        'config' => [
            'model' => 'gpt-4o-mini-2024-07-18',
            'temperature' => 0.7,
            'maxTokens' => 2048,
            'provider' => 'openai',
        ],
        'settings' => [
            'communicationStyle' => 'professional',
            'responseMode' => 'balanced',
            'responseLength' => 'balanced',
            'functionCalling' => false,
        ],
    ]);
    
    echo "✅ Updated Recruiter Agent: {$recruiterAgent->name}\n";
    echo "   URL: https://app.heyiris.io/agent/simple/358?bloq=208\n\n";

    // 6. Delete Demo Agent (Cleanup)
    echo "🗑️  Step 6: Delete Demo Agent\n";
    echo "------------------------------\n";
    
    $iris->agents->delete($agentId);
    echo "✅ Deleted agent #{$agentId}\n\n";

    // Summary
    echo "✨ Demo Complete!\n";
    echo "================\n";
    echo "All agent operations executed successfully:\n";
    echo "  ✓ List agents\n";
    echo "  ✓ Create agent\n";
    echo "  ✓ Update agent\n";
    echo "  ✓ Chat with agent\n";
    echo "  ✓ Delete agent\n";

} catch (\IRIS\SDK\Exceptions\AuthException $e) {
    echo "❌ Authentication Error: {$e->getMessage()}\n";
    echo "\nPlease ensure you have valid credentials:\n";
    echo "  export IRIS_API_KEY=your_token_here\n";
    echo "  export IRIS_USER_ID=193\n";
    exit(1);
} catch (\IRIS\SDK\Exceptions\IRISException $e) {
    echo "❌ Error: {$e->getMessage()}\n";
    exit(1);
}
