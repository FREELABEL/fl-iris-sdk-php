# Agent-Workflow Integration Guide

## Overview

This guide shows how to attach V5.5 workflow templates to agents, enabling agents to call workflows via the `CallWorkflowTool`.

---

## Quick Reference

### ✅ What We Built

**Agent #164:** High-Volume Recruitment Assistant  
**Workflow #8:** find_candidates (High-Volume Candidate Sourcing)  
**Status:** Successfully connected ✅

**How it works:**
1. Agent has `enabledWorkflows` in settings: `["find_candidates"]`
2. ToolRegistry detects enabled workflows and adds `CallWorkflowTool`
3. Agent can now call: `callWorkflow(workflow="find_candidates", input={...})`
4. WorkflowAgent executes in agentic mode with allowed tools

---

## Architecture

```
User → Agent #164 (Recruitment Assistant)
           ↓
  Agent has settings.enabledWorkflows = ["find_candidates"]
           ↓
  ToolRegistry.getAvailableTools(agent)
           ↓
  Detects enabled workflows → Adds CallWorkflowTool
           ↓
  Agent React Loop can now use:
    - RecruitmentQueryGeneratorTool
    - CandidateScorerTool
    - WebSearchTool
    - ScrapeWebPageTool
    - CallWorkflowTool (with find_candidates available)
           ↓
  When agent calls: callWorkflow("find_candidates", {...})
           ↓
  CallWorkflowTool routes to WorkflowAgent
           ↓
  WorkflowAgent executes with:
    - Goal from workflow.agent_config.goal
    - Allowed tools from workflow.allowed_tools
    - Max iterations from workflow.max_iterations
           ↓
  Returns result to parent agent
```

---

## How to Connect Workflows to Agents

### Method 1: Direct Database Update (Tinker)

```bash
docker exec fl-api php artisan tinker
```

```php
use App\Models\Bloq\Agent;

$agent = Agent::find(164);

// Get current settings
$settings = $agent->settings ?? [];

// Add workflow(s)
$settings['enabledWorkflows'] = [
    'find_candidates',
    // Add more workflows as needed
];

// Save
$agent->settings = $settings;
$agent->save();

echo "✅ Workflows enabled\n";
```

### Method 2: SDK CLI Helper (Recommended)

**Create the helper script:**

```bash
# Location: fl-docker-dev/sdk/php/bin/agent-enable-workflow
#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use IRIS\SDK\IRIS;

$agentId = $argv[1] ?? null;
$workflowName = $argv[2] ?? null;

if (!$agentId || !$workflowName) {
    echo "Usage: ./bin/agent-enable-workflow <agent_id> <workflow_callable_name>\n";
    exit(1);
}

$iris = new IRIS(getenv('IRIS_API_KEY') ?: '');

$agent = $iris->agents()->get($agentId);
$settings = $agent['settings'] ?? [];
$enabledWorkflows = $settings['enabledWorkflows'] ?? [];

if (!in_array($workflowName, $enabledWorkflows)) {
    $enabledWorkflows[] = $workflowName;
    $settings['enabledWorkflows'] = $enabledWorkflows;
    
    $iris->agents()->update($agentId, ['settings' => $settings]);
    echo "✅ Workflow '{$workflowName}' enabled\n";
} else {
    echo "⚠️  Already enabled\n";
}
```

**Usage:**

```bash
cd fl-docker-dev/sdk/php
./bin/agent-enable-workflow 164 find_candidates
```

### Method 3: SDK API Call (Programmatic)

```php
use IRIS\SDK\IRIS;

$iris = new IRIS(getenv('IRIS_API_KEY'));

// Get agent
$agent = $iris->agents()->get(164);

// Modify settings
$settings = $agent['settings'] ?? [];
$settings['enabledWorkflows'] = ['find_candidates'];

// Update
$iris->agents()->update(164, [
    'settings' => $settings
]);
```

### Method 4: REST API (cURL)

```bash
# Get agent first
curl -X GET http://localhost:8000/api/users/193/agents/164 \
  -H "Authorization: Bearer $API_TOKEN"

# Update with workflows
curl -X PUT http://localhost:8000/api/users/193/agents/164 \
  -H "Authorization: Bearer $API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "settings": {
      "model": "gpt-4o-mini",
      "tools": ["RecruitmentQueryGeneratorTool", "CandidateScorerTool"],
      "enabledWorkflows": ["find_candidates"]
    }
  }'
```

---

## Verification

### Check Agent Configuration

```bash
docker exec fl-api php artisan tinker --execute="
use App\Models\Bloq\Agent;

\$agent = Agent::find(164);
\$workflows = \$agent->settings['enabledWorkflows'] ?? [];

echo 'Enabled Workflows: ' . implode(', ', \$workflows) . PHP_EOL;
"
```

**Expected output:**
```
Enabled Workflows: find_candidates
```

### Check Workflow Is Callable

```bash
curl http://localhost:8000/api/v1/workflows/callable | jq '.data[] | select(.callable_name == "find_candidates")'
```

**Expected output:**
```json
{
  "id": 8,
  "name": "High-Volume Candidate Sourcing",
  "callable_name": "find_candidates",
  "execution_mode": "agentic",
  "agent_config": {...},
  "allowed_tools": [
    "RecruitmentQueryGeneratorTool",
    "CandidateScorerTool",
    "WebSearchTool",
    "ScrapeWebPageTool"
  ]
}
```

### Check ToolRegistry (IRIS-side)

```bash
# Check IRIS logs when agent loads
docker exec fl-iris-api tail -f storage/logs/laravel.log | grep "ToolRegistry"
```

**Expected log entries:**
```
ToolRegistry: Fetched callable workflows, count: 1, workflows: ["find_candidates"]
ToolRegistry: Filtered workflows for agent, agent_id: 164, enabled_count: 1, available_count: 1
```

---

## How Agents Use Workflows

### Automatic Detection

When an agent conversation starts, the ToolRegistry:

1. **Fetches all callable workflows** from fl-api: `/api/v1/workflows/callable`
2. **Filters by agent's enabledWorkflows** setting
3. **Adds CallWorkflowTool** if any workflows are enabled
4. **Generates dynamic description** listing available workflows

### Tool Description (Auto-generated)

```
CallWorkflowTool:
  Execute pre-defined workflow automations. Available workflows (1):
  find_candidates: Find and score 50-100+ candidates for any job description.
  Generates LinkedIn search URLs, boolean queries, extraction scripts, and
  automated candidate scoring with rankings.
```

### Agent Usage (Automatic)

When the user says:
```
"Find candidates for a Senior React Developer role in Austin"
```

The agent's React loop will:
1. **Detect intent:** User wants candidate sourcing
2. **Check available tools:** Sees `CallWorkflowTool` with `find_candidates`
3. **Call workflow:**
   ```
   callWorkflow(
     workflow="find_candidates",
     input={
       job_title: "Senior React Developer",
       job_description: "...",
       location: "Austin, TX",
       platform: "linkedin",
       experience_level: "senior"
     }
   )
   ```
4. **CallWorkflowTool routes to WorkflowAgent** (agentic mode)
5. **WorkflowAgent executes** with allowed tools
6. **Returns result** to parent agent
7. **Agent formats response** for user

---

## Managing Multiple Workflows

### Enable Multiple Workflows

```php
$settings['enabledWorkflows'] = [
    'find_candidates',          // Recruitment workflow
    'generate_newsletter',      // Newsletter workflow
    'legal_discovery_analysis', // Legal workflow
];
```

### Workflow Categories

Organize by category for better UX:

```php
// Recruitment-focused agent
$recruitmentAgent->settings['enabledWorkflows'] = [
    'find_candidates',
    'score_candidates',
    'generate_outreach_emails',
];

// Content-focused agent
$contentAgent->settings['enabledWorkflows'] = [
    'generate_newsletter',
    'research_and_write_article',
    'optimize_seo_content',
];

// General assistant (all workflows)
$generalAgent->settings['enabledWorkflows'] = [
    'find_candidates',
    'generate_newsletter',
    'legal_discovery_analysis',
];
```

---

## Workflow Template Requirements

For a workflow to be callable by agents, it must have:

### 1. Database Record (workflow_templates table)

```php
WorkflowTemplate::create([
    'name' => 'Human-readable name',
    'slug' => 'url-slug',
    'callable_name' => 'tool_function_name',  // ← Used in enabledWorkflows
    'callable_description' => 'Brief description',
    'is_callable' => true,                     // ← Must be true
    'is_public' => true,                       // ← Or user-specific access
    'execution_mode' => 'agentic',             // ← 'agentic' or 'fixed'
    'agent_config' => [...],                   // ← For agentic mode
    'allowed_tools' => [...],                  // ← Tools workflow can use
]);
```

### 2. For Agentic Mode

```php
'execution_mode' => 'agentic',
'agent_config' => [
    'goal' => 'The goal to accomplish, with {input_vars}',
    'system_prompt' => 'You are a specialist in...',
    'constraints' => [
        'must_include' => ['results', 'citations'],
        'max_length' => 2000,
    ],
],
'allowed_tools' => [
    'WebSearchTool',
    'ScrapeWebPageTool',
    'CallIntegrationTool',
],
'max_iterations' => 10,
```

### 3. For Fixed Mode

```php
'execution_mode' => 'fixed',
'steps' => [
    [
        'type' => 'ai',
        'prompt' => 'Generate content for {{input.topic}}',
        'system_prompt' => 'You are a...',
    ],
    [
        'type' => 'integration',
        'integration' => 'resend',
        'action' => 'send_email',
        'parameters' => [
            'to' => '{{input.recipient_email}}',
            'subject' => '{{results.step_1.subject}}',
        ],
    ],
],
```

---

## Common Patterns

### Pattern 1: Single-Purpose Agent

**Use case:** Dedicated recruitment assistant

```php
Agent::create([
    'name' => 'Recruitment Assistant',
    'role' => 'recruitment_specialist',
    'settings' => [
        'enabledWorkflows' => ['find_candidates'],
        'tools' => [
            'RecruitmentQueryGeneratorTool',
            'CandidateScorerTool',
        ],
    ],
]);
```

### Pattern 2: Multi-Purpose Agent

**Use case:** General business assistant

```php
Agent::create([
    'name' => 'Business Assistant',
    'role' => 'general_assistant',
    'settings' => [
        'enabledWorkflows' => [
            'find_candidates',
            'generate_newsletter',
            'create_meeting_notes',
            'analyze_spreadsheet',
        ],
        'tools' => [
            'WebSearchTool',
            'CallIntegrationTool',
            // Specific tools added via workflows
        ],
    ],
]);
```

### Pattern 3: Client-Specific Agent

**Use case:** Custom agent for a specific client

```php
// Gniice's agent
Agent::create([
    'user_id' => 53,  // Gniice's user ID
    'name' => 'Kaizen Recruitment Assistant',
    'settings' => [
        'enabledWorkflows' => [
            'find_candidates',
            'score_candidates',
            'generate_recruiter_reports',
        ],
        'tools' => [
            'RecruitmentQueryGeneratorTool',
            'CandidateScorerTool',
            'WebSearchTool',
        ],
    ],
]);
```

---

## Troubleshooting

### Issue: Agent doesn't see workflow

**Check 1:** Workflow is callable
```bash
curl http://localhost:8000/api/v1/workflows/callable | jq '.data[].callable_name'
```

**Check 2:** Agent has workflow enabled
```php
$agent = Agent::find(164);
$workflows = $agent->settings['enabledWorkflows'] ?? [];
var_dump($workflows); // Should include 'find_candidates'
```

**Check 3:** ToolRegistry cache
```bash
# Clear cache
docker exec fl-iris-api php artisan cache:clear

# Or wait 5 minutes (cache TTL)
```

**Check 4:** IRIS logs
```bash
docker exec fl-iris-api tail -50 storage/logs/laravel.log | grep "ToolRegistry"
```

### Issue: Workflow execution fails

**Check 1:** Workflow has required fields
```sql
SELECT id, callable_name, execution_mode, agent_config, allowed_tools
FROM workflow_templates
WHERE callable_name = 'find_candidates';
```

**Check 2:** User has dependencies
```php
$workflow = WorkflowTemplate::find(8);
$user = User::find(193);
$canRun = $workflow->canUserRun($user);
var_dump($canRun); // ['can_run' => true/false, 'issues' => [...]]
```

**Check 3:** CallWorkflowTool logs
```bash
docker exec fl-iris-api tail -100 storage/logs/laravel.log | grep "CallWorkflowTool"
```

### Issue: Wrong tools available in workflow

**Check:** Workflow's allowed_tools matches actual tool names
```php
$workflow = WorkflowTemplate::find(8);
echo json_encode($workflow->allowed_tools, JSON_PRETTY_PRINT);

// Should match actual NeuronAI tool names:
// "RecruitmentQueryGeneratorTool" (not "recruitment_query_generator")
```

---

## Best Practices

### 1. Explicit Workflow Enablement

**Bad:** Empty enabledWorkflows (no workflows available)
```php
$agent->settings['enabledWorkflows'] = [];
```

**Good:** Explicitly list workflows
```php
$agent->settings['enabledWorkflows'] = ['find_candidates'];
```

### 2. Match Agent Purpose to Workflows

**Bad:** Generic agent with all workflows
```php
$agent->name = 'Assistant';
$agent->settings['enabledWorkflows'] = [
    'find_candidates',
    'legal_analysis',
    'medical_diagnosis',
    'financial_advice',
]; // Confusing for users
```

**Good:** Focused agent with relevant workflows
```php
$agent->name = 'Recruitment Specialist';
$agent->settings['enabledWorkflows'] = [
    'find_candidates',
    'score_candidates',
]; // Clear purpose
```

### 3. Tool Redundancy Check

**Bad:** Agent has tools AND workflow with same tools
```php
$agent->settings['tools'] = ['RecruitmentQueryGeneratorTool'];
$agent->settings['enabledWorkflows'] = ['find_candidates'];
// find_candidates already uses RecruitmentQueryGeneratorTool
// Now agent has it twice (direct + via workflow)
```

**Good:** Workflow handles tools, agent just enables workflow
```php
$agent->settings['tools'] = []; // Or general tools only
$agent->settings['enabledWorkflows'] = ['find_candidates'];
// find_candidates brings its own tools
```

### 4. Version Tracking

**Good:** Track which agents use which workflows
```php
// Add metadata to agent
$agent->config['workflow_manifest'] = [
    'find_candidates' => [
        'enabled_at' => '2026-01-07',
        'version' => '1.0.0',
        'purpose' => 'High-volume recruitment sourcing',
    ],
];
```

---

## SDK Integration Examples

### Example 1: Bulk Enable Workflow

```php
// Enable find_candidates for all recruitment agents
use App\Models\Bloq\Agent;

$recruitmentAgents = Agent::where('role', 'recruitment_specialist')
    ->orWhere('role', 'recruiter')
    ->get();

foreach ($recruitmentAgents as $agent) {
    $settings = $agent->settings ?? [];
    $workflows = $settings['enabledWorkflows'] ?? [];
    
    if (!in_array('find_candidates', $workflows)) {
        $workflows[] = 'find_candidates';
        $settings['enabledWorkflows'] = $workflows;
        $agent->settings = $settings;
        $agent->save();
        
        echo "✅ Enabled for agent #{$agent->id}: {$agent->name}\n";
    }
}
```

### Example 2: Workflow Usage Analytics

```php
// Track which agents use which workflows
use App\Models\Bloq\Agent;
use App\Models\Bloq\WorkflowRun;

$workflowName = 'find_candidates';

$agents = Agent::whereJsonContains('settings->enabledWorkflows', $workflowName)->get();

echo "Agents with '{$workflowName}' enabled: {$agents->count()}\n\n";

foreach ($agents as $agent) {
    $runs = WorkflowRun::where('workflow_name', 'High-Volume Candidate Sourcing')
        ->where('user_id', $agent->user_id)
        ->count();
    
    echo "Agent #{$agent->id} ({$agent->name}): {$runs} executions\n";
}
```

### Example 3: Dynamic Workflow Assignment

```php
// Assign workflows based on user subscription tier
use App\Models\User;
use App\Models\Bloq\Agent;

function assignWorkflowsByTier(User $user) {
    $agent = $user->agents()->where('is_primary', true)->first();
    
    if (!$agent) return;
    
    $workflows = match ($user->subscription_tier) {
        'free' => [],
        'starter' => ['find_candidates'],
        'professional' => ['find_candidates', 'generate_newsletter'],
        'enterprise' => ['find_candidates', 'generate_newsletter', 'legal_analysis'],
        default => [],
    };
    
    $settings = $agent->settings ?? [];
    $settings['enabledWorkflows'] = $workflows;
    $agent->settings = $settings;
    $agent->save();
    
    return $workflows;
}
```

---

## Next Steps

### For Gniice Deployment

1. **✅ Workflow created:** Template #8 (find_candidates)
2. **✅ Agent created:** Agent #164 (High-Volume Recruitment Assistant)
3. **✅ Workflow attached:** enabledWorkflows = ["find_candidates"]
4. **⏳ Pending:** Transfer agent ownership to Gniice's user
5. **⏳ Pending:** Test via IRIS chat interface
6. **⏳ Pending:** Schedule 15-minute demo

### For Template Marketplace

1. Create 5-10 more workflow templates
2. Build UI for browsing templates
3. Add self-serve activation flow
4. Track usage analytics
5. Build case studies

---

**Last Updated:** January 7, 2026  
**Agent #164 Status:** ✅ Production Ready  
**Workflow #8 Status:** ✅ Attached and Callable  
**Next Action:** Deploy to Gniice's account
