# IRIS PHP SDK

Official PHP SDK for the **IRIS AI Platform** - Build intelligent agents, execute multi-step workflows, and manage leads with comprehensive CRM functionality.

## 🚀 Quick Examples

```bash
# 🎯 Update lead status and add task
./bin/iris sdk:call leads.update 412 status=Won
./bin/iris sdk:call leads.tasks.create 412 title="Setup delivery meeting"

# 🔍 Search leads with beautiful colored output
./bin/iris sdk:call leads.search search=john bloq_id=40 status=Won

# 📦 Add deliverable
./bin/iris sdk:call leads.deliverables.create 24 type=link title="Trained AI Agent" external_url="https://app.heyiris.io/agent/356" user_id=193

# 📊 Get priority insights
./bin/iris sdk:call leads.aggregation.statistics
./bin/iris sdk:call leads.aggregation.list has_incomplete_tasks=1 sort=priority
```

## Installation

```bash
composer require iris-ai/sdk
```

### Requirements
- PHP 8.1+
- Guzzle 7.0+

## CLI Tool

The SDK includes a lightweight CLI for quick access to all SDK features from the command line.

### Setup

```bash
# Set your credentials
export IRIS_API_KEY=sk_live_xxxxx
export IRIS_USER_ID=123

# Make the CLI executable (if needed)
chmod +x vendor/iris-ai/sdk/bin/iris

# Or use it directly
./vendor/bin/iris list
```

### Usage

The CLI uses a dynamic proxy pattern to access any SDK resource and method:

```bash
# Pattern: iris sdk:call <resource>.<method> [params] [options]

# 🔍 Lead Search & Management
./vendor/bin/iris sdk:call leads.search search=john bloq_id=40
./vendor/bin/iris sdk:call leads.update 412 status=Won
./vendor/bin/iris sdk:call leads.tasks.create 412 title="Setup meeting"
./vendor/bin/iris sdk:call leads.deliverables.list 24
./vendor/bin/iris sdk:call leads.deliverables.create 24 type=link title="AI Agent" external_url="https://app.heyiris.io/agent/356" user_id=193

# 📊 Lead Aggregation & Analytics
./vendor/bin/iris sdk:call leads.aggregation.statistics --json
./vendor/bin/iris sdk:call leads.aggregation.getRecentLeads 10
./vendor/bin/iris sdk:call leads.aggregation.list has_incomplete_tasks=1 sort=priority
./vendor/bin/iris sdk:call leads.aggregation.list status=Won,Negotiation per_page=20

# 🤖 AI Agents
./vendor/bin/iris sdk:call agents.chat agent_id=5 message="Hello"
./vendor/bin/iris sdk:call workflows.execute '{"agent_id":5,"query":"Research"}'

# 📚 Knowledge Base
./vendor/bin/iris sdk:call bloqs.list
./vendor/bin/iris sdk:call rag.query question="vacation policy" topK=5
```

### Output Formats

```bash
# JSON output (for scripting/automation)
iris sdk:call leads.list --json

# Raw output (no formatting)
iris sdk:call leads.get 123 --raw

# Colorful compact view (default) - Beautiful, readable format with emojis and colors
iris sdk:call leads.search search=john bloq_id=40
```

**Compact View Features:**
- 🎨 Color-coded fields (status, tasks, notes)
- 📊 Status badges with icons (✓ Won, ⚡ Negotiation, ✨ New, etc.)
- 🔗 Underlined URLs for easy clicking
- 📝 Smart field selection (only shows relevant data)
- Perfect for large datasets - no more unwieldy tables!

### Parameter Types

The CLI auto-detects parameter types:
- `true`/`false` → boolean
- `123` → integer  
- `12.5` → float
- `null` → null
- `{"key":"val"}` → JSON object
- `[1,2,3]` → JSON array
- `anything else` → string

### For Autonomous Agents

Perfect for programmatic access in autonomous development pipelines:

```bash
#!/bin/bash
# Platform AI Agent - Find high-priority work
LEADS=$(iris sdk:call leads.aggregation.list has_incomplete_tasks=1 --json)

# SDK AI Agent - Get requirements
REQS=$(iris sdk:call leads.aggregation.requirements 123 --json)

# QA Engineer Agent - Monitor stats
STATS=$(iris sdk:call leads.aggregation.statistics --json)

# Process results
echo $LEADS | jq '.[] | select(.priority_score > 50)'
```

### Extensibility

The CLI is a pure proxy - any new SDK resources or methods are automatically available without code changes.

## Quick Start

```php
<?php
use IRIS\SDK\IRIS;

// Initialize the SDK
$iris = new IRIS([
    'api_key' => 'sk_live_xxxxx',
    'user_id' => 193,  // Your user ID
]);

// Search for leads
$leads = $iris->leads->search([
    'search' => 'acme',
    'bloq_id' => 40,
    'status' => 'Won'
]);

// Update lead status
$lead = $iris->leads->update(412, ['status' => 'Won']);

// Add a task
$task = $iris->leads->tasks(412)->create([
    'title' => 'Setup delivery meeting',
    'due_date' => '2025-12-30'
]);

// Add deliverable
$deliverable = $iris->leads->deliverables(24)->create([
    'type' => 'link',
    'title' => 'Trained AI Agent',
    'external_url' => 'https://app.heyiris.io/agent/simple/356',
    'user_id' => 193
]);

// Chat with an agent
$response = $iris->agents->chat('agent_123', [
    ['role' => 'user', 'content' => 'Draft a marketing email']
]);

echo $response->content;
```

## Features

### 🤖 AI Agents

Create, configure, and interact with intelligent AI agents.

#### List Agents

```bash
# List all agents (requires client credentials)
./bin/iris sdk:call agents.list

# Search with pagination
./bin/iris sdk:call agents.list search="recruiter" per_page=10 page=1
```

```php
// List all agents
$agents = $iris->agents->list([
    'search' => 'marketing',
    'per_page' => 20,
]);

foreach ($agents as $agent) {
    echo "{$agent->name} (#{$agent->id})\n";
}
```

#### Create an Agent

```bash
# Create via CLI - using simplified config
./bin/iris sdk:call agents.create name="Marketing Assistant" prompt="You are a helpful marketing assistant" model="gpt-4o-mini"
```

```php
// Create an agent
$agent = $iris->agents->create(new AgentConfig(
    name: 'Marketing Assistant',
    prompt: 'You are a helpful marketing assistant specializing in email campaigns.',
    model: 'gpt-4o-mini',
    integrations: ['gmail', 'google-drive'],
));

echo "Created agent #{$agent->id}: {$agent->name}\n";
```

#### Update an Agent

**⚠️ IMPORTANT: Partial Updates**

The `patch()` method updates ONLY the fields you specify without overwriting other data:

```bash
# Update just the prompt (recommended)
./bin/iris sdk:call agents.patch 356 initial_prompt="Updated instructions..."

# Update just the name
./bin/iris sdk:call agents.patch 356 name="New Agent Name"

# Update multiple fields
./bin/iris sdk:call agents.patch 356 \
  initial_prompt="New prompt..." \
  description="Updated description"
```

```php
// RECOMMENDED: Partial update (only changes specified fields)
$agent = $iris->agents->patch(356, [
    'initial_prompt' => 'Focus on positive testimonials...',
]);

// Full update (overwrites ALL fields - use with caution)
$agent = $iris->agents->update(358, [
    'name' => 'Talent Recruiter Agent',
    'initial_prompt' => 'You are an AI recruitment assistant...',
    'config' => [
        'model' => 'gpt-4o-mini-2024-07-18',
        'temperature' => 0.7,
        'maxTokens' => 2048,
    ],
    'settings' => [
        'communicationStyle' => 'professional',
        'responseMode' => 'balanced',
        'functionCalling' => true,
    ],
]);
```

**Why use `patch()` instead of `update()`?**
- `patch()`: Updates only what you specify, keeps everything else
- `update()`: Replaces ALL fields, can accidentally clear data

**Real-world example:**
```bash
# Production setup
export IRIS_API_KEY="your_production_token_from_browser"
export IRIS_USER_ID=193

# Update just the prompt without touching other config
./bin/iris sdk:call agents.patch 356 \
  initial_prompt="Enhanced instructions..."
```

#### Chat with an Agent

```bash
# Single message chat
./bin/iris sdk:call agents.chat 358 message="Analyze this resume: John Doe - 5 years experience..."
```

```php
// Chat with the agent
$response = $iris->agents->chat($agent->id, [
    ['role' => 'user', 'content' => 'Write a subject line for our product launch']
]);

echo $response->content;
```

#### Delete an Agent

```bash
# Delete an agent
./bin/iris sdk:call agents.delete 358
```

```php
// Delete an agent
$iris->agents->delete(358);
```

#### Add Knowledge to Agent

```php
// Add knowledge to agent's memory
$iris->agents->addMemory($agent->id, '/path/to/brand-guide.pdf');
```

#### Attach Files to Agent (RAG Knowledge Base)

Upload and attach files to give your agent access to custom training data. These files become part of the agent's knowledge base for RAG (Retrieval-Augmented Generation).

```php
// Method 1: Upload and attach in one step (recommended)
$agent = $iris->agents->uploadAndAttachFiles(335, [
    '/path/to/training_data.csv',
    '/path/to/product_catalog.pdf',
    '/path/to/faq.txt',
], 40);  // 40 is the bloq_id

echo "Agent now has " . count($agent->fileAttachments) . " files\n";

// Method 2: Upload separately, then attach
$attachment = $iris->cloudFiles->uploadForAgent('/path/to/data.csv', 40, [
    'title' => 'Lead Data',
    'description' => 'Training data for lead analysis'
]);
$agent = $iris->agents->addFileAttachments(335, [$attachment]);

// Method 3: Upload multiple files separately
$attachments = $iris->cloudFiles->uploadMultipleForAgent([
    '/path/to/file1.pdf',
    '/path/to/file2.csv',
], 40);
$agent = $iris->agents->addFileAttachments(335, $attachments);
```

**Managing File Attachments:**

```php
// Get current attachments
$files = $iris->agents->getFileAttachments(335);
foreach ($files as $file) {
    echo "{$file['name']} ({$file['type']})\n";
}

// Remove a specific file
$agent = $iris->agents->removeFileAttachment(335, $cloudFileId);

// Replace all attachments
$agent = $iris->agents->setFileAttachments(335, $newAttachments);

// Clear all attachments
$agent = $iris->agents->clearFileAttachments(335);
```

**CLI Usage:**

```bash
# Upload a file for agent attachment
./bin/iris sdk:call cloudFiles.uploadForAgent /path/to/data.csv bloq_id=40

# List files attached to an agent
./bin/iris sdk:call agents.getFileAttachments 335

# Clear all attachments
./bin/iris sdk:call agents.clearFileAttachments 335
```

### 🔄 V5 Multi-Step Workflows

Execute complex workflows with real-time progress tracking and human-in-the-loop support.

```php
// Execute a workflow
$workflow = $iris->workflows->execute([
    'agent_id' => 'research_agent',
    'query' => 'Research competitors in the CRM space and create a comparison report',
]);

// Track progress in real-time
foreach ($workflow->steps() as $step) {
    echo "[{$step->progress}%] {$step->description}\n";

    if ($step->isCompleted()) {
        echo "  Result: " . $step->getResultString() . "\n";
    }
}

// Handle human-in-the-loop approval
if ($workflow->needsHumanInput()) {
    $task = $workflow->pendingTask;
    echo "Approval needed: {$task->description}\n";

    // Approve and continue
    $workflow->approve('Looks good, proceed with the report.');
}

// Get final result
$result = $workflow->result();
echo $result->content;

// Access generated files
foreach ($result->getFileUrls() as $url) {
    echo "File: {$url}\n";
}
```

### 📚 Document Management (Bloqs)

Organize content with projects, lists, and items.

```php
// Create a knowledge base
$kb = $iris->bloqs->create('Company Knowledge Base', [
    'description' => 'Internal documentation and policies',
]);

// Upload documents
$iris->bloqs->uploadFile($kb->id, '/path/to/handbook.pdf', [
    'title' => 'Employee Handbook',
    'tags' => ['hr', 'policy'],
]);

// Create organized lists
$list = $iris->bloqs->lists($kb->id)->create([
    'title' => 'Q1 Marketing Materials',
    'type' => 'folder',
]);

// Add items to lists
$iris->bloqs->items($list->id)->create([
    'title' => 'Campaign Brief',
    'content' => 'Campaign details...',
]);
```

### 🔍 RAG (Retrieval-Augmented Generation)

Query your knowledge base with semantic search.

```php
// Index content
$iris->rag->index(
    content: 'Our vacation policy allows for 20 days of PTO...',
    metadata: [
        'bloq_id' => $kb->id,
        'type' => 'policy',
        'title' => 'Vacation Policy',
    ]
);

// Query with semantic search
$results = $iris->rag->query(
    question: 'What is our vacation policy?',
    filters: ['bloq_id' => $kb->id],
    topK: 3
);

foreach ($results->documents as $doc) {
    echo "Score: {$doc->score}\n";
    echo "Content: {$doc->content}\n\n";
}
```

### 👤 Lead Management

Comprehensive CRM functionality for managing contacts, outreach, and sales pipelines.

#### Search & Filter Leads

```php
// Advanced search with filters
$results = $iris->leads->search([
    'search' => 'john',
    'bloq_id' => 40,
    'status' => 'Won',
    'per_page' => 50,
    'sort' => 'updated_at',
    'order' => 'desc',
    'include_notes' => true,
    'include_events' => true
]);

foreach ($results['data'] as $lead) {
    echo "{$lead['nickname']} - {$lead['status']} - {$lead['note_count']} notes\n";
}
```

**CLI Search:**
```bash
# Search by name with bloq filter
iris sdk:call leads.search search=john bloq_id=40

# Get Won deals with notes
iris sdk:call leads.search status=Won include_notes=true per_page=20

# Beautiful colored output:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  #24 │ Rodney Mayo │ ✓ Won
  🔑 id: 24
  👤 nickname: Rodney Mayo
  📊 status: ✓ Won
  🏷️ lead_type: unknown
  📝 note_count: 7
  ✅ tasks_count: 2
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

#### Update Lead Status

```php
// Update lead status
$lead = $iris->leads->update(412, [
    'status' => 'Won',
    'price_bid' => 5000
]);

echo "Updated {$lead->name} to {$lead->status}\n";
```

**CLI Update:**
```bash
# Change status to Won
iris sdk:call leads.update 412 status=Won

# Update multiple fields
iris sdk:call leads.update 412 status=Negotiation price_bid=5000
```

#### Manage Tasks

```php
// Create a task
$task = $iris->leads->tasks(412)->create([
    'title' => 'Setup delivery meeting',
    'description' => 'Schedule video call to walk through deliverables',
    'due_date' => '2025-12-30',
    'status' => 'pending'
]);

// List all tasks
$tasks = $iris->leads->tasks(412)->all();
foreach ($tasks as $task) {
    echo "- {$task->title} ({$task->status})\n";
}

// Update task status
$iris->leads->tasks(412)->update($task->id, [
    'status' => 'completed'
]);
```

**CLI Tasks:**
```bash
# Create a task
iris sdk:call leads.tasks.create 412 title="Setup delivery meeting"

# Add task with details
iris sdk:call leads.tasks.create 412 title="Send proposal" description="Draft and send pricing proposal" due_date="2025-12-30"

# List tasks
iris sdk:call leads.tasks.all 412

# Mark task complete
iris sdk:call leads.tasks.update 412 5 status=completed
```

#### Deliverables Management

```php
// List deliverables for a lead
$deliverables = $iris->leads->deliverables(24)->list();
foreach ($deliverables as $item) {
    echo "{$item['title']} - {$item['url']}\n";
}

// Create link deliverable
$deliverable = $iris->leads->deliverables(24)->create([
    'type' => 'link',
    'title' => 'Trained AI Agent',
    'external_url' => 'https://app.heyiris.io/agent/simple/356?bloq=203',
    'user_id' => 193
]);

// Upload file deliverable
$deliverable = $iris->leads->deliverables(24)->uploadFile(
    '/path/to/report.pdf',
    ['title' => 'Q4 Analytics Report']
);

// Update deliverable
$iris->leads->deliverables(24)->update($deliverable['id'], [
    'title' => 'Updated Report Title'
]);

// Preview email before sending (AI-generated)
$preview = $iris->leads->deliverables(16)->previewEmail([
    'deliverable_ids' => [203],
    'message_mode' => 'ai',
    'subject' => 'Your deliverables are ready',
    'include_project_context' => true,
]);
echo "Preview:\n{$preview['body']}\n";

// Send email with AI content
$result = $iris->leads->deliverables(16)->send([
    'deliverable_ids' => [203],
    'message_mode' => 'ai',
    'subject' => 'Your deliverables are ready',
    'recipient_emails' => ['mike@greenleaf.co'],
    'include_project_context' => true,
]);

// Or generate and send in one step
$result = $iris->leads->deliverables(16)->generateAndSend(
    [203, 204],
    ['subject' => 'Your project is complete!']
);
```

**CLI Deliverables:**
```bash
# List all deliverables
iris sdk:call leads.deliverables.list 24

# Add agent link
iris sdk:call leads.deliverables.create 24 type=link title="Trained AI Agent" external_url="https://app.heyiris.io/agent/simple/356?bloq=203" user_id=193

# Preview email
iris sdk:call leads.deliverables.previewEmail 16 deliverable_ids='[203]' message_mode=ai

# Send with AI content
iris sdk:call leads.deliverables.send 16 deliverable_ids='[203]' message_mode=ai recipient_emails='["mike@greenleaf.co"]'

# Delete deliverable
iris sdk:call leads.deliverables.delete 24 333
```

#### Invoice Management

Create and manage invoices for leads.

```php
// List invoices
$invoices = $iris->leads->invoices(16)->list();

// Create invoice from lead pricing
$invoice = $iris->leads->invoices(16)->create([
    'price' => 25000,  // Amount in cents ($250.00)
    'description' => 'AI Agent Development - Phase 1',
]);

echo "Invoice #{$invoice['id']} created\n";
echo "Payment link: {$invoice['payment_url']}\n";

// Send invoice to lead
$result = $iris->leads->invoices(16)->send($invoice['id'], [
    'subject' => 'Invoice for AI Agent Development',
    'message' => 'Please find your invoice attached.',
]);

// Mark as paid
$iris->leads->invoices(16)->markPaid($invoice['id'], [
    'payment_method' => 'stripe',
    'transaction_id' => 'ch_xxxxx',
]);

// Void/cancel an invoice
$iris->leads->invoices(16)->void($invoice['id'], 'Client cancelled project');
```

**CLI Invoices:**
```bash
# List invoices for a lead
iris sdk:call leads.invoices.list 16

# Create invoice
iris sdk:call leads.invoices.create 16 price=25000 description="AI Agent Development"

# Send invoice
iris sdk:call leads.invoices.send 16 123 subject="Your invoice"

# Mark as paid
iris sdk:call leads.invoices.markPaid 16 123
```

#### Lead Aggregation & Priority Analysis

```php
// Get comprehensive statistics
$stats = $iris->leads->aggregation()->statistics();
echo "Total leads: {$stats['total_leads']}\n";
echo "Incomplete tasks: {$stats['total_incomplete_tasks']}\n";

// Get priority leads with tasks
$leads = $iris->leads->aggregation()->list([
    'has_incomplete_tasks' => true,
    'sort' => 'priority',
    'order' => 'desc',
    'per_page' => 10
]);

// Get recently updated leads
$recent = $iris->leads->aggregation()->getRecentLeads(10);

// Get specific lead with context
$lead = $iris->leads->aggregation()->get(24);
echo "Priority score: {$lead['priority_score']}\n";
echo "Tasks: {$lead['incomplete_tasks_count']}/{$lead['total_tasks_count']}\n";
```

**CLI Aggregation:**
```bash
# Statistics dashboard
iris sdk:call leads.aggregation.statistics

# High priority leads with tasks
iris sdk:call leads.aggregation.list has_incomplete_tasks=1 sort=priority order=desc

# Recently updated leads
iris sdk:call leads.aggregation.getRecentLeads 10 sort=updated_at

# Filter by status (comma-separated)
iris sdk:call leads.aggregation.list status=Won,Negotiation per_page=20
```

#### Basic Lead Operations

```php
// Create a lead
$lead = $iris->leads->create([
    'name' => 'John Smith',
    'email' => 'john@acme.com',
    'company' => 'Acme Corp',
    'tags' => ['enterprise', 'hot'],
]);

// Generate AI-powered outreach
$email = $iris->leads->generateResponse($lead->id,
    'Write a personalized introduction email based on their company profile'
);

// Track activity
$iris->leads->activities($lead->id)->create([
    'type' => 'email_sent',
    'content' => $email,
    'metadata' => ['campaign' => 'Q1_outreach'],
]);
```

#### Lead Enrichment

Automatically enrich leads with additional data from external sources.

```php
// Enrich a lead (async process)
$result = $iris->leads->enrich(24, ['auto_update' => false]);
echo "Enrichment started: {$result['status']}\n";

// Check enrichment status
$status = $iris->leads->enrichmentStatus(24);
echo "Status: {$status['status']}\n";  // 'pending', 'processing', 'completed', 'failed'

if ($status['status'] === 'completed') {
    echo "Found data: " . json_encode($status['data']) . "\n";
}
```

**CLI Enrichment:**
```bash
# Start enrichment
iris sdk:call leads.enrich 24 auto_update=false

# Check status
iris sdk:call leads.enrichmentStatus 24
```

#### AI-Powered Lead Creation

Create leads from natural language descriptions using AI parsing.

```php
// Parse a freeform lead description
$parsed = $iris->leads->parseDescription(
    'David Park, freelance consultant, david.park.consulting@gmail.com, tech innovation',
    40  // bloq_id
);

echo "Parsed name: {$parsed['lead']['first_name']} {$parsed['lead']['last_name']}\n";
echo "Email: {$parsed['lead']['email']}\n";
echo "Tags: " . implode(', ', $parsed['lead']['tags']) . "\n";

// Create lead from description in one step (recommended)
$lead = $iris->leads->createFromDescription(
    'Sarah Chen, startup founder, sarah@techventure.io, AI enthusiast in San Francisco',
    40,  // bloq_id
    ['lifecycle_stage' => 'New']
);

echo "Created lead #{$lead->id}: {$lead->name}\n";

// Bulk create from multiple descriptions
$results = $iris->leads->bulkCreateFromDescriptions([
    'John Doe, developer, john@example.com',
    'Jane Smith, designer, jane@design.co, creative',
    'Bob Wilson, CTO at TechCorp, bob@techcorp.io',
], 40);

echo "Created {$results['successful']} leads\n";
```

**Helper Methods:**

```php
// Get available tags for a bloq
$tags = $iris->leads->getAvailableTags(40);
foreach ($tags as $tag) {
    echo "- {$tag['name']}\n";
}

// Get lifecycle stages
$stages = $iris->leads->getLifecycleStages();
// Returns: ['New', 'Qualified', 'Proposal', 'Negotiation', 'Won', 'Lost']

// Check for duplicate before creating
$duplicate = $iris->leads->checkDuplicate('david@example.com', 40);
if ($duplicate['exists']) {
    echo "Lead already exists: #{$duplicate['lead_id']}\n";
} else {
    // Safe to create
    $lead = $iris->leads->createFromDescription($description, 40);
}
```

**CLI Usage:**

```bash
# Parse a lead description (preview without creating)
./bin/iris sdk:call leads.parseDescription description="John Smith, CEO at Acme, john@acme.com" bloq_id=40

# Create lead from description
./bin/iris sdk:call leads.createFromDescription description="Sarah Chen, sarah@example.com, AI consultant" bloq_id=40

# Get available tags for a bloq
./bin/iris sdk:call leads.getAvailableTags 40

# Get lifecycle stages
./bin/iris sdk:call leads.getLifecycleStages

# Check for duplicate
./bin/iris sdk:call leads.checkDuplicate email="john@acme.com" bloq_id=40
```

### 🔌 Integrations

Access 16+ third-party integrations.

```php
// List available integrations
$integrations = $iris->integrations->available();

// Execute integration function
$files = $iris->integrations->execute(
    type: 'google-drive',
    function: 'search_files',
    params: ['query' => 'Q1 Report', 'limit' => 10]
);

// Get OAuth URL for connecting
$oauthUrl = $iris->integrations->getOAuthUrl('google-drive');
```

**Supported Integrations:**
- Google Drive, Gmail, Calendar
- Slack, Discord
- Mailjet, Mailchimp
- YouTube, ElevenLabs
- Servis.ai (15+ functions)
- And more...

### 📞 Voice AI (VAPI)

Enable AI-powered phone calls with your agents using VAPI integration.

#### List & Configure Phone Numbers

```php
// List all phone numbers
$numbers = $iris->vapi->phoneNumbers();
foreach ($numbers as $number) {
    echo "{$number['phone_number']} - Agent: {$number['agent_id']}\n";
}

// Configure a phone number to use an agent
$iris->vapi->configurePhoneNumber('dd3905f2-08d6-4dc2-a50f-f0c937ada251', [
    'agent_id' => 335,
    'use_dynamic_assistant' => true,
    'allow_override' => true,
]);

// Disconnect phone from agent
$iris->vapi->disconnectPhoneNumber('dd3905f2-...');
```

#### Sync Agent with VAPI

```php
// Sync agent settings to VAPI assistant
$result = $iris->vapi->syncAssistant(335);
echo "VAPI Assistant ID: {$result['assistant_id']}\n";

// Update voice settings
$iris->vapi->updateVoice(335, [
    'voice' => 'Lily',
    'language' => 'en-US',
    'speed' => 1.0,
]);

// Get available voices
$voices = $iris->vapi->voices();
```

#### Call Handoff (Transfer to Human)

```php
// Configure handoff settings
$iris->vapi->updateHandoff(335, [
    'enabled' => true,
    'phone_number' => '8788765657',
    'mode' => 'blind',  // 'blind' or 'warm' transfer
    'message' => 'Transferring you to a human agent...',
    'sms_notifications' => true,
]);

// Get current handoff settings
$handoff = $iris->vapi->getHandoff(335);
```

#### Call Management

```php
// Initiate an outbound call
$call = $iris->vapi->initiateCall(335, '+15551234567', [
    'context' => [
        'lead_id' => 412,
        'purpose' => 'Follow-up on proposal',
    ],
]);

// Get call history
$calls = $iris->vapi->callHistory(['limit' => 50, 'agent_id' => 335]);

// Get call details and transcript
$details = $iris->vapi->getCall($callId);
$transcript = $iris->vapi->getTranscript($callId);
$recordingUrl = $iris->vapi->getRecording($callId);

// End an active call
$iris->vapi->endCall($callId);

// Get VAPI usage statistics
$usage = $iris->vapi->usage();
```

**CLI Usage:**

```bash
# List phone numbers
./bin/iris sdk:call vapi.phoneNumbers

# Configure phone for agent
./bin/iris sdk:call vapi.configurePhoneNumber dd3905f2-... agent_id=335 use_dynamic_assistant=true

# Sync agent with VAPI
./bin/iris sdk:call vapi.syncAssistant 335

# Update handoff settings
./bin/iris sdk:call vapi.updateHandoff 335 handoff='{"enabled":true,"phone_number":"8788765657","mode":"blind"}'

# Get call history
./bin/iris sdk:call vapi.callHistory agent_id=335 limit=20
```

### 🤖 AI Models

List and manage available AI models.

```php
// Get basic/fast models (nano, mini)
$basic = $iris->models->basic();

// Get popular models
$popular = $iris->models->popular();

// Get nano models (fastest, cheapest)
$nano = $iris->models->nano();

// Get models by provider
$openai = $iris->models->byProvider('openai');
$anthropic = $iris->models->byProvider('anthropic');

// Get specific model details
$model = $iris->models->get('gpt-4o-mini-2024-07-18');
echo "Model: {$model['name']}\n";
echo "Provider: {$model['provider']}\n";

// Get recommended model for use case
$recommended = $iris->models->recommended('coding');

// Get pricing info
$pricing = $iris->models->pricing();
```

**CLI Usage:**

```bash
# List basic models
./bin/iris sdk:call models.basic

# Get popular models
./bin/iris sdk:call models.popular

# Get nano models
./bin/iris sdk:call models.nano

# Get model by provider
./bin/iris sdk:call models.byProvider openai
```

### 💳 Credit & Billing Status

```php
// Get credit status
$credits = $iris->usage->creditStatus();
echo "Credits remaining: {$credits['credits_remaining']}\n";
echo "Credits used: {$credits['credits_used']}\n";

if ($credits['credits_remaining'] < 100) {
    echo "Warning: Low credits!\n";
}

// Get credit history
$history = $iris->usage->creditHistory(['limit' => 50]);

// Get subscription details
$subscription = $iris->usage->subscription();

// Get available upgrade plans
$plans = $iris->usage->availablePlans();
```

**CLI Usage:**

```bash
# Check credit status
./bin/iris sdk:call usage.creditStatus

# Get subscription info
./bin/iris sdk:call usage.subscription

# Get available plans
./bin/iris sdk:call usage.availablePlans
```

## Error Handling

```php
use IRIS\SDK\Exceptions\{
    IRISException,
    AuthenticationException,
    RateLimitException,
    ValidationException,
    WorkflowException
};

try {
    $response = $iris->agents->chat('agent_123', $messages);
} catch (AuthenticationException $e) {
    // Invalid API key
    echo "Auth failed: " . $e->getMessage();
} catch (RateLimitException $e) {
    // Rate limited - wait and retry
    echo "Rate limited. Retry after {$e->retryAfter} seconds";
    sleep($e->retryAfter);
    // Retry...
} catch (ValidationException $e) {
    // Validation errors
    foreach ($e->validationErrors as $field => $errors) {
        echo "{$field}: " . implode(', ', $errors) . "\n";
    }
} catch (WorkflowException $e) {
    // Workflow execution failed
    echo "Step '{$e->stepName}' failed: " . $e->getMessage();
} catch (IRISException $e) {
    // Generic error
    echo "Error: " . $e->getMessage();
}
```

## Laravel Integration

The SDK includes a Laravel service provider for seamless integration.

### Configuration

```php
// config/iris.php
return [
    'api_key' => env('IRIS_API_KEY'),
    'base_url' => env('IRIS_API_URL', 'https://api.iris.ai'),
];
```

### Usage

```php
use IRIS\SDK\Laravel\Facades\IRIS;

// Using the facade
$response = IRIS::agents()->chat($agentId, $messages);

// Or with dependency injection
use IRIS\SDK\IRIS;

class ChatController
{
    public function chat(IRIS $iris, Request $request)
    {
        return $iris->agents->chat(
            $request->agent_id,
            $request->messages
        );
    }
}
```

## Webhook Handling

Receive real-time workflow events via webhooks.

```php
// In your webhook controller
$handler = $iris->webhooks();

$handler->onStepCompleted(function ($event) {
    Log::info('Step completed', [
        'workflow_id' => $event->workflowId,
        'step' => $event->stepNumber,
        'progress' => $event->progress,
    ]);
});

$handler->onHumanInputRequired(function ($event) {
    // Notify user
    Notification::send($user, new ApprovalRequired($event->task));
});

$handler->onWorkflowCompleted(function ($event) {
    // Process result
    ProcessResult::dispatch($event->result);
});

// Handle incoming webhook
$handler->handle(request());
```

## Configuration Options

```php
$iris = new IRIS([
    'api_key' => 'sk_live_xxxxx',      // Required: API key
    'base_url' => 'https://api.iris.ai', // Optional: API base URL
    'iris_url' => 'https://iris.iris.ai', // Optional: IRIS workflows URL
    'user_id' => 123,                   // Optional: Default user context
    'timeout' => 30,                    // Optional: Request timeout (seconds)
    'retries' => 3,                     // Optional: Max retry attempts
    'webhook_secret' => 'whsec_xxx',   // Optional: Webhook verification secret
    'debug' => false,                   // Optional: Enable debug logging
    'polling_interval' => 500,          // Optional: Workflow polling interval (ms)
    'max_polling_duration' => 300,      // Optional: Max polling time (seconds)
]);

// Switch user context
$iris->asUser(456);
```

## Testing

The SDK includes comprehensive test suites and example scripts.

### Quick Start - Lead Aggregation Test

Test the Lead Aggregation API with automatic environment configuration:

```bash
# 1. Copy environment template
cp .env.example .env

# 2. Add your API key to .env
# IRIS_API_KEY=your_api_key_here

# 3. Run the test
php test-lead-aggregation-user-193.php
```

**Output:**
```
🔧 Configuration:
   Environment: local
   Base URL: https://local.raichu.freelabel.net
   User ID: 193

📊 Lead Statistics:
  ✓ Total Leads: 125
  ✓ Total Tasks: 487
  ✓ Incomplete Tasks: 234
  ✓ Active Leads: 89

  🔥 Top Priority Leads:
     [95] Acme Corp (active)
     [87] Tech Startup (qualified)
✅ Test completed successfully!
```

**Environment Configuration:**

For **local development** (default):
```env
IRIS_ENV=local
IRIS_LOCAL_URL=https://local.raichu.freelabel.net
```

For **production testing**:
```env
IRIS_ENV=production
IRIS_PRODUCTION_URL=https://apiv2.heyiris.io
```

📖 **[Full Testing Documentation →](TEST_README.md)**

### Unit Tests

```php
use IRIS\SDK\Http\MockClient;

// Create mock client for testing
$mockHttp = new MockClient();
$mockHttp->addResponse('POST', '/v1/bloqs/agents/generate-response', [
    'content' => 'Mocked response',
    'tokens_used' => 100,
]);

$iris = new IRIS([
    'api_key' => 'test_key',
    'http_client' => $mockHttp,
]);

// Your tests
$response = $iris->agents->chat('agent_123', $messages);
assert($response->content === 'Mocked response');
```

## API Reference

| Resource | Methods |
|----------|---------|
| `$iris->agents` | `list`, `get`, `create`, `update`, `patch`, `delete`, `chat`, `multiStep`, `addMemory`, `togglePublic`, `generateWebhook`, `getFileAttachments`, `addFileAttachments`, `setFileAttachments`, `removeFileAttachment`, `clearFileAttachments`, `uploadAndAttachFiles` |
| `$iris->workflows` | `execute`, `getStatus`, `continue`, `completeTask`, `generate`, `generateWithAgents`, `templates`, `importTemplate`, `runs`, `getLogs` |
| `$iris->bloqs` | `list`, `get`, `create`, `update`, `delete`, `overview`, `agents`, `bloqAgents`, `workflows`, `lists`, `items`, `uploadFile`, `files`, `getCustomFieldsConfig`, `updateCustomFieldsConfig`, `addCustomField`, `removeCustomField`, `clearCustomFields` |
| `$iris->leads` | `list`, `get`, `create`, `update`, `delete`, `search`, `addNote`, `activities`, `tasks`, `deliverables`, `invoices`, `aggregation`, `outreach`, `outreachSteps`, `enrich`, `enrichmentStatus`, `generateResponse`, `recordOutreach`, `parseDescription`, `createFromDescription`, `getAvailableTags`, `getLifecycleStages`, `checkDuplicate`, `bulkCreateFromDescriptions` |
| `$iris->leads->tasks()` | `all`, `create`, `update`, `delete`, `reorder` |
| `$iris->leads->deliverables()` | `list`, `create`, `uploadFile`, `update`, `delete`, `previewEmail`, `send`, `generateAndSend` |
| `$iris->leads->invoices()` | `list`, `get`, `create`, `update`, `delete`, `markPaid`, `send`, `getPaymentLink`, `void` |
| `$iris->leads->aggregation()` | `statistics`, `list`, `get`, `getRecentLeads`, `requirements` |
| `$iris->leads->outreach()` | `checkEligibility`, `getInfo`, `recordAttempt`, `setAutoRespond`, `generateEmail`, `sendEmail`, `generateAndSend` |
| `$iris->leads->outreachSteps()` | `list`, `all`, `create`, `update`, `complete`, `reopen`, `delete`, `reorder`, `initializeDefault`, `clearAll` |
| `$iris->cloudFiles` | `list`, `get`, `upload`, `update`, `delete`, `downloadUrl`, `status`, `content`, `supportedTypes`, `forBloq`, `forAgent`, `attachToAgent`, `detachFromAgent`, `reindex`, `uploadForAgent`, `uploadMultipleForAgent` |
| `$iris->usage` | `summary`, `details`, `byAgent`, `byModel`, `billing`, `package`, `quota`, `history`, `workflowStats`, `storage`, `creditStatus`, `creditHistory`, `subscription`, `availablePlans` |
| `$iris->vapi` | `phoneNumbers`, `getPhoneNumber`, `configurePhoneNumber`, `disconnectPhoneNumber`, `syncAssistant`, `updateHandoff`, `getHandoff`, `getAssistant`, `updateVoice`, `voices`, `callHistory`, `getCall`, `getTranscript`, `getRecording`, `initiateCall`, `endCall`, `usage` |
| `$iris->models` | `list`, `basic`, `popular`, `get`, `byProvider`, `recommended`, `providers`, `sync`, `pricing`, `nano` |
| `$iris->integrations` | `available`, `connected`, `getOAuthUrl`, `test`, `execute`, `functions` |
| `$iris->rag` | `query`, `index`, `indexFile`, `searchSimilar`, `delete` |

## License

MIT License - see [LICENSE](LICENSE) for details.

## Support

- Documentation: https://docs.iris.ai/sdk/php
- Issues: https://github.com/iris-ai/php-sdk/issues
- Email: support@iris.ai
