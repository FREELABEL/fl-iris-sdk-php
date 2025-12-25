# IRIS PHP SDK

Official PHP SDK for the **IRIS AI Platform** - Build intelligent agents, execute multi-step workflows, and manage leads with comprehensive CRM functionality.

## 🚀 Quick Examples

```bash
# 💬 Chat with AI agents (real-time progress display!)
./bin/iris chat 11 "Hello, what can you do?"
./bin/iris chat 337 "Analyze my leads" --bloq=40

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

# 📝 Generate article from YouTube video
./bin/iris tools article --url="https://www.youtube.com/watch?v=abc123" --length=medium --style=informative

# ⚖️ Generate legal demand package
./bin/iris tools demand-package --case-id="Richard Ramos" --ai-model=gpt-5-nano
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

Configure the SDK using the `.env` file:

```bash
# Copy the example and edit with your credentials
cp .env.example .env
```

**Required `.env` Configuration:**

```bash
# IRIS SDK Configuration
# ======================
# The SDK uses TWO separate APIs:
# - IRIS API: agents, chat, workflows (iris-api.freelabel.net)
# - FL-API: leads, deliverables, profiles, services (apiv2.heyiris.io)

# API Authentication (same token works for both APIs)
IRIS_API_KEY=your_sdk_key_from_developer_portal
IRIS_USER_ID=your_user_id

# Environment: 'production' or 'local'
IRIS_ENV=production

# ========================================
# Production API URLs
# ========================================
# IRIS API - agents, chat, workflows, bloqs
IRIS_API_URL=https://iris-api.freelabel.net

# FL-API - leads, deliverables, profiles, services
FL_API_URL=https://apiv2.heyiris.io

# ========================================
# Local Development URLs (when IRIS_ENV=local)
# ========================================
IRIS_LOCAL_URL=https://local.iris.freelabel.net
FL_API_LOCAL_URL=https://local.raichu.freelabel.net

# Optional: OAuth credentials for advanced use
# IRIS_CLIENT_ID=your-oauth-client-id
# IRIS_CLIENT_SECRET=your-oauth-client-secret
```

### ⚠️ Critical: API Routing

The SDK automatically routes requests to the correct API based on the endpoint pattern:

**IRIS API** (`iris-api.freelabel.net`) handles:
- `/iris/*` - Core IRIS functionality
- `/chat/*` - AI chat and workflows
- `/workflows/*` - Multi-step workflows
- `/agents/*` - Agent management
- `/bloqs/*` - Knowledge bases

**FL-API** (`apiv2.heyiris.io`) handles:
- `/leads` - Lead management and CRM
- `/deliverables` - Lead deliverables
- `/profile` and `/profiles` - Profile creation and management (both singular and plural!)
- `/services` - Service offerings
- `/users/*` - User-specific endpoints

**Important:** The HTTP Client checks for endpoint patterns to route correctly. If you're getting "method not supported" errors, verify:
1. The endpoint pattern is included in the routing logic (see `src/Http/Client.php`)
2. Both `/profile` (singular) and `/profiles` (plural) route to FL-API
3. Your `.env` has the correct API URLs for your environment

### Configuration Status

Check your configuration:

```bash
./bin/iris config
```

### Override via CLI

You can override `.env` values using CLI flags:

```bash
./bin/iris chat 11 "Hello!" --api-key=sk_xxx --user-id=123
```

### Environment Switching (Local vs Production)

If your `.env` is set to `IRIS_ENV=local` for development, but you need to run a quick command against the **Production API**, you can override the environment variable directly in your shell command without changing your `.env` file:

```bash
# Force production environment for a single command
IRIS_ENV=production ./bin/iris sdk:call leads.list

# Force local environment
IRIS_ENV=local ./bin/iris chat 11 "Hello local agent"
```

This is the preferred way to interact with live production data while keeping your local development environment intact.

Once configured, use any CLI command:

```bash
./bin/iris chat 11 "Hello!"
./bin/iris sdk:call leads.search search=john bloq_id=40
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

#### ⚠️ Important: String Parameters with Spaces

**Always use quotes for multi-word strings** - this is standard CLI behavior:

```bash
# ✅ CORRECT - Use quotes for content with spaces
./bin/iris sdk:call leads.addNote 518 "This is a multi-word note about the meeting"
./bin/iris sdk:call leads.tasks.create 412 title="Setup delivery meeting" description="Prepare demo materials"

# ❌ WRONG - Without quotes, each word becomes a separate argument
./bin/iris sdk:call leads.addNote 518 This is wrong

# Quote types (all valid)
./bin/iris sdk:call leads.addNote 518 "Double quotes work"
./bin/iris sdk:call leads.addNote 518 'Single quotes work too'
./bin/iris sdk:call leads.addNote 518 "He said \"hello\""  # Escaped quotes

# For long multi-line content, use heredoc or text files
./bin/iris sdk:call leads.addNote 518 "$(cat << 'EOF'
Line 1 of note
Line 2 of note
Line 3 of note
EOF
)"

# Or read from file
./bin/iris sdk:call leads.addNote 518 "$(cat meeting-notes.txt)"
```

**Why quotes are required:**
- Shell interprets spaces as argument separators
- Quotes group words into a single argument
- Standard behavior across all CLI tools (`git commit -m "message"`, `echo "hello world"`, etc.)
- Ensures predictable, type-safe parameter passing

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

### Recruitment Tools

Generate recruitment search queries and score candidates using AI-powered analysis.

#### List Available Tools

```bash
./bin/iris tools
```

#### Generate Recruitment Queries from Job Description

```bash
# From a PDF file
./bin/iris tools recruitment \
  --file=/path/to/job-description.pdf \
  --location="Austin, TX" \
  --experience=senior

# From text
./bin/iris tools recruitment \
  --job-description="Senior Solutions Engineer with 5+ years SaaS implementation..." \
  --platform=linkedin \
  --location="Austin, TX"

# JSON output for scripting
./bin/iris tools recruitment \
  --file=/path/to/job.pdf \
  --json
```

**Options:**
| Option | Description |
|--------|-------------|
| `--file`, `-f` | Path to PDF or DOCX file containing job description |
| `--job-description`, `-d` | Job description text (alternative to file) |
| `--platform`, `-p` | Target platform: `linkedin`, `github`, `twitter` (default: linkedin) |
| `--location`, `-l` | Target location for candidates |
| `--experience`, `-e` | Experience level: `entry`, `mid`, `senior`, `lead`, `executive` |
| `--json` | Output as JSON for scripting |

**Example Output:**
```
Generating recruitment queries...

Job Title: Senior Solutions Engineer, Insurance

=== Extracted Requirements ===
Must-Have Skills:
  • Client-facing SaaS implementation experience (4+ years)
  • Ownership of deployments from kickoff to go-live
  • Platform configuration for customers
  • Insurance or healthcare-adjacent industry experience

Nice-to-Have Skills:
  • Training, workshop, or enablement session facilitation
  • Ability to translate technical concepts into plain English

Title Keywords:
  Senior Solutions Engineer, Solutions Engineer, Implementation Engineer

Experience: 4+ years

=== Search URLs ===
Primary: Job Title Search:
  https://www.linkedin.com/search/results/people/?keywords=Senior+Solutions+Engineer...

Extended Network Search:
  https://www.linkedin.com/search/results/people/?keywords=Senior+Solutions+Engineer&network=...

=== Boolean Queries ===
Primary Boolean Query:
  ("Senior Solutions Engineer" OR "Implementation Engineer") AND (SaaS implementation OR...)

=== Browser Extraction Script ===
Copy this JavaScript into browser console on LinkedIn search results:
  // LinkedIn Profile Extractor v3.0...

=== Instructions ===
## How to Extract Candidate Profiles
...
```

#### Score Candidates

After extracting candidate profiles using the browser script:

```bash
# Score candidates against job requirements
./bin/iris tools candidate-score \
  --data='[{"name":"John Smith","title":"Solutions Engineer",...}]' \
  --requirements='{"must_have_skills":["SaaS","API"],...}'

# Or via sdk:call
./bin/iris sdk:call tools.scoreCandidates \
  candidate_data='[{"name":"John Smith","title":"Solutions Engineer",...}]' \
  requirements='{"must_have_skills":["SaaS","API"],...}'
```

```php
// PHP SDK usage
$result = $iris->tools->recruitment([
    'job_description_file' => '/path/to/job.pdf',
    'platform' => 'linkedin',
    'location' => 'Austin, TX',
]);

echo "Found " . count($result->searchUrls) . " search URLs\n";
echo "Must-have skills: " . implode(', ', $result->getMustHaveSkills()) . "\n";

// Score extracted candidates
$scoring = $iris->tools->scoreCandidates([
    'candidate_data' => $extractedCandidatesJson,
    'requirements' => $result->requirements,
]);

echo "Strong matches: " . count($scoring->strongMatches) . "\n";
foreach ($scoring->getTopCandidates(5) as $candidate) {
    echo "  {$candidate['rank']}. {$candidate['name']} - {$candidate['overall_score']}%\n";
}
```

#### Full Recruitment Workflow

```bash
# 1. Generate search queries from PDF
./bin/iris tools recruitment --file=/path/to/job.pdf --location="Austin, TX" --json > queries.json

# 2. Open LinkedIn search URLs from queries.json
# 3. Run extraction script in browser console
# 4. Copy extracted JSON data to candidates.json

# 5. Score candidates
./bin/iris tools candidate-score \
  --data="$(cat candidates.json)" \
  --requirements="$(jq '.requirements' queries.json)"
```

**Output includes:**
- Ranked candidate list with scores (0-100%)
- Categorized matches: Strong (80%+), Good (60-79%), Potential (40-59%), Low (<40%)
- Scoring breakdown per candidate (skills, experience, title, location, network)

### Article Generation

Generate articles from YouTube videos, topics, webpages, or RSS feeds using AI-powered content generation.

#### From YouTube Video (Most Common)

```bash
# Generate article from YouTube video
./bin/iris tools article \
  --url="https://www.youtube.com/watch?v=dQw4w9WgXcQ" \
  --length=medium \
  --style=informative

# Dry run (don't publish to Freelabel)
./bin/iris tools article \
  --url="https://www.youtube.com/watch?v=abc123" \
  --length=long \
  --style=analysis \
  --no-publish

# Publish to specific profile
./bin/iris tools article \
  --url="https://www.youtube.com/watch?v=xyz789" \
  --profile-id=9203684 \
  --publish
```

#### From Topic (Research-Based)

```bash
# Generate article from research topic
./bin/iris tools article \
  --topic="The future of AI in healthcare" \
  --source-type=topic \
  --length=long \
  --style=editorial

# Short newsletter style
./bin/iris tools article \
  --topic="Top 10 productivity tips for remote workers" \
  --source-type=topic \
  --length=short \
  --style=newsletter
```

#### From Webpage or RSS Feed

```bash
# Generate from webpage content
./bin/iris tools article \
  --url="https://example.com/blog/interesting-article" \
  --source-type=webpage \
  --length=medium

# Generate from RSS feed
./bin/iris tools article \
  --url="https://example.com/feed.xml" \
  --source-type=rss \
  --length=short
```

**Options:**

| Option | Description | Default |
|--------|-------------|---------|
| `--url`, `-u` | YouTube URL, webpage URL, or RSS feed URL | - |
| `--topic`, `-t` | Topic for research-based article generation | - |
| `--source-type`, `-s` | Source type: `video`, `topic`, `webpage`, `rss` | `video` |
| `--length` | Article length: `short`, `medium`, `long` | `medium` |
| `--style` | Writing style: `informative`, `editorial`, `newsletter`, `analysis` | `informative` |
| `--profile-id` | Profile ID for publishing the article | - |
| `--publish` | Publish to Freelabel platform | - |
| `--no-publish` | Don't publish (dry run mode) | - |
| `--json` | Output as JSON for scripting | - |

**Example Output:**

```
Article Generation
==================

Source Type: video
Source: https://www.youtube.com/watch?v=dQw4w9WgXcQ
Length: medium
Style: informative
Publish: No (dry run)

 Dispatching article generation job...

 [OK] Article generation job dispatched!

The article is being generated in the background.

Job Details:
  Message: Article generation started
  Queue: article-generation
  Source: https://www.youtube.com/watch?v=dQw4w9WgXcQ

Note: Article generation takes 1-3 minutes. Check your dashboard for the result.
```

**How It Works:**

1. **For YouTube videos**: Extracts transcript via SupaData.ai API
2. **For topics**: Performs AI-powered research using web search
3. **For webpages**: Extracts and summarizes content
4. **For RSS feeds**: Synthesizes content from feed items
5. NeuronAI RAG processes content through 4-phase pipeline:
   - **Indexer**: Structures and indexes source content
   - **Editor**: Plans article structure and key points
   - **Reporter**: Writes the full article draft
   - **Publisher**: Polishes and formats for publication

**PHP SDK Usage:**

```php
// Generate from YouTube video
$result = $iris->articles->generateFromVideo([
    'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    'article_length' => 'medium',
    'article_style' => 'informative',
]);

// Generate from topic
$result = $iris->articles->generateFromTopic(
    'The impact of AI on modern education',
    ['article_length' => 'long', 'article_style' => 'analysis']
);

// Generate from any source
$result = $iris->articles->generate([
    'source_type' => 'video',
    'source' => 'https://www.youtube.com/watch?v=abc123',
    'article_length' => 'medium',
    'article_style' => 'informative',
    'profile_id' => 9203684,
    'publish_to_fl' => true,
]);

// Create article directly (skip AI generation)
$article = $iris->articles->create([
    'profile_id' => 9203684,
    'title' => 'My Custom Article',
    'content' => '<p>Article content here...</p>',
]);
```

**Note:** Article generation is an **async operation**. The job is dispatched to a background queue and typically takes 1-3 minutes to complete. Check your dashboard or use webhooks to receive notifications when the article is ready.

### Legal Demand Package Generation

Generate comprehensive AI-powered legal demand packages for personal injury cases using ServisAI integration. Creates case summaries, medical chronologies, patient details, and settlement demand letters in multiple formats.

#### Generate Demand Package

```bash
# Generate demand package for a case
./bin/iris tools demand-package \
  --case-id="Richard Ramos" \
  --ai-model=gpt-5-nano

# Use different AI model
./bin/iris tools demand-package \
  --case-id="CAS100508" \
  --ai-model=gpt-4o

# Disable cloud upload (local only)
./bin/iris tools demand-package \
  --case-id="John Smith" \
  --no-publish

# Use cached results if available
./bin/iris tools demand-package \
  --case-id="Richard Ramos" \
  --use-cache

# JSON output for automation
./bin/iris tools demand-package \
  --case-id="Richard Ramos" \
  --json
```

**Options:**

| Option | Description | Default |
|--------|-------------|---------|
| `--case-id`, `-c` | Patient name or case number (e.g., "Richard Ramos", "CAS12345") | **Required** |
| `--ai-model`, `-m` | AI model: `gpt-4o`, `gpt-5-nano`, `claude-3-5-sonnet` | `gpt-5-nano` |
| `--upload-to-gcs` | Upload to Google Cloud Storage (enabled by default) | `true` |
| `--use-cache` | Use cached results if available | `false` |
| `--json` | Output as JSON for scripting | - |

**Example Output:**

```
Generating Legal Demand Package
-------------------------------

 Case ID: Richard Ramos
 AI Model: gpt-5-nano
 Upload to GCS: Yes
 Use Cache: No

 ⏳ Generating demand package via ServisAI...

 [OK] Demand package generated successfully!

Results
-------

 Case ID ........... 8c0d8d1c-98ba-4596-9239-d0d93b7690ac
 Output Type ....... demand_package
 AI Model .......... gpt-5-nano
 Execution Time .... 56.7s
 Total Billing ..... $0.00

Download
--------

 📄 https://storage.googleapis.com/gs-dev-media-assets/demand-packages/case-8c0d8d1c...

Components Generated
--------------------

 ✓ Case Summary
 ✓ Medical Chronology
 ✓ Patient Details
 ✓ Medical Services

Preview (First 500 chars)
--------------------------

 # Demand Package for Settlement
 
 **Case ID:** CAS100508
 **Patient:** Richard Ramos
 **Generated:** December 24, 2025
 
 ---
 
 Executive Summary
 Richard Ramos sustained injuries in an incident on February 17, 2022...
 
 Full length: 24,172 characters
```

**What Gets Generated:**

The demand package tool creates comprehensive legal documentation including:

1. **Executive Summary**: Overview of the case, injuries, and settlement demand
2. **Medical Chronology**: Detailed timeline of all medical treatments and services
3. **Patient Details**: Demographics, contact information, and case metadata
4. **Medical Services**: Itemized list of all treatments with dates and providers
5. **Demand Letter**: AI-drafted settlement demand with liability analysis
6. **Multi-Format Output**: 
   - PDF (print-ready)
   - DOCX (editable)
   - HTML (web-ready)
   - Markdown (source)
   - ZIP bundle (all formats)

**Alternative: Docker Direct Execution**

For development and testing, you can also run the demand package tool directly in the Docker container:

```bash
# Run in fl-iris-api container
docker exec fl-iris-api php test-demand-package.php "Richard Ramos"
```

**PHP SDK Usage:**

```php
// Generate demand package via ServisAI integration
$result = $iris->integrations->execute('servis-ai', 'create_demand_package', [
    'case_id' => 'Richard Ramos',
    'options' => [
        'ai_model' => 'gpt-5-nano',
        'upload_to_gcs' => true,
        'use_cache' => false,
    ],
]);

// Access results
echo "Case ID: {$result['case_id']}\n";
echo "Download URL: {$result['gcs_url']}\n";
echo "Components:\n";
if ($result['components']['summary']) echo "  ✓ Summary\n";
if ($result['components']['chronology']) echo "  ✓ Chronology\n";
if ($result['components']['patient_details']) echo "  ✓ Patient Details\n";
```

**How It Works:**

1. **Case Lookup**: Searches ServisAI system by case ID or patient name (natural language)
2. **Data Retrieval**: Fetches all medical records, treatments, and case details
3. **AI Analysis**: Uses GPT-4o/5-nano/Claude to analyze medical records and draft documents
4. **Document Generation**: Creates comprehensive demand package with all components
5. **Multi-Format Export**: Generates PDF, DOCX, HTML, and Markdown versions
6. **Cloud Upload**: Uploads to Google Cloud Storage and returns download URL
7. **BLOQ Integration**: Creates BLOQ item with all document formats attached

**Note:** Demand package generation is an **async operation** for large cases. It typically takes 30-90 seconds depending on case complexity and number of medical records. The tool runs in the background with real-time progress tracking.

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

### 💬 Real-Time Chat

Chat with AI agents using the V5 workflow system with real-time progress tracking.

#### CLI Chat Command (Recommended)

The dedicated `chat` command provides beautiful progress display and formatted output:

```bash
# Basic chat
./bin/iris chat 11 "Hello, what can you do?"

# Chat with bloq context (for RAG)
./bin/iris chat 337 "Analyze the attached documents" --bloq=40

# JSON output for scripting
./bin/iris chat 11 "Generate a report" --json

# Custom timeout
./bin/iris chat 11 "Long running task" --timeout=600
```

**Example Output:**
```
╭─────────────────────────────────────────────────────────────╮
│ 🤖 Agent #11 (Bloq: 40)                                     │
╰─────────────────────────────────────────────────────────────╯

📤 Sending: "Hello, what can you do?"

⠙ ⏳ Running (2.3s)

✅ Complete!

╭─────────────────────────────────────────────────────────────╮
│ Hello! I'm IRIS AI. I can help you with:                    │
│                                                             │
│ • Lead management and CRM tasks                             │
│ • Content generation and analysis                           │
│ • Integration with Google Drive, Gmail, etc.                │
│ • Workflow automation                                       │
│                                                             │
│ What would you like to work on today?                       │
╰─────────────────────────────────────────────────────────────╯

📊 Tokens: 245 | Time: 2.3s | Model: gpt-4o-mini | Agent: IRIS AI
```

#### PHP SDK Usage

```php
// Simple blocking execution (recommended)
$result = $iris->chat->execute([
    'query' => 'Hello, what can you do?',
    'agentId' => 11,
    'bloqId' => 40,  // Optional: for RAG context
]);

echo $result['summary'];

// With progress callback
$result = $iris->chat->execute([
    'query' => 'Analyze my leads',
    'agentId' => 337,
], function($status) {
    echo "Status: {$status['status']}\n";
});

// Async usage (start + poll manually)
$response = $iris->chat->start([
    'query' => 'Generate a report',
    'agentId' => 11,
]);

$workflowId = $response['workflow_id'];

while (true) {
    $status = $iris->chat->getStatus($workflowId);

    if ($status['status'] === 'completed') {
        echo $status['summary'];
        break;
    }

    if ($status['status'] === 'failed') {
        throw new Exception($status['error']);
    }

    usleep(500000); // 500ms
}
```

#### Human-in-the-Loop (HITL)

Handle workflows that require human approval:

```php
$result = $iris->chat->execute([
    'query' => 'Send email to all leads',
    'agentId' => 11,
]);

// Check if paused for approval
if ($result['status'] === 'paused' && $result['requires_approval']) {
    echo "Approval needed: {$result['pending_approval']['approval_prompt']}\n";

    // Resume with approval
    $iris->chat->resume($result['workflow_id'], [
        'approved' => true,
        'comment' => 'Looks good, proceed!',
    ]);
}
```

#### Conversation History & Summarization

```php
// Get user's chat history
$history = $iris->chat->history([
    'status' => 'completed',
    'per_page' => 20,
]);

// Get workflow statistics
$stats = $iris->chat->stats();
echo "Total: {$stats['total_workflows']}, Success: {$stats['success_rate']}%\n";

// Summarize long conversations to save tokens
$summarized = $iris->chat->summarize($messages, keepRecent: 4, threshold: 20);
```

### 🧠 Persistent Memory & Knowledge Base (Bloqs)

Build long-term memory for your AI agents using Bloqs - intelligent containers that automatically index content for RAG (Retrieval-Augmented Generation).

#### How It Works

```
┌─────────────────────────────────────────────────────────────────┐
│                    BLOQ (Knowledge Container)                    │
├─────────────────────────────────────────────────────────────────┤
│  📁 Lists (Categories)           │  🤖 Agents (AI Assistants)   │
│  ├── 📄 Items (Content)          │  ├── Recruiter Agent         │
│  │   ├── Text/Documents          │  ├── Sales Agent             │
│  │   ├── File Attachments ──────────── (auto-indexed for RAG)  │
│  │   └── Chat History            │  └── Support Agent           │
│  └── Custom Fields               │                              │
└─────────────────────────────────────────────────────────────────┘
                    │
                    ▼ Auto-Vectorized (OpenAI Embeddings)
         ┌─────────────────────────┐
         │   Pinecone Vector DB    │
         │   (Semantic Search)     │
         └─────────────────────────┘
                    │
                    ▼ RAG Context Retrieval
         ┌─────────────────────────┐
         │   Agent Chat Response   │
         │   (Enriched with KB)    │
         └─────────────────────────┘
```

#### Create a Knowledge Base

```php
// Create a bloq (knowledge container)
$kb = $iris->bloqs->create('Customer Support KB', [
    'description' => 'Support documentation and FAQs',
]);

// Create organized lists (categories)
$faqList = $iris->bloqs->lists($kb->id)->create([
    'title' => 'FAQs',
    'type' => 'document',
]);

// Add content (automatically vectorized for RAG search)
$item = $iris->bloqs->items($faqList->id)->create([
    'title' => 'Refund Policy',
    'content' => 'Our refund policy allows returns within 30 days...',
    'description' => 'Customer refund guidelines',
]);

// Upload files (PDF, CSV, TXT auto-extracted and indexed)
$file = $iris->bloqs->uploadFile($kb->id, '/path/to/handbook.pdf', [
    'title' => 'Employee Handbook',
]);
```

**CLI:**
```bash
# Create bloq
./bin/iris sdk:call bloqs.create title="Customer Support KB"

# Add content
./bin/iris sdk:call bloqs.addContent 40 title="Refund Policy" content="Returns within 30 days..."

# Upload file (auto-indexed for RAG)
./bin/iris sdk:call bloqs.uploadFile 40 /path/to/document.pdf
```

#### Assign Agent to Knowledge Base

```php
// Create an agent with bloq as its knowledge base
$agent = $iris->agents->create([
    'name' => 'Support Bot',
    'initial_prompt' => 'You are a helpful customer support agent.',
    'bloq_id' => $kb->id,  // Agent uses this bloq for RAG
    'config' => [
        'model' => 'gpt-4o-mini',
        'temperature' => 0.7,
    ],
]);

// Attach files directly to agent (also indexed for RAG)
$iris->agents->uploadAndAttachFiles($agent->id, [
    '/path/to/product_catalog.pdf',
    '/path/to/pricing.csv',
], $kb->id);

// Chat - agent automatically retrieves relevant context from KB
$response = $iris->chat->execute([
    'query' => 'What is your refund policy?',
    'agentId' => $agent->id,
    'bloqId' => $kb->id,
]);
// Response is enriched with relevant KB content via RAG
```

**CLI:**
```bash
# Create agent with bloq
./bin/iris sdk:call agents.create name="Support Bot" bloq_id=40

# Upload files to agent knowledge base
./bin/iris sdk:call cloudFiles.uploadForAgent /path/to/data.pdf bloq_id=40

# Chat (uses RAG automatically)
./bin/iris chat 337 "What is your refund policy?" --bloq=40
```

#### Share Knowledge Base (Collaboration)

```php
// Share bloq with team members
$iris->bloqs->share($kb->id, $teammateUserId, 'write');

// Get shared users
$sharedWith = $iris->bloqs->getSharedUsers($kb->id);

// Update permissions
$iris->bloqs->updateSharePermission($kb->id, $teammateUserId, 'admin');

// Remove access
$iris->bloqs->unshare($kb->id, $teammateUserId);
```

**CLI:**
```bash
# Share with teammate
./bin/iris sdk:call bloqs.share 40 user_id=456 permission=write

# List shared users
./bin/iris sdk:call bloqs.getSharedUsers 40
```

#### Public Sharing (External Access)

```php
// Make an item publicly accessible
$result = $iris->bloqs->makeItemPublic($itemId);
echo "Public URL: {$result['public_url']}";

// Revoke public access
$iris->bloqs->makeItemPrivate($itemId);
```

#### Custom Fields for Structured Data

```php
// Configure custom fields for leads/items in this bloq
$iris->bloqs->updateCustomFieldsConfig($kb->id, [
    'fields' => [
        ['id' => 'company', 'label' => 'Company Name', 'type' => 'text', 'required' => true],
        ['id' => 'phone', 'label' => 'Phone', 'type' => 'tel'],
        ['id' => 'service', 'label' => 'Service Type', 'type' => 'select', 'options' => ['Web', 'Mobile', 'AI']],
    ],
]);

// Add single field
$iris->bloqs->addCustomField($kb->id, [
    'id' => 'budget',
    'label' => 'Budget Range',
    'type' => 'select',
    'options' => ['<$5k', '$5k-$10k', '$10k+'],
]);
```

---

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

#### Get Agent URLs (Embed/Share)

Get shareable URLs for your agents. These URLs allow users to interact with your agent directly at **app.heyiris.io**.

```bash
# Get all URLs for an agent
./bin/iris sdk:call agents.getUrls 11

# Get just the simple URL
./bin/iris sdk:call agents.getUrl 11
```

```php
// Get all URLs
$urls = $iris->agents->getUrls(11);
echo $urls['simple'];   // https://app.heyiris.io/agent/simple/11?bloq=40
echo $urls['embed'];    // Same as simple (alias)
echo $urls['public'];   // https://app.heyiris.io/agent/my-slug (if public)

// Get just the embed/share URL
$url = $iris->agents->getUrl(11);
// → https://app.heyiris.io/agent/simple/11?bloq=40

// Or from an agent instance
$agent = $iris->agents->get(11);
$url = $agent->getSimpleUrl();
$url = $agent->getEmbedUrl();  // alias
$allUrls = $agent->getUrls();

// Custom base URL (for self-hosted or local dev)
$url = $agent->getSimpleUrl('https://local.elon.freelabel.net');
// → https://local.elon.freelabel.net/agent/simple/11?bloq=40
```

**URL Types:**
- **simple/embed**: Direct link to chat with the agent (`/agent/simple/{id}?bloq={bloqId}`)
- **public**: Slug-based URL if agent is public (`/agent/{slug}`)

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

#### Stripe Payment History

Get payment history from Stripe for a lead based on their email address.

```php
$payments = $iris->leads->stripePayments(16);

echo "Customer found: " . ($payments['has_stripe_customer'] ? 'Yes' : 'No') . "\n";
echo "Total paid: $" . number_format($payments['total_paid'] / 100, 2) . "\n";

// List recent payments
foreach ($payments['payments'] as $payment) {
    echo "- {$payment['description']}: \${$payment['amount'] / 100} ({$payment['status']})\n";
}

// List Stripe invoices
foreach ($payments['invoices'] as $invoice) {
    echo "Invoice #{$invoice['number']}: \${$invoice['amount_due'] / 100}\n";
}
```

**CLI:**
```bash
# Get Stripe payment history for a lead
iris sdk:call leads.stripePayments 16
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

#### ReAct AI Enrichment (Advanced)

The ReAct (Reasoning + Acting) enrichment pattern provides intelligent, goal-driven lead enrichment. It uses AI reasoning to select optimal search strategies and includes free native HTTP scraping before using paid APIs.

**How ReAct Works:**

```
┌─────────────────────────────────────────────────────────────────┐
│                    ReAct Loop (max 3-5 iterations)              │
├─────────────────────────────────────────────────────────────────┤
│  1. OBSERVE: Analyze current state - what do we have/need?      │
│  2. THINK: AI reasons about best search strategy                │
│  3. ACT: Execute search (native HTTP → Tavily → FireCrawl)      │
│  4. EVALUATE: Did we achieve the goal? Stop or continue.        │
└─────────────────────────────────────────────────────────────────┘
```

**Features:**
- **Goal-driven**: Specify what you need (email, phone, or all)
- **Cost-optimized**: Native HTTP scraping tries first (free)
- **AI-powered**: Smart strategy selection based on context
- **Early exit**: Stops as soon as goal is achieved

```php
// ReAct enrichment with email goal
$result = $iris->leads->enrichReAct(510, [
    'goal' => 'email',          // 'email', 'phone', or 'all'
    'max_iterations' => 3,      // 1-5 iterations
    'use_native_http' => true   // Try free scraping first
]);

if ($result['goal_achieved']) {
    echo "Found emails!\n";
    foreach ($result['found_contacts']['emails'] as $email) {
        echo "  - {$email}\n";
    }
}

// View AI reasoning
foreach ($result['reasoning'] as $thought) {
    echo "AI: {$thought}\n";
}

// Apply confirmed data to lead
if (!empty($result['found_contacts']['emails'])) {
    $iris->leads->applyEnrichment(510, [
        'email' => $result['found_contacts']['emails'][0],
        'company' => $result['found_contacts']['company'],
        'linkedin_url' => $result['found_contacts']['linkedin_url']
    ]);
}
```

**CLI ReAct Enrichment:**

```bash
# Find email using ReAct pattern
./bin/iris sdk:call leads.enrichReAct 510 goal=email max_iterations=3 use_native_http=true

# Find all contact info
./bin/iris sdk:call leads.enrichReAct 510 goal=all

# Apply confirmed data
./bin/iris sdk:call leads.applyEnrichment 510 email="john@example.com" company="Acme Corp"
```

**Response Structure:**

```json
{
    "success": true,
    "lead_id": 510,
    "found_contacts": {
        "emails": ["john@coffee.com", "info@coffee.com"],
        "phones": ["512-555-1234", "(512) 555-5678"],
        "company": "Jo's Coffee",
        "website": "https://joscoffee.com",
        "linkedin_url": "https://linkedin.com/company/joscoffee",
        "address": "123 Main St, Austin, TX"
    },
    "goal": "email",
    "goal_achieved": true,
    "iterations": 2,
    "reasoning": [
        "Lead has no email. Starting with general web search.",
        "Found website. Trying native HTTP scrape on contact page.",
        "Email found! Goal achieved."
    ],
    "sources": ["https://joscoffee.com/contact"]
}
```

**Best Practices:**
- Use `goal=email` for faster results when you only need email
- Set `use_native_http=true` (default) to minimize API costs
- Keep `max_iterations` at 3 unless you need exhaustive search
- Always review results before applying with `applyEnrichment()`

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

### � Profile & Services Management

Create and manage user profiles with service offerings. Profiles live at public URLs on the FreeLABEL network:

- **Primary URL**: `freelabel.net/username` (redirects to production)
- **Production**: `the.freelabel.net/username`
- **Local Dev**: `local.elon.freelabel.net/username`

#### Create a Profile

```php
// Create a profile
$profile = $iris->profiles->create([
    'username' => 'nsgbillz',
    'name' => 'NSG Billz',
    'bio' => 'Credit repair specialist and videographer',
    'city' => 'Dallas',
    'state' => 'Texas',
    'instagram' => 'nsgbillz',
    'user_id' => 193,
]);

echo "Profile created: {$profile['id']}\n";

// Get public URL
$url = $profile->getPublicUrl();
echo "URL: {$url}\n";  // https://freelabel.net/nsg-billz

// Or specify environment
$prodUrl = $profile->getPublicUrl('https://the.freelabel.net');
$localUrl = $profile->getPublicUrl('https://local.elon.freelabel.net');
```

**CLI:**
```bash
# Create profile
./bin/iris sdk:call profiles.create \
  username=nsgbillz \
  name='NSG Billz' \
  bio='Credit repair specialist and videographer' \
  city='Dallas' \
  state='Texas' \
  instagram=nsgbillz \
  user_id=193

# Profile will be accessible at: https://freelabel.net/nsg-billz
```

#### Create Services for a Profile

Services define offerings that appear on the profile page with pricing.

```php
// Create a service
$service = $iris->services->create([
    'profile_id' => 9203684,
    'title' => 'Credit Repair Services',
    'description' => 'Professional credit restoration services',
    'price' => 500,
    'price_max' => 2500,  // Optional: for price ranges
    'user_id' => 193,
]);

echo "Service created: #{$service['id']}\n";
```

**CLI:**
```bash
# Create service with price range
./bin/iris sdk:call services.create \
  profile_id=9203684 \
  title='Credit Repair Services' \
  description='Professional credit restoration services' \
  price=500 \
  price_max=2500 \
  user_id=193

# Create service with fixed price
./bin/iris sdk:call services.create \
  profile_id=9203684 \
  title='Video Production' \
  description='Professional video editing and production' \
  price=1000 \
  user_id=193
```

#### List Services for a Profile

```php
// Get all services for a profile
$services = $iris->services->list(['profile_id' => 9203684]);

foreach ($services as $service) {
    $priceDisplay = $service['price_max'] 
        ? "\${$service['price']}-\${$service['price_max']}"
        : "\${$service['price']}";
    
    echo "{$service['title']}: {$priceDisplay}\n";
}
```

**CLI:**
```bash
# List services for a profile
./bin/iris sdk:call services.list profile_id=9203684
```

**⚠️ Important:** The `profile_id` filter is critical. Without it, `services.list` returns ALL services across the entire platform. Always specify `profile_id` when querying services for a specific profile.

#### Update Profile

```php
// Update profile fields
$profile = $iris->profiles->update(9203684, [
    'bio' => 'Updated bio text',
    'website_url' => 'https://example.com',
]);
```

#### Update Service

```php
// Update service pricing or details
$service = $iris->services->update(245, [
    'price' => 600,
    'price_max' => 3000,
    'description' => 'Updated service description',
]);
```

### �🔌 Integrations (17+ Services)

Connect your agents to external services with 17+ pre-built integrations. Perfect for users coming from N8N - use our integrations directly or build custom workflows.

#### Available Integrations

| Category | Integrations | Functions |
|----------|--------------|-----------|
| **Google Suite** | Drive, Gmail, Calendar | Search files, send emails, manage events |
| **Communication** | Slack, Discord | Send messages, manage channels |
| **Email Marketing** | Mailjet, Mailchimp, SMTP | Campaigns, transactional emails |
| **Content** | YouTube, YouTube Transcript | Search, analyze, extract transcripts |
| **AI Services** | ElevenLabs, Google Gemini | Voice synthesis, image/video generation |
| **Business** | Servis.ai (15+ functions) | CRM, appointments, case management |
| **Documents** | Case Reviewer, Gamma | AI document review, presentations |

#### Connect an Integration

```php
// Get OAuth URL for user authorization
$oauthUrl = $iris->integrations->getOAuthUrl('google-drive');
// Redirect user to $oauthUrl, they'll be redirected back after auth

// List connected integrations
$connected = $iris->integrations->enabled();

// Test an integration
$result = $iris->integrations->test($integrationId);
echo $result->success ? "Connected!" : "Error: {$result->error}";
```

#### Execute Integration Functions

```php
// Search Google Drive
$files = $iris->integrations->execute('google-drive', 'search_files', [
    'query' => 'Q1 Report',
    'limit' => 10,
]);

// Send an email via Gmail
$iris->integrations->execute('gmail', 'send_email', [
    'to' => 'client@example.com',
    'subject' => 'Your AI Agent is Ready',
    'body' => 'Your custom AI agent has been deployed...',
]);

// Post to Slack
$iris->integrations->execute('slack', 'send_message', [
    'channel' => '#general',
    'message' => 'New lead received: John Smith',
]);

// Get YouTube transcript
$transcript = $iris->integrations->execute('youtube-transcript', 'get_transcript', [
    'video_url' => 'https://youtube.com/watch?v=...',
]);

// Get calendar availability
$slots = $iris->integrations->execute('google-calendar', 'check_availability', [
    'start_date' => '2025-01-01',
    'end_date' => '2025-01-07',
]);
```

**CLI:**
```bash
# List available integrations
./bin/iris sdk:call integrations.types

# Get OAuth URL
./bin/iris sdk:call integrations.getOAuthUrl google-drive

# Execute a function
./bin/iris sdk:call integrations.execute type=google-drive function=search_files params='{"query":"report"}'

# List integration functions
./bin/iris sdk:call integrations.getFunctions gmail
```

#### Integration Metadata & AI Context

```php
// Get all integration metadata (for building UI)
$metadata = $iris->integrations->getMetadata();

// Get AI context (function definitions for agents)
$aiContext = $iris->integrations->getAiContext();
// Agents use this to know which integrations they can call
```

#### MCP (Model Context Protocol) Support

For Claude and other MCP-compatible AI systems:

```php
// List MCP-compatible integrations
$mcpIntegrations = $iris->integrations->mcpIntegrations();

// Get functions for an MCP service
$functions = $iris->integrations->getFunctions('gmail');

// Execute via MCP protocol
$result = $iris->integrations->executeFunction('gmail', 'read_emails', [
    'limit' => 10,
    'unread_only' => true,
]);
```

---

### 🔄 N8N Workflow Compatibility

**For teams using N8N**: IRIS integrations work alongside your existing N8N workflows. You can:

1. **Use IRIS integrations directly** - No need to rebuild, we support the same services
2. **Trigger IRIS agents from N8N** - Call our API endpoints in N8N HTTP nodes
3. **Export data to IRIS** - Push workflow results to IRIS for AI processing

```bash
# Example: N8N HTTP Request node calling IRIS API
POST https://api.heyiris.io/api/chat/start
Authorization: Bearer your-api-key
{
    "query": "Process this lead data",
    "agentId": 11,
    "bloqId": 40
}
```

Your agents live in the cloud at **app.heyiris.io** and can be accessed from any workflow tool.

---

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
FL_API_LOCAL_URL=https://local.raichu.freelabel.net
```

For **production testing**:
```env
IRIS_ENV=production
FL_API_URL=https://apiv2.heyiris.io
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
| `$iris->chat` | `start`, `getStatus`, `execute`, `resume`, `summarize`, `history`, `stats` |
| `$iris->agents` | `list`, `get`, `create`, `update`, `patch`, `delete`, `chat`, `multiStep`, `addMemory`, `togglePublic`, `generateWebhook`, `getFileAttachments`, `addFileAttachments`, `setFileAttachments`, `removeFileAttachment`, `clearFileAttachments`, `uploadAndAttachFiles`, `getUrl`, `getUrls` |
| `$iris->workflows` | `execute`, `getStatus`, `continue`, `completeTask`, `generate`, `generateWithAgents`, `templates`, `importTemplate`, `runs`, `getLogs` |
| `$iris->bloqs` | `list`, `get`, `create`, `update`, `delete`, `overview`, `agents`, `bloqAgents`, `workflows`, `lists`, `items`, `uploadFile`, `files`, `getCustomFieldsConfig`, `updateCustomFieldsConfig`, `addCustomField`, `removeCustomField`, `clearCustomFields`, `share`, `getSharedUsers`, `updateSharePermission`, `unshare`, `getContent`, `addContent`, `removeContent`, `makeItemPublic`, `makeItemPrivate`, `getPublicItem`, `storeChatMessage`, `getChatMessages`, `clearChatMessages` |
| `$iris->leads` | `list`, `get`, `create`, `update`, `delete`, `search`, `addNote`, `activities`, `tasks`, `deliverables`, `invoices`, `aggregation`, `outreach`, `outreachSteps`, `enrich`, `enrichReAct`, `applyEnrichment`, `enrichmentStatus`, `generateResponse`, `recordOutreach`, `parseDescription`, `createFromDescription`, `getAvailableTags`, `getLifecycleStages`, `checkDuplicate`, `bulkCreateFromDescriptions`, `stripePayments` |
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
| `$iris->tools` | `list`, `invoke`, `recruitment`, `scoreCandidates`, `enrichLead` |
| `$iris->articles` | `generate`, `generateFromVideo`, `generateFromTopic`, `generateFromWebpage`, `generateFromRss`, `create` |

## Troubleshooting

### "The POST method is not supported for route..."

**Problem:** Getting this error when trying to create profiles or other resources.

**Cause:** The HTTP Client's routing logic doesn't recognize the endpoint pattern, so it routes to the wrong API.

**Solution:**

1. **Check your endpoint pattern** - The Client routes based on URL patterns:
   - `/profile` (singular) → FL-API ✅
   - `/profiles` (plural) → FL-API ✅
   - `/leads` → FL-API ✅
   - `/agents/*` → IRIS API ✅
   - `/chat/*` → IRIS API ✅

2. **Verify routing in `src/Http/Client.php`**:
   ```php
   // This check must include BOTH singular and plural forms
   if (str_contains($endpoint, '/profile')  // ✅ Both work
       || str_contains($endpoint, '/leads')
       || str_contains($endpoint, '/services')
       || str_contains($endpoint, '/users/')) {
       return $this->config->flApiUrl . '/' . ltrim($endpoint, '/');
   }
   ```

3. **Check your `.env` configuration**:
   ```bash
   # Production
   IRIS_API_URL=https://iris-api.freelabel.net  # For agents/chat/workflows
   FL_API_URL=https://apiv2.heyiris.io          # For leads/profiles/services
   
   # Local
   IRIS_LOCAL_URL=https://local.iris.freelabel.net
   FL_API_LOCAL_URL=https://local.raichu.freelabel.net
   ```

**Key lesson:** Always check for both singular and plural forms of resource names in routing logic. Backend routes may use `/api/v1/profile` (singular) even though SDK resources are named `profiles` (plural).

### Services Returning All Records Instead of Filtered Results

**Problem:** Calling `services.list(profile_id=123)` returns ALL services from the entire platform.

**Cause:** Missing `profile_id` parameter or backend not applying the filter.

**Solution:**

1. **Always specify `profile_id`**:
   ```php
   // ✅ CORRECT - Only returns services for this profile
   $services = $iris->services->list(['profile_id' => 9203684]);
   
   // ❌ WRONG - Returns ALL services (can be thousands)
   $services = $iris->services->list();
   ```

2. **CLI usage**:
   ```bash
   # ✅ CORRECT
   ./bin/iris sdk:call services.list profile_id=9203684
   
   # ❌ WRONG - Returns everything
   ./bin/iris sdk:call services.list
   ```

3. **Backend fix** - Ensure `ServicesController.php` applies filters:
   ```php
   private function searchServices(Request $request) {
       $services = Service::query();
       
       // Must check profile_id FIRST
       $profileId = $request->query('profile_id');
       if ($profileId) {
           $services->where('profile_id', $profileId);
       }
       // ... other filters
   }
   ```

### Wrong API URL Configuration

**Problem:** SDK calls failing or routing to wrong endpoints.

**Symptoms:**
- 404 errors on valid endpoints
- CORS errors
- "Method not supported" on working endpoints

**Solution:** Verify your API URLs in `.env`:

```bash
# ❌ WRONG - Both pointing to same URL
IRIS_API_URL=https://apiv2.heyiris.io
FL_API_URL=https://apiv2.heyiris.io

# ✅ CORRECT - Separate APIs
IRIS_API_URL=https://iris-api.freelabel.net  # Chat, agents, workflows
FL_API_URL=https://apiv2.heyiris.io          # Leads, profiles, services
```

**Quick test:**
```bash
# Test IRIS API (agents, chat)
curl https://iris-api.freelabel.net/api/health

# Test FL-API (leads, profiles)
curl https://apiv2.heyiris.io/api/health

# Both should return: {"status":"ok","database":"connected"}
```

## License

MIT License - see [LICENSE](LICENSE) for details.

## Support

- Documentation: https://docs.iris.ai/sdk/php
- Issues: https://github.com/iris-ai/php-sdk/issues
- Email: support@iris.ai
