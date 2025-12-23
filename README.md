# IRIS PHP SDK

**Build production-ready AI agents in minutes, not months.**

The official PHP SDK for the [IRIS AI Platform](https://app.heyiris.io) - the fastest way to create, deploy, and scale intelligent AI agents with persistent memory, multi-step workflows, and 17+ integrations.

[![PHP 8.1+](https://img.shields.io/badge/PHP-8.1+-blue.svg)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

## Why IRIS?

| Challenge | Traditional Approach | With IRIS SDK |
|-----------|---------------------|---------------|
| **Building AI agents** | Weeks of prompt engineering | 5 lines of code |
| **Persistent memory** | Build your own RAG system | Automatic vectorization |
| **Integrations** | Write custom code for each | 17+ built-in services |
| **Deployment** | Infrastructure setup | Cloud-hosted at app.heyiris.io |
| **Scaling** | Complex architecture | One API call |

---

## See It In Action

### 30-Second Demo: Chat With Any Agent

```bash
./bin/iris chat 11 "Hello, what can you do?"
```

```
╭─────────────────────────────────────────────────────────────╮
│ 🤖 Agent #11                                                 │
╰─────────────────────────────────────────────────────────────╯

📤 Sending: "Hello, what can you do?"

⠙ ⏳ Running (2.3s)

✅ Complete!

╭─────────────────────────────────────────────────────────────╮
│ Hello! I'm your AI assistant. I can help you with:          │
│ • Managing leads and CRM tasks                              │
│ • Generating content and analyzing data                     │
│ • Connecting to Google Drive, Gmail, Slack & more           │
│ • Automating workflows                                      │
╰─────────────────────────────────────────────────────────────╯

📊 Tokens: 245 | Time: 3.2s | Model: gpt-4o-mini
```

### Create Your First Agent (5 Lines)

```php
$iris = new IRIS(['api_key' => 'your-key', 'user_id' => 193]);

$agent = $iris->agents->create(new AgentConfig(
    name: 'Sales Assistant',
    prompt: 'You are a helpful sales assistant.',
));

echo $agent->getSimpleUrl();  // https://app.heyiris.io/agent/simple/123
```

**Your agent is now live and shareable!** → [Learn more about agents](#-ai-agents)

---

## Quick Start

### Installation

```bash
composer require iris-ai/sdk
```

### Setup (One-Time)

```bash
./bin/iris config setup
```

### That's It! Start Building

```bash
# Chat with an agent
./bin/iris chat 11 "Analyze my sales data"

# Search your leads
./bin/iris sdk:call leads.search search=acme status=Won

# Create an agent
./bin/iris sdk:call agents.create name="Support Bot" prompt="Help customers..."
```

---

## Core Features

### 🤖 AI Agents
Create intelligent assistants that live in the cloud at **app.heyiris.io**.

```php
// Create an agent
$agent = $iris->agents->create(new AgentConfig(
    name: 'Marketing Assistant',
    prompt: 'You specialize in email marketing campaigns.',
    model: 'gpt-4o-mini',
    integrations: ['gmail', 'google-drive'],
));

// Get shareable URL
$url = $agent->getSimpleUrl();
// → https://app.heyiris.io/agent/simple/42?bloq=40

// Chat with your agent
$response = $iris->agents->chat($agent->id, [
    ['role' => 'user', 'content' => 'Draft a product launch email']
]);
```

📖 [Full Agent Documentation](TECHNICAL.md#-ai-agents)

---

### 📚 Persistent Memory (Knowledge Base)
Your agents remember everything with automatic RAG (Retrieval-Augmented Generation).

```
┌─────────────────────────────────────────────────────────────┐
│                    Your Knowledge Base                       │
├─────────────────────────────────────────────────────────────┤
│  📁 Lists (Categories)    │  🤖 Agents (AI Assistants)      │
│  ├── 📋 Sales Docs        │  ├── Sales Assistant            │
│  ├── 📋 Product Info      │  └── Support Bot                │
│  └── 📋 Training Data     │                                 │
├─────────────────────────────────────────────────────────────┤
│  📄 Items (Documents)     →  🔢 Auto-Vectorized (OpenAI)    │
│  Uploaded files are automatically indexed for semantic       │
│  search and intelligent retrieval by your agents.            │
└─────────────────────────────────────────────────────────────┘
```

```php
// Create a knowledge base
$kb = $iris->bloqs->create('Sales Knowledge Base');

// Upload training documents (auto-vectorized!)
$iris->agents->uploadAndAttachFiles($agent->id, [
    '/path/to/product-guide.pdf',
    '/path/to/sales-playbook.docx',
], $kb->id);

// Now your agent knows your products!
$response = $iris->agents->chat($agent->id, [
    ['role' => 'user', 'content' => 'What are our pricing tiers?']
]);
// Agent answers using your uploaded documents
```

📖 [Full Memory & RAG Documentation](TECHNICAL.md#-persistent-memory--knowledge-base-bloqs)

---

### 🔗 17+ Built-in Integrations
Connect your agents to the tools your team already uses.

| Category | Integrations |
|----------|--------------|
| **Google Suite** | Drive, Gmail, Calendar |
| **Communication** | Slack, Discord |
| **Email Marketing** | Mailjet, Mailchimp |
| **AI Providers** | OpenAI, Anthropic, DeepSeek, OpenRouter |
| **Business Tools** | Stripe, Buffer, Reddit, YouTube |
| **Custom** | Webhooks, MCP Protocol |

```php
// Enable integrations on an agent
$agent = $iris->agents->patch($agentId, [
    'integrations' => ['gmail', 'google-drive', 'slack'],
]);

// Agent can now search emails, read files, send Slack messages
$response = $iris->agents->chat($agent->id, [
    ['role' => 'user', 'content' => 'Find the Q4 sales report in my Drive']
]);
```

📖 [Full Integrations Documentation](TECHNICAL.md#-integrations-17-services)

---

### 📊 Lead Management CRM
Complete CRM functionality built-in - manage your sales pipeline directly from the SDK.

```php
// Search leads
$leads = $iris->leads->search([
    'search' => 'acme',
    'status' => 'Won',
    'bloq_id' => 40,
]);

// Update and track
$iris->leads->update(412, ['status' => 'Won']);
$iris->leads->tasks(412)->create(['title' => 'Send contract']);
$iris->leads->deliverables(412)->create([
    'type' => 'link',
    'title' => 'Trained AI Agent',
    'external_url' => $agent->getSimpleUrl(),
]);

// Analytics
$stats = $iris->leads->aggregation->statistics();
```

📖 [Full Lead Management Documentation](TECHNICAL.md#-lead-management)

---

### 🔄 Multi-Step Workflows
Execute complex workflows with real-time progress tracking and human-in-the-loop approval.

```php
// Execute a multi-step workflow
$workflow = $iris->workflows->execute([
    'agent_id' => 'research_agent',
    'query' => 'Research competitors and create a comparison report',
]);

// Track progress in real-time
foreach ($workflow->steps() as $step) {
    echo "[{$step->progress}%] {$step->description}\n";
}

// Handle approval points
if ($workflow->needsHumanInput()) {
    $workflow->approve('Proceed with the analysis.');
}

// Get results
echo $workflow->result()->content;
```

📖 [Full Workflow Documentation](TECHNICAL.md#-v5-multi-step-workflows)

---

## CLI: Power at Your Fingertips

The SDK includes a powerful CLI that mirrors all SDK functionality:

```bash
# AI Chat (with beautiful real-time progress!)
./bin/iris chat 11 "Analyze my leads"
./bin/iris chat 337 "Draft marketing email" --bloq=40

# Lead Management
./bin/iris sdk:call leads.search search=acme status=Won
./bin/iris sdk:call leads.update 412 status=Won
./bin/iris sdk:call leads.tasks.create 412 title="Follow up"

# Agent Operations
./bin/iris sdk:call agents.list
./bin/iris sdk:call agents.create name="Support Bot" prompt="..."
./bin/iris sdk:call agents.getUrl 11  # Get shareable URL

# Knowledge Base
./bin/iris sdk:call bloqs.list
./bin/iris sdk:call bloqs.uploadFile 40 /path/to/document.pdf
```

📖 [Full CLI Documentation](TECHNICAL.md#cli-tool)

---

## N8N & Workflow Tool Compatibility

**Already using N8N?** IRIS works seamlessly alongside your existing workflows:

- **Trigger IRIS agents** from N8N HTTP nodes
- **Receive webhooks** from IRIS workflows
- **Access all SDK features** via REST API
- **Keep your automation** - add AI capabilities

Your agents live at **app.heyiris.io** and can be accessed from any workflow tool.

📖 [N8N Integration Guide](TECHNICAL.md#n8n-workflow-compatibility)

---

## Complete Documentation

| Section | Description |
|---------|-------------|
| [Technical Reference](TECHNICAL.md) | Full API documentation with all methods |
| [CLI Guide](TECHNICAL.md#cli-tool) | Complete CLI usage and commands |
| [AI Agents](TECHNICAL.md#-ai-agents) | Create, configure, and deploy agents |
| [Knowledge Base](TECHNICAL.md#-persistent-memory--knowledge-base-bloqs) | Persistent memory and RAG |
| [Lead Management](TECHNICAL.md#-lead-management) | CRM functionality |
| [Integrations](TECHNICAL.md#-integrations-17-services) | 17+ service connections |
| [Workflows](TECHNICAL.md#-v5-multi-step-workflows) | Multi-step automation |
| [Testing](TECHNICAL.md#testing) | Mocking and test utilities |

---

## API Reference (Quick)

| Resource | Key Methods |
|----------|-------------|
| `$iris->agents` | `create`, `chat`, `get`, `patch`, `getUrl`, `uploadAndAttachFiles` |
| `$iris->bloqs` | `create`, `uploadFile`, `share`, `lists`, `items` |
| `$iris->leads` | `search`, `create`, `update`, `tasks`, `deliverables`, `aggregation` |
| `$iris->workflows` | `execute`, `getStatus`, `approve`, `templates` |
| `$iris->chat` | `start`, `execute`, `resume`, `history` |
| `$iris->integrations` | `list`, `get`, `enable`, `execute` |

📖 [Full API Reference](TECHNICAL.md#api-reference)

---

## Get Started Today

```bash
# 1. Install
composer require iris-ai/sdk

# 2. Configure
./bin/iris config setup

# 3. Build!
./bin/iris chat 11 "Hello, I'm ready to build AI agents!"
```

**Questions?** Open an issue or contact us at [support@heyiris.io](mailto:support@heyiris.io)

---

## License

MIT License - see [LICENSE](LICENSE) for details.
