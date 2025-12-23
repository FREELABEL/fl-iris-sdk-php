#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;

// Initialize SDK
$iris = new IRIS([
    'api_key' => getenv('IRIS_API_KEY'),
    'user_id' => 193,
]);

// Update agent 358 with the full configuration
$agentData = [
    'name' => 'Talent Recruiter Agent',
    'type' => 'content',
    'icon' => 'fas fa-pen-fancy',
    'isHuman' => false,
    'description' => '',
    'initial_prompt' => 'You are an AI recruitment assistant designed to support recruiters and hiring professionals in sourcing, evaluating, and onboarding top talent for various organizations. When users request assistance, you will utilize the available tools, such as the recruiter tool, to facilitate the recruitment process efficiently. If provided with a job description, you will initiate the recruiter tool process to identify suitable candidates, generate candidate profiles, or perform relevant screening tasks aligned with the role\'s requirements. Your responses should be professional, clear, and tailored to meet the specific needs of the user, ensuring an effective and streamlined recruitment experience.

## CONTEXT
Your primary purpose is to assist recruiters and hiring managers in the end-to-end recruitment process, from sourcing candidates to onboarding. You leverage available tools to optimize candidate identification, evaluation, and engagement, ultimately helping organizations secure the best talent efficiently.

## ROLE & COMMUNICATION STYLE
You are a professional, knowledgeable, and helpful recruitment assistant. Your tone is courteous, clear, and precise, aiming to facilitate a seamless recruitment experience. You communicate with authority and expertise, providing actionable insights and structured responses tailored to the user\'s needs.

## CORE KNOWLEDGE / DATA
- You are equipped to understand various job descriptions across multiple industries.
- You can generate candidate profiles based on role requirements.
- You are familiar with screening criteria, sourcing strategies, and onboarding best practices.
- You have access to tools for candidate identification, profile generation, and screening tasks.

## INTERACTION FLOW / COMMANDS
- When a user provides a job description, initiate the recruiter tool process to identify suitable candidates.
- Use commands such as initiating candidate sourcing, generating candidate profiles, or screening candidates as needed.
- Respond to user requests with clear, structured, and professional outputs.
- If user inputs commands like \'!help\' or \'!plan\', provide appropriate guidance or process outlines.
- Always aim to streamline the recruitment process by leveraging available tools efficiently.

## BOUNDARIES
- Do not provide personal or sensitive candidate information unless explicitly authorized.
- Avoid making assumptions about candidate qualifications beyond the provided data.
- Refrain from providing legal, financial, or confidential hiring advice.
- Do not engage in discriminatory or biased language.
- Do not offer guarantees about candidate suitability or hiring outcomes.

## OUTPUT FORMAT
- Responses should be professional, clear, and well-structured.
- Use bullet points or numbered lists where appropriate to enhance clarity.
- Maintain a formal tone and avoid informal language.
- When applicable, provide summaries, candidate profiles, or step-by-step processes in clearly labeled sections.

## EXAMPLE SCENARIO
User provides a job description for a Software Engineer role. You initiate the recruiter tool to find suitable candidates matching the technical skills, experience level, and location specified. You generate candidate profiles with summaries of their qualifications and suggest the next steps for screening or outreach.',
    'role' => '',
    'email' => '',
    'phone_number' => '',
    'config' => [
        'model' => 'gpt-4o-mini-2024-07-18',
        'temperature' => 0.7,
        'maxTokens' => 2048,
        'modelName' => 'gpt-4o-mini-2024-07-18',
        'provider' => 'openai',
    ],
    'is_favorite' => false,
    'last_used_at' => null,
    'usage_count' => 0,
    'useAdvancedPersonality' => false,
    'personalityData' => [],
    'personalityTraits' => '',
    'fileAttachments' => [],
    'structuredOutput' => [
        'mode' => 'text',
        'schema' => null,
        'enabled' => false,
        'description' => null,
    ],
    'is_public' => false,
    'public_name' => '',
    'public_description' => '',
    'public_slug' => '',
    'allow_copy' => true,
    'settings' => [
        'preferredModel' => 'cloud',
        'responseMode' => 'balanced',
        'communicationStyle' => 'professional',
        'responseLength' => 'balanced',
        'contextWindow' => '10',
        'fileContextMode' => 'basic',
        'learningMode' => 'adaptive',
        'useKnowledgeBase' => true,
        'webAccess' => false,
        'memoryPersistence' => true,
        'canCreateContent' => true,
        'canAnalyzeData' => false,
        'canManageTasks' => false,
        'functionCalling' => false,
        'functions' => [],
        'mcpEnabled' => false,
        'mcpServers' => [],
        'agentIntegrations' => [
            'gmail' => false,
            'slack' => false,
            'github' => false,
            'trello' => false,
            'discord' => false,
            'google-drive' => false,
            'google-calendar' => false,
            'credit-repair-ai' => false,
        ],
        'offlineConfig' => [
            'apiKey' => null,
            'authType' => 'none',
            'endpoint' => 'http://localhost:11434',
            'provider' => 'ollama',
            'maxTokens' => null,
            'modelName' => null,
            'temperature' => null,
            'customHeaders' => [],
        ],
        'ollamaConfig' => [
            'apiKey' => null,
            'endpoint' => 'http://localhost:11434',
            'maxTokens' => null,
            'modelName' => null,
            'temperature' => null,
        ],
        'openRouterConfig' => [
            'apiKey' => null,
            'maxTokens' => 2048,
            'modelName' => null,
            'temperature' => 0.7,
        ],
        'enabledFunctions' => [
            'travelAgent' => false,
            'deepResearch' => false,
            'brandAnalytics' => false,
            'marketResearch' => false,
            'staffManagement' => false,
            'businessProposal' => false,
            'eventCoordination' => false,
        ],
        'schedule' => [
            'enabled' => false,
            'timezone' => 'America/New_York',
            'frequency' => 'always_on',
            'active_hours' => [
                'end' => '17:00',
                'start' => '09:00',
            ],
            'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'recurring_tasks' => [],
        ],
        'role' => null,
        'isHuman' => false,
        'identity' => [
            'email' => '',
            'phone' => '',
            'role' => '',
            'company' => '',
            'replyTo' => '',
            'calendarLink' => '',
        ],
        'personalityData' => [],
        'useAdvancedPersonality' => false,
    ],
];

echo "🤖 Updating Agent #358: Talent Recruiter Agent\n\n";

try {
    $agent = $iris->agents->update(358, $agentData);
    
    echo "✅ Agent updated successfully!\n\n";
    echo "📋 Agent Details:\n";
    echo "   ID: {$agent->id}\n";
    echo "   Name: {$agent->name}\n";
    echo "   Type: {$agent->type}\n";
    echo "   Model: {$agent->model}\n";
    echo "   URL: https://app.heyiris.io/agent/simple/358?bloq=208\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
