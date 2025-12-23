# IRIS AI Platform

**Create AI assistants that actually know your business.**

---

## What is IRIS?

IRIS lets you build **AI agents** - intelligent assistants that can answer questions, perform tasks, and connect to your tools. Think of it like creating your own custom ChatGPT that knows your products, your processes, and your data.

**No coding required.** Everything can be done through our visual interface at [app.heyiris.io](https://app.heyiris.io). This SDK and CLI are optional power tools for developers who want programmatic control.

---

## Who is This For?

- **Business owners** who want AI assistants for customer support, sales, or internal operations
- **Agencies** building AI solutions for clients
- **Developers** integrating AI capabilities into applications
- **Teams** that need intelligent automation without hiring ML engineers

---

## Platform Features Explained

### 🤖 AI Agents — Your Custom AI Assistants

**What it is:** An AI agent is like having a smart employee that's available 24/7. You tell it what you want it to do, give it knowledge about your business, and it handles conversations and tasks for you.

**Real examples:**
- A **customer support agent** that answers product questions using your documentation
- A **sales assistant** that qualifies leads and schedules meetings
- A **recruiting agent** that screens resumes and answers candidate questions
- An **internal helper** that answers HR policy questions for employees

**How it works:**
1. Give your agent a name and personality ("friendly and professional")
2. Write instructions for what it should do ("Help customers with product questions")
3. Upload files so it knows your business (product guides, FAQs, policies)
4. Share the link - your agent is live!

**Why this matters:** You don't need to train an AI model or write complex code. Just describe what you want in plain English, upload your files, and your agent is ready to use.

---

### 📚 Knowledge Base — Your Agent's Memory

**What it is:** The knowledge base is where your agent stores everything it needs to know. When someone asks a question, your agent searches this memory to find relevant information and give accurate answers.

**Real examples:**
- Upload your **product catalog** → Agent can answer "What's the price of X?" or "Do you have Y in stock?"
- Upload your **employee handbook** → Agent answers "How many vacation days do I have?" or "What's the expense policy?"
- Upload **training materials** → Agent helps onboard new team members
- Upload **sales playbooks** → Agent coaches reps on handling objections

**How it works:**
1. Create a knowledge base (just give it a name)
2. Upload files - PDFs, Word docs, spreadsheets, text files
3. IRIS automatically reads and indexes everything
4. Your agent can now search and reference this information

**Why this matters:** Traditional chatbots give generic answers. Your IRIS agent gives answers based on YOUR actual documents and data. When your information changes, just upload the new files - no retraining required.

---

### 🔗 Integrations — Connect Your Tools

**What it is:** Integrations let your agent connect to the software you already use. Instead of just chatting, your agent can actually DO things - search your Google Drive, read your emails, send Slack messages, or update your CRM.

**Available integrations:**
| Category | Services |
|----------|----------|
| **Google Workspace** | Drive (search files), Gmail (read/send), Calendar (check/create events) |
| **Communication** | Slack (send messages), Discord (post updates) |
| **Email Marketing** | Mailjet, Mailchimp (manage campaigns) |
| **Business Tools** | Stripe (payments), Buffer (social media) |
| **AI Models** | OpenAI, Anthropic Claude, Google Gemini |

**Real examples:**
- "Find the Q3 sales report in my Google Drive" → Agent searches and retrieves it
- "Send a Slack message to #marketing about the campaign launch" → Agent sends it
- "What meetings do I have tomorrow?" → Agent checks your Google Calendar
- "Draft a follow-up email to John" → Agent writes it and optionally sends via Gmail

**Why this matters:** Your agent becomes a true assistant that takes action, not just a chatbot that gives advice. One agent can work across all your tools.

---

### 📊 Lead Management — Complete CRM System

**What it is:** A full-featured CRM built right into IRIS. Track contacts, manage deals, assign tasks, send invoices, and automate follow-ups. Your AI agents can access and update this data, creating a seamless workflow between human and AI.

**Lead Tracking:**
- **Contact information** - Name, email, phone, company, and any custom fields you need
- **Pipeline stages** - New → Contacted → Negotiation → Won/Lost (customizable)
- **Lead scoring** - Automatically prioritize based on engagement and fit
- **Source tracking** - Know where each lead came from

**Tasks & Follow-ups:**
- **Task management** - Create to-dos with due dates for each lead
- **Automated reminders** - Never miss a follow-up
- **Task templates** - Standard checklists for your sales process
- **Assignment** - Delegate tasks to team members or AI agents

**Notes & Activity History:**
- **Conversation notes** - Log every interaction
- **Activity timeline** - See the complete history of each lead
- **AI-generated summaries** - Your agent can summarize long email threads
- **Searchable history** - Find any conversation instantly

**Invoicing & Payments:**
- **Create invoices** - Generate professional invoices for leads
- **Stripe integration** - Accept payments directly
- **Invoice tracking** - See paid, pending, and overdue invoices
- **Itemized billing** - Add line items, quantities, and descriptions

**Deliverables:**
- **Track what you've delivered** - Files, links, access credentials
- **Proof of delivery** - Document what was provided and when
- **Client portal** - Leads can view their deliverables
- **Version history** - Track updates to deliverables

**Automation Capabilities:**
- **Auto-assign leads** - Route new leads to the right person or agent
- **Trigger workflows** - When lead status changes, kick off automations
- **AI follow-up** - Let your agent draft and send follow-up emails
- **Lead enrichment** - Automatically research companies and contacts

**Real examples:**
- Sales team tracks prospects from first contact to closed deal, with AI drafting follow-up emails
- Recruiting agency manages candidates, with agents scheduling interviews
- Agency tracks client projects, invoices for work completed, and delivers assets
- Service business manages customer requests, dispatches work, and collects payment

**Why this matters:** You don't need a separate CRM like Salesforce or HubSpot. Everything is built-in, and your AI agents can work with your leads directly - researching, following up, and even closing deals.

---

### 🎙️ Voice AI Agents — Talk to Your AI

**What it is:** Create AI agents that can talk on the phone. Real voice conversations, not just text chat. Your agent can answer calls, make outbound calls, and have natural conversations.

**How it works:**
1. Create an agent with voice enabled
2. Connect a phone number (via VAPI integration)
3. Your agent answers calls 24/7
4. Conversations are transcribed and logged

**Real examples:**
- **Appointment scheduling** - "Hi, I'd like to book an appointment for Thursday" → Agent checks calendar, confirms availability, books it
- **Customer support** - Customers call, agent answers questions using your knowledge base
- **Lead qualification** - Agent calls new leads, asks qualifying questions, updates your CRM
- **After-hours support** - Agent handles calls when your team is unavailable

**Voice capabilities:**
- Natural-sounding speech synthesis
- Real-time conversation (not pre-recorded menus)
- Multiple voice options and personalities
- Call recording and transcription
- Handoff to human when needed

**Why this matters:** Phone support is expensive. A voice AI agent can handle routine calls 24/7 at a fraction of the cost, while complex issues get routed to your team.

---

### 🔄 Agentic Workflows — AI That Takes Action

**What it is:** Unlike traditional automation (if this, then that), agentic workflows let your AI decide what to do based on the situation. You describe the goal, and the agent figures out the steps.

**Traditional automation vs IRIS:**

| Traditional (N8N, Zapier) | IRIS Agentic Workflow |
|---------------------------|----------------------|
| "IF email contains 'urgent' THEN send to channel A" | "Triage incoming emails by urgency and route appropriately" |
| Must define every possible path | AI handles edge cases intelligently |
| Breaks when unexpected situations arise | Adapts to new situations |
| Requires technical setup for each rule | Just describe what you want in English |

**Real examples:**
- "Process support tickets - categorize by issue type, draft responses for simple questions, escalate complex issues to the team"
- "When a new lead comes in, research their company, find relevant case studies in our files, and draft a personalized outreach email"
- "Review these resumes against the job requirements and rank the top 5 candidates"

**Why this matters:** You describe the outcome you want, not every step to get there. The AI figures out how to accomplish your goal, just like a smart employee would.

---

### 🤖 AI Model Access — Use Any AI

**What it is:** IRIS connects to all major AI providers. Use GPT-4, Claude, Gemini, or even run local models with Ollama. Switch models anytime without changing your agents.

**Available models:**
| Provider | Models |
|----------|--------|
| **OpenAI** | GPT-4o, GPT-4o-mini |
| **Anthropic** | Claude 3.5 Sonnet, Claude 3 Haiku |
| **Google** | Gemini Pro, Gemini Flash 1.5 |
| **Open Source** | DeepSeek, Llama 3.1 (via Ollama) |

**Why multiple models?**
- **Cost optimization** - Use cheaper models for simple tasks, premium models for complex ones
- **Capability matching** - Some models are better at certain tasks
- **Redundancy** - If one provider has issues, switch to another
- **Privacy** - Run local models for sensitive data

---

### 📈 Analytics & Reporting

**What it is:** See how your agents are performing. Track conversations, measure response quality, and understand what your users are asking about.

**What you can track:**
- **Conversation metrics** - Total chats, average length, response times
- **Usage patterns** - Peak hours, popular topics, common questions
- **Agent performance** - Which agents are most used, satisfaction indicators
- **Cost tracking** - Token usage, model costs, budget monitoring

**Why this matters:** Data helps you improve. See which agents need better training, identify gaps in your knowledge base, and prove ROI to stakeholders.

---

### 🏢 Team & Collaboration

**What it is:** Work together on agents and knowledge bases. Control who can see, edit, and manage your AI resources.

**Team features:**
- **User roles** - Admin, editor, viewer permissions
- **Shared agents** - Whole team uses the same AI assistants
- **Shared knowledge** - Central knowledge base everyone contributes to
- **Activity logs** - See who changed what and when

---

### 🎨 White Label & Custom Branding (Enterprise)

**What it is:** Make IRIS look like your own product. Custom domains, your logo, your colors - your clients never see the IRIS brand.

**Customization options:**
- **Custom domain** - agents.yourcompany.com
- **Logo and colors** - Match your brand identity
- **Remove IRIS branding** - Completely white-labeled
- **Custom email templates** - Notifications come from your domain

**Why this matters:** Agencies and enterprises can offer AI agents as their own product or seamlessly integrate into existing platforms.

---

## Pricing & Plans

IRIS offers flexible pricing from free to enterprise. Start free and scale as you grow.

### Plan Overview

| Feature | Free | Starter | Growth | Professional | Enterprise |
|---------|------|---------|--------|--------------|------------|
| **AI Agents** | 50 | 50 | 500 | 1,000 | Unlimited |
| **Workflows** | 3 | 10 | 25 | 100 | Unlimited |
| **Contacts (CRM)** | 100 | 100 | 10,000 | 250,000 | Unlimited |
| **Knowledge Items** | 50 | 100 | 2,000 | 10,000 | Unlimited |
| **AI Credits/month** | 100 | 1,000 | 5,000 | 20,000 | Unlimited |
| **Voice AI** | ❌ | ❌ | ❌ | ✅ | ✅ |
| **White Label** | ❌ | ❌ | ❌ | ❌ | ✅ |
| **API Access** | Limited | ✅ | ✅ | ✅ | ✅ |

### What's Included in Every Plan

- ✅ All AI models (GPT-4o, Claude, Gemini, etc.)
- ✅ Knowledge base with automatic RAG
- ✅ 17+ integrations (Google, Slack, etc.)
- ✅ Lead management CRM
- ✅ Web UI, CLI, and SDK access

### Usage-Based Pricing

AI credits are consumed when your agents respond. Different models cost different amounts:
- **Budget models** (GPT-4o-mini, Haiku) - ~1 credit per response
- **Standard models** (GPT-4o, Sonnet) - ~5 credits per response
- **Premium models** (Claude Opus) - ~15 credits per response

Most users find the included credits more than enough. If you need more, credits are available as add-ons.

### Free Trial

Every new account gets **7 days of Enterprise access** - all features, unlimited usage. After the trial, you're automatically moved to the Free plan unless you upgrade.

---

## Three Ways to Use IRIS

### 1. Web Interface (No Code)
Go to [app.heyiris.io](https://app.heyiris.io) and do everything visually:
- Click to create agents
- Drag and drop to upload files
- Point and click to configure integrations
- Visual pipeline for managing leads

**Best for:** Everyone, especially non-technical users

### 2. Command Line (CLI)
Run commands in your terminal for quick actions:
```bash
./bin/iris chat 11 "What can you help me with?"
./bin/iris sdk:call leads.search search=acme status=Won
```

**Best for:** Developers, automation scripts, quick testing

### 3. PHP SDK (Code)
Full programmatic control for building applications:
```php
$agent = $iris->agents->create(new AgentConfig(
    name: 'Support Bot',
    prompt: 'Help customers with product questions',
));
```

**Best for:** Developers integrating IRIS into applications

---

## Everything Stays in Sync

Create an agent in the **web interface**, update it from the **CLI**, access it via the **SDK** - everything works together. Your whole team can collaborate:

- Marketing creates an agent in the UI
- Developer connects it to the company app via SDK
- Operations updates the knowledge base by uploading new files
- Everyone chats with the same agent

---

## Getting Started

### Option 1: Just Use the Web Interface
1. Go to [app.heyiris.io](https://app.heyiris.io)
2. Create an account
3. Click "New Agent" and follow the prompts
4. Upload your files to the knowledge base
5. Share your agent's link

**No installation, no code, no technical setup.**

### Option 2: Use the SDK/CLI (Developers)
```bash
# Install the SDK
composer require iris-ai/sdk

# Configure your credentials
./bin/iris config setup

# Start chatting with agents
./bin/iris chat 11 "Hello!"

# Or use in your PHP code
$iris = new IRIS(['api_key' => 'your-key', 'user_id' => 193]);
$response = $iris->agents->chat(11, [['role' => 'user', 'content' => 'Hello!']]);
```

---

## Agentic Workflows vs Node-Based Automation

**IRIS takes a fundamentally different approach to automation.**

Traditional tools like N8N, Zapier, and Make use **node-based workflows** - you drag boxes, connect lines, and configure each step manually. Every decision path must be pre-defined. Every edge case handled explicitly.

IRIS uses **agentic workflows** - you describe *what* you want, and AI figures out *how* to do it.

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    NODE-BASED (Traditional)                              │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│   [Trigger] → [Parse Email] → [If Contains X] → [Route A]               │
│                                    ↓                                     │
│                              [If Contains Y] → [Route B]                │
│                                    ↓                                     │
│                              [Else] → [Route C] → [Format] → [Send]     │
│                                                                          │
│   ❌ Must pre-define every path                                          │
│   ❌ Breaks when edge cases appear                                       │
│   ❌ Complex logic = spaghetti connections                               │
│   ❌ Changing requirements = rebuild the flow                            │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                    AGENTIC (IRIS)                                        │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│   [Agent with Capabilities] ← "Process incoming emails, categorize      │
│         ↓                      by urgency, draft responses for          │
│   Gmail | Slack | CRM          routine inquiries, escalate complex      │
│         ↓                      issues to the team via Slack"            │
│   [AI Decides & Acts]                                                    │
│                                                                          │
│   ✅ Handles edge cases intelligently                                    │
│   ✅ Adapts to new situations                                            │
│   ✅ Natural language = anyone can modify                                │
│   ✅ Changing requirements = update the prompt                           │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### How It Works

1. **Give agents capabilities** (integrations they can use)
2. **Write a prompt** (goals, intentions, guidelines)
3. **Let AI decide** (routes, actions, responses)

```php
// Create an agent with capabilities
$agent = $iris->agents->create(new AgentConfig(
    name: 'Email Processor',
    prompt: <<<PROMPT
        You are an email processing assistant.

        GOALS:
        - Categorize incoming emails by urgency (high/medium/low)
        - Draft responses for routine inquiries
        - Escalate complex issues to Slack #support channel
        - Log all customer interactions to our CRM

        GUIDELINES:
        - Be professional but friendly
        - If unsure, ask for clarification rather than guessing
        - Always CC the account manager on high-value client emails
    PROMPT,
    integrations: ['gmail', 'slack', 'google-drive'],
));

// That's it! The agent figures out the rest.
$response = $iris->agents->chat($agent->id, [
    ['role' => 'user', 'content' => 'Process the last 10 unread emails']
]);
```

### Prompts Guide Everything

With IRIS, **prompts are your workflow logic**:

| Traditional Node | IRIS Prompt Equivalent |
|-----------------|------------------------|
| If/Then branch | "If the customer mentions pricing, focus on value..." |
| Loop node | "For each lead in the list, research their company..." |
| Filter node | "Only process emails from @enterprise.com domains..." |
| Transform node | "Summarize the document into 3 bullet points..." |
| API call node | "Search Google Drive for relevant contracts..." |

### Why This Matters

- **Non-technical users** can modify workflows by editing prompts
- **Edge cases** are handled intelligently, not with more nodes
- **Complex logic** becomes simple English instructions
- **Maintenance** is updating a prompt, not rewiring a flowchart

📖 [See Multi-Step Workflows in Action](#-multi-step-workflows)

---

## Train Your AI Coding Assistant

**Give [TECHNICAL.md](TECHNICAL.md) to your AI coding assistant and it becomes an IRIS expert.**

The technical documentation is specifically structured for LLM consumption. Your AI assistant can learn the entire SDK and CLI, then help you build agents, manage leads, and automate workflows.

### Supported Platforms

| Platform | How to Use |
|----------|------------|
| **Claude Code** | Add `TECHNICAL.md` to your project or reference it in prompts |
| **GitHub Copilot** | Include in your workspace for context-aware suggestions |
| **Cursor** | Add to your project's docs folder for AI indexing |
| **Windsurf** | Reference in your codebase for intelligent completions |
| **Lovable** | Upload as project documentation |
| **Codex / ChatGPT** | Paste or upload for code generation assistance |
| **Google AI Studio** | Upload as context for Gemini-powered development |

### Example: Teaching Claude Code

```bash
# In your project, tell Claude:
"Read TECHNICAL.md and help me build an agent that processes
customer support emails and escalates urgent issues to Slack"
```

Claude will understand:
- All available SDK methods (`$iris->agents->create()`, etc.)
- CLI commands (`./bin/iris chat`, `./bin/iris sdk:call`)
- Integration options (Gmail, Slack, Google Drive, etc.)
- Best practices for prompts and agent configuration

### Example: Automated Development Pipeline

```bash
# Your AI assistant can now run IRIS commands directly
./bin/iris sdk:call agents.create name="Support Bot" prompt="..."
./bin/iris sdk:call agents.uploadAndAttachFiles 123 /docs/knowledge.pdf bloq_id=40
./bin/iris chat 123 "Test: How do I reset my password?"
```

### What Your AI Assistant Learns

From [TECHNICAL.md](TECHNICAL.md), your AI coding assistant understands:

- **40+ SDK methods** across agents, leads, bloqs, workflows, and integrations
- **CLI syntax** for rapid prototyping and testing
- **Authentication patterns** (API keys, OAuth, credential management)
- **Code examples** for every feature with copy-paste snippets
- **Best practices** for agent prompts, RAG setup, and workflow design

**The result?** Ask your AI assistant to "create a lead qualification agent with Google Calendar integration" and it knows exactly how to do it.

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
