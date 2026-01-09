# Lead Management Workflow Guide

Complete workflow documentation for managing leads, deliverables, notes, and tasks using the IRIS SDK.

## Table of Contents

1. [Finding & Analyzing Leads](#finding--analyzing-leads)
2. [Updating Lead Status](#updating-lead-status)
3. [Adding Deliverables](#adding-deliverables)
4. [Managing Notes](#managing-notes)
5. [Managing Tasks](#managing-tasks)
6. [Managing Agent Workflows](#managing-agent-workflows)
7. [Real-World Examples](#real-world-examples)

---

## Finding & Analyzing Leads

### Search Leads

```bash
# Basic search
./bin/iris sdk:call leads.search search="john" bloq_id=40

# Search with filters
./bin/iris sdk:call leads.search \
  bloq_id=40 \
  status=Won,Negotiation \
  include_notes=true \
  sort=updated_at \
  order=desc

# Search specific lead types
./bin/iris sdk:call leads.search \
  bloq_id=40 \
  lead_type=client \
  per_page=20
```

```php
// Search leads
$leads = $iris->leads->search([
    'bloq_id' => 40,
    'status' => 'Won,Negotiation',
    'search' => 'john',
    'include_notes' => true,
    'per_page' => 20,
]);

foreach ($leads as $lead) {
    echo "{$lead->nickname} - {$lead->status}\n";
}
```

### Get Lead Aggregation Statistics

```bash
# Get overall statistics
./bin/iris sdk:call leads.aggregation.statistics

# Example output:
# Total leads: 499
# Won: 245
# Negotiation: 78
# Incomplete tasks: 8
```

```php
$stats = $iris->leads->aggregation()->statistics();
echo "Total leads: {$stats['total_leads']}\n";
echo "Won deals: {$stats['by_status']['Won']}\n";
echo "Incomplete tasks: {$stats['incomplete_tasks']}\n";
```

### Get Priority Leads

```bash
# Get top 10 priority leads
./bin/iris sdk:call leads.aggregation.list \
  sort=priority \
  order=desc \
  per_page=10

# Get leads with incomplete tasks
./bin/iris sdk:call leads.aggregation.list \
  has_incomplete_tasks=1 \
  sort=priority
```

```php
// Get priority leads
$priorityLeads = $iris->leads->aggregation()->list([
    'sort' => 'priority',
    'order' => 'desc',
    'per_page' => 10,
]);

foreach ($priorityLeads as $lead) {
    echo "Priority {$lead['priority']}: {$lead['nickname']}\n";
    echo "  Tasks: {$lead['tasks_count']}\n";
    echo "  Notes: {$lead['note_count']}\n";
}
```

### Analyze Priority Scores

Priority scoring formula:
- **Won**: 80 points (base)
- **Negotiation**: 70 points (base)
- **Proposal**: 60 points (base)
- **+10 points**: Recent activity (last 7 days)
- **+5 points**: Has incomplete tasks
- **+3 points**: Has notes
- **+2 points**: Recently updated

Example priority analysis:
```bash
# Get priorities with JSON output for analysis
./bin/iris sdk:call leads.aggregation.list \
  sort=priority \
  order=desc \
  per_page=10 \
  --json | jq '.data[] | {id, nickname, status, priority, tasks: .tasks_count, notes: .note_count}'
```

---

## Updating Lead Status

### Update via CLI

```bash
# Update lead status
./bin/iris sdk:call leads.update 412 status=Won

# Update multiple fields
./bin/iris sdk:call leads.update 80 \
  status="On Hold" \
  lead_type=prospect
```

### Update via PHP SDK

```php
// Update lead
$lead = $iris->leads->update(412, [
    'status' => 'Won',
    'lead_type' => 'client',
]);

echo "Updated {$lead->nickname} to {$lead->status}\n";
```

### Update via Direct API (Fallback)

```bash
# When SDK has type issues, use direct API
curl -X PUT "https://apiv2.heyiris.io/api/v1/leads/80" \
  -H "Authorization: Bearer $IRIS_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"status": "On Hold"}'
```

### Status Options

- `New` - Just entered the pipeline
- `Contacted` - Initial outreach made
- `Qualified` - Verified as potential client
- `Proposal` - Proposal sent
- `Negotiation` - Discussing terms
- `Won` - Deal closed successfully
- `Lost` - Deal lost
- `On Hold` - Paused temporarily

---

## Adding Deliverables

Deliverables represent work products delivered to clients (AI agents, reports, websites, etc.).

### Create Link Deliverable

```bash
# Add AI agent deliverable
./bin/iris sdk:call leads.deliverables.create 53 \
  type=link \
  title="AI Recruiter Assistant Agent" \
  external_url="https://app.heyiris.io/agent/simple/358?bloq=208" \
  description="AI agent for analyzing resumes, scoring candidates, and creating LinkedIn search queries. Built for recruiter workflow optimization." \
  user_id=193

# Add deployed website
./bin/iris sdk:call leads.deliverables.create 24 \
  type=link \
  title="Marketing Website" \
  external_url="https://client-site.com" \
  user_id=193
```

```php
// Create link deliverable
$deliverable = $iris->leads->deliverables(53)->create([
    'type' => 'link',
    'title' => 'AI Recruiter Assistant Agent',
    'external_url' => 'https://app.heyiris.io/agent/simple/358?bloq=208',
    'description' => 'AI agent for analyzing resumes, scoring candidates, and creating LinkedIn search queries.',
    'user_id' => 193,
]);

echo "Created deliverable #{$deliverable['id']}\n";
```

### Upload File Deliverable

```bash
# Upload PDF report
./bin/iris sdk:call leads.deliverables.uploadFile 24 \
  file=/path/to/report.pdf \
  title="Q4 Marketing Report" \
  user_id=193

# Upload with options
./bin/iris sdk:call leads.deliverables.uploadFile 53 \
  file=/path/to/contract.pdf \
  title="Service Agreement" \
  description="Signed contract for recruitment services" \
  user_id=193
```

```php
// Upload file
$deliverable = $iris->leads->deliverables(24)->uploadFile('/path/to/report.pdf', [
    'title' => 'Q4 Marketing Report',
    'user_id' => 193,
]);
```

### List Deliverables

```bash
# List all deliverables for a lead
./bin/iris sdk:call leads.deliverables.list 53
```

```php
// List deliverables
$deliverables = $iris->leads->deliverables(53)->list();

foreach ($deliverables as $deliverable) {
    echo "{$deliverable['title']} ({$deliverable['type']})\n";
    echo "  URL: {$deliverable['url']}\n";
}
```

### Update Deliverable

```bash
# Update title/description
./bin/iris sdk:call leads.deliverables.update 53 335 \
  title="Enhanced AI Recruiter" \
  description="Updated with LinkedIn integration"
```

```php
$iris->leads->deliverables(53)->update(335, [
    'title' => 'Enhanced AI Recruiter',
    'description' => 'Updated with LinkedIn integration',
]);
```

### Delete Deliverable

```bash
./bin/iris sdk:call leads.deliverables.delete 53 335
```

```php
$iris->leads->deliverables(53)->delete(335);
```

---

## Managing Notes

Notes document conversations, decisions, and important context about leads.

### Add Note

```bash
# Add note to lead
./bin/iris sdk:call leads.addNote 65 "Client wants Texas mini series featuring local country artists. Discussed budget of $50k-75k for 6-episode series."

# Add note with context
./bin/iris sdk:call leads.addNote 53 "Gniice asked for recruiter tools to help analyze resumes, score candidates, and create search queries for LinkedIn. We built out the AI Recruiter Assistant Agent for this workflow. Agent available at: https://app.heyiris.io/agent/simple/358?bloq=208"
```

```php
// Add note
$note = $iris->leads->addNote(65, 
    "Client wants Texas mini series featuring local country artists. Discussed budget of $50k-75k for 6-episode series."
);

echo "Added note #{$note['id']}\n";
```

### Add Note via Direct API

```bash
# When CLI has parameter issues, use direct API
curl -X POST "https://apiv2.heyiris.io/api/v1/leads/65/notes" \
  -H "Authorization: Bearer $IRIS_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"message": "Your note content here"}'
```

### View Notes in Search

```bash
# Search with notes included
./bin/iris sdk:call leads.search \
  search=nsgbillz \
  bloq_id=40 \
  include_notes=true
```

### Delete Note

```bash
# Delete specific note
./bin/iris sdk:call leads.deleteNote 65 391
```

```php
// Delete note
$iris->leads->deleteNote(65, 391);
```

**Note:** The delete note endpoint uses webhook authentication and requires production deployment of the backend route.

---

## Managing Tasks

Tasks track action items and deliverables for leads.

### Create Task

```bash
# Create simple task
./bin/iris sdk:call leads.tasks.create 412 \
  title="Setup delivery meeting" \
  status=incomplete

# Create task with details
./bin/iris sdk:call leads.tasks.create 24 \
  title="Send Q4 report" \
  description="Include metrics and recommendations" \
  status=incomplete \
  priority=high \
  due_date="2025-12-30"
```

```php
// Create task
$task = $iris->leads->tasks(412)->create([
    'title' => 'Setup delivery meeting',
    'status' => 'incomplete',
    'priority' => 'medium',
]);

echo "Created task #{$task['id']}\n";
```

### List Tasks

```bash
# List all tasks for a lead
./bin/iris sdk:call leads.tasks.all 412

# Get tasks from aggregation
./bin/iris sdk:call leads.aggregation.get 412
```

```php
// List tasks
$tasks = $iris->leads->tasks(412)->all();

foreach ($tasks as $task) {
    echo "{$task['title']} - {$task['status']}\n";
}
```

### Update Task

```bash
# Mark task complete
./bin/iris sdk:call leads.tasks.update 412 11 status=complete

# Update multiple fields
./bin/iris sdk:call leads.tasks.update 412 11 \
  status=complete \
  notes="Meeting scheduled for Dec 30th"
```

```php
$iris->leads->tasks(412)->update(11, [
    'status' => 'complete',
    'notes' => 'Meeting scheduled for Dec 30th',
]);
```

### Delete Task

```bash
./bin/iris sdk:call leads.tasks.delete 412 11
```

```php
$iris->leads->tasks(412)->delete(11);
```

---

## Checking Stripe Payments

Verify payment status and view complete Stripe payment history for leads.

### Quick Payment Summary

```bash
# Quick summary (recommended for quick checks)
./bin/iris payments 110 --summary

# Example output:
# Payment Summary - Dr. John F. Ayala
# ====================================
# 
# Customer:    John Ayala
# Email:       jayala@aec-hq.com
# Stripe ID:   cus_TcnOtQuBmG7vkE
# 
# ✅ Status: PAID
# 
# Invoices:    1 total, 1 paid, 0 pending
# Payments:    1 successful
# Total Paid:  $541.25
```

### Full Payment Details

```bash
# View complete payment history
./bin/iris payments 110

# Includes:
# - Customer information
# - All invoices (with status, amounts, dates)
# - Payment transactions (card details, amounts)
# - Checkout sessions
# - Financial summary
# - PDF download links
```

### Via SDK Method

```bash
# Using the SDK proxy (returns raw data)
./bin/iris sdk:call leads.stripePayments 110

# JSON output for automation
./bin/iris sdk:call leads.stripePayments 110 --json
```

### PHP SDK Usage

```php
// Get payment history
$payments = $iris->leads->stripePayments(110);

// Check if customer exists
if ($payments['has_stripe_customer']) {
    echo "Customer: {$payments['customer']['name']}\n";
    echo "Email: {$payments['customer']['email']}\n";
    echo "Total Paid: \${$payments['total_paid']}\n";
}

// List invoices
foreach ($payments['invoices'] as $invoice) {
    echo "Invoice #{$invoice['number']}: ";
    echo "\${$invoice['amount_paid']} - {$invoice['status']}\n";
    
    if ($invoice['status'] === 'paid') {
        echo "  Paid at: {$invoice['paid_at']}\n";
        echo "  PDF: {$invoice['invoice_pdf']}\n";
    }
}

// Check payments
foreach ($payments['payments'] as $payment) {
    $card = $payment['payment_method'];
    echo "Payment: \${$payment['amount']} - {$payment['status']}\n";
    echo "  Card: {$card['brand']} ending in {$card['last4']}\n";
    echo "  Date: {$payment['created']}\n";
}

// Summary stats
$summary = $payments['summary'];
echo "\nSummary:\n";
echo "  Total Invoices: {$summary['total_invoices']}\n";
echo "  Paid: {$summary['paid_invoices']}\n";
echo "  Pending: {$summary['pending_invoices']}\n";
echo "  Total Revenue: \${$payments['total_paid']}\n";
```

### Payment Status Indicators

The `payments` command uses visual indicators:

- **✅ PAID** - Invoice fully paid
- **⏳ PENDING** - Payment in progress
- **📝 DRAFT** - Invoice not sent yet
- **❌ VOID** - Invoice cancelled
- **✅ SUCCEEDED** - Payment transaction successful

### Use Cases

```bash
# After client says "I paid the invoice"
./bin/iris payments 110 --summary

# Verify payment before marking tasks complete
./bin/iris payments 110 --summary && ./bin/iris sdk:call leads.tasks.update 110 13 is_completed=true

# Get JSON data for automated workflows
./bin/iris payments 110 --json | jq '.summary.total_paid'

# Check multiple leads
for lead_id in 110 24 53; do
  echo "Checking lead $lead_id..."
  ./bin/iris payments $lead_id --summary
  echo ""
done
```

### Troubleshooting

**No Stripe customer found:**
- Lead email may not match Stripe customer email
- Customer may not exist in your Stripe account
- Check lead email is correct: `./bin/iris sdk:call leads.get <lead_id>`

**Payment shows but invoice doesn't:**
- Payment may be for a different invoice
- Use Stripe dashboard to investigate: https://dashboard.stripe.com

**Total paid is in cents:**
- SDK automatically converts to dollars (e.g., 54125 cents → $541.25)
- If you see raw API response, divide by 100

---

## Managing Agent Workflows

Agents can execute multi-step workflows to automate complex tasks. Workflows must be attached to agents before they can be used. This is managed through the agent-workflow relational system.

### Discovering Available Workflows

Before attaching workflows to agents, you can discover what workflows are available using the workflow discovery API.

#### List All Callable Workflows

```bash
# List all available workflows
./bin/iris sdk:call agents.listAvailableWorkflows

# Filter by category
./bin/iris sdk:call agents.listAvailableWorkflows category=Recruitment

# Filter by execution mode
./bin/iris sdk:call agents.listAvailableWorkflows execution_mode=agentic

# Search by keyword
./bin/iris sdk:call agents.listAvailableWorkflows search=newsletter
```

```php
// List all available workflows
$workflows = $iris->agents->listAvailableWorkflows();

foreach ($workflows as $workflow) {
    echo "{$workflow['name']} (ID: {$workflow['id']})\n";
    echo "  Callable: {$workflow['callable_name']}\n";
    echo "  Category: {$workflow['category']}\n";
    echo "  Mode: {$workflow['execution_mode']}\n";
    echo "  Agentic: " . ($workflow['is_agentic'] ? 'Yes' : 'No') . "\n";
}

// Filter workflows
$recruitmentWorkflows = $iris->agents->listAvailableWorkflows([
    'category' => 'Recruitment',
    'execution_mode' => 'agentic'
]);
```

#### Find Workflow by Name

```bash
# Find workflow by callable name (exact match)
./bin/iris sdk:call agents.findWorkflowByName find_candidates

# Example output:
# {
#   "id": 8,
#   "name": "LinkedIn Candidate Finder",
#   "callable_name": "find_candidates",
#   "callable_description": "Search LinkedIn for qualified candidates",
#   "category": "Recruitment",
#   "execution_mode": "agentic",
#   "is_agentic": true,
#   "default_model": "gpt-4o"
# }
```

```php
// Find workflow by name
$workflow = $iris->agents->findWorkflowByName('find_candidates');

if ($workflow) {
    echo "Found: {$workflow['name']} (ID: {$workflow['id']})\n";
    echo "Description: {$workflow['callable_description']}\n";
    
    // Now attach it to an agent
    $iris->agents->attachWorkflow(164, $workflow['id'], [
        'priority' => 10,
        'is_enabled' => true
    ]);
} else {
    echo "Workflow not found\n";
}
```

#### Get Workflow Details

```bash
# Get details by workflow ID
./bin/iris sdk:call agents.getWorkflowDetails 8

# Get details by callable name
./bin/iris sdk:call agents.getWorkflowDetails find_candidates

# Example output shows full workflow configuration including:
# - Input/output schemas
# - Dependencies
# - Agent configuration (for agentic workflows)
# - Model requirements
# - Features and benefits
```

```php
// Get detailed workflow information
$details = $iris->agents->getWorkflowDetails('find_candidates');

echo "Workflow: {$details['name']}\n";
echo "Description: {$details['callable_description']}\n";
echo "Model: {$details['default_model']}\n";
echo "Execution: {$details['execution_mode']}\n";

if ($details['is_agentic']) {
    echo "\nAgentic Configuration:\n";
    $config = $details['agent_config'];
    echo "  Goal: {$config['goal']}\n";
    echo "  Max Iterations: {$details['max_iterations']}\n";
}

// Check input schema
if (!empty($details['input_schema'])) {
    echo "\nRequired Inputs:\n";
    foreach ($details['input_schema']['properties'] as $field => $schema) {
        $required = in_array($field, $details['input_schema']['required'] ?? []);
        echo "  - {$field}: {$schema['description']}" . ($required ? ' (required)' : '') . "\n";
    }
}
```

#### Complete Discovery-to-Attachment Workflow

Here's a real-world example of discovering and attaching a workflow:

```bash
# Step 1: Search for recruiting workflows
./bin/iris sdk:call agents.listAvailableWorkflows category=Recruitment

# Step 2: Get details about a specific workflow
./bin/iris sdk:call agents.getWorkflowDetails find_candidates

# Step 3: Attach it to your agent
./bin/iris sdk:call agents.attachWorkflow 164 8 \
  priority=10 \
  config='{"max_results":100,"include_skills":true}'

# Step 4: Verify attachment
./bin/iris sdk:call agents.listWorkflows 164
```

```php
// Complete workflow: Discover → Attach → Verify

// 1. Search for content generation workflows
$workflows = $iris->agents->listAvailableWorkflows([
    'search' => 'newsletter'
]);

// 2. Find the specific workflow
$newsletter = $iris->agents->findWorkflowByName('generate_newsletter');

if (!$newsletter) {
    throw new Exception('Newsletter workflow not found');
}

// 3. Get detailed information
$details = $iris->agents->getWorkflowDetails($newsletter['id']);

echo "Attaching: {$details['name']}\n";
echo "Model required: {$details['default_model']}\n";

// 4. Attach to agent with appropriate configuration
$result = $iris->agents->attachWorkflow(356, $newsletter['id'], [
    'priority' => 20,
    'is_enabled' => true,
    'config' => [
        'tone' => 'professional',
        'length' => 'medium',
        'include_images' => true
    ]
]);

echo "✓ Workflow attached successfully\n";

// 5. Verify attachment
$attachedWorkflows = $iris->agents->listWorkflows(356);
echo "Agent now has " . count($attachedWorkflows) . " workflow(s) attached\n";
```

### Understanding Workflows

Workflows are reusable templates that define multi-step processes:
- **find_candidates** (ID: 8) - LinkedIn candidate search and analysis
- **generate_newsletter** (ID: 1) - Newsletter research and writing
- **generate_article** - Article generation from sources
- And more custom workflows...

Each workflow attachment has settings:
- **is_enabled**: Whether the workflow is active (true/false)
- **priority**: Execution priority (0-100, higher = more priority)
- **config**: Custom JSON configuration for the workflow

### List Agent Workflows

```bash
# List all workflows attached to an agent
./bin/iris sdk:call agents.listWorkflows 164

# Example output:
# [
#   {
#     "id": 8,
#     "name": "Find Candidates",
#     "callable_name": "find_candidates",
#     "is_enabled": true,
#     "priority": 10,
#     "config": {"max_results": 50},
#     "enabled_at": "2026-01-08 12:00:00"
#   }
# ]
```

```php
// List workflows
$workflows = $iris->agents->listWorkflows(164);

foreach ($workflows as $workflow) {
    echo "{$workflow['name']} - Priority: {$workflow['priority']}\n";
    echo "  Enabled: " . ($workflow['is_enabled'] ? 'Yes' : 'No') . "\n";
    echo "  Callable: {$workflow['callable_name']}\n";
}
```

### Attach Workflow to Agent

```bash
# Attach workflow with default settings
./bin/iris sdk:call agents.attachWorkflow 164 8

# Attach workflow with custom settings
./bin/iris sdk:call agents.attachWorkflow 164 8 \
  priority=10 \
  is_enabled=true \
  config='{"max_results":50,"include_linkedin":true}'

# Attach newsletter workflow
./bin/iris sdk:call agents.attachWorkflow 356 1 \
  priority=20 \
  config='{"tone":"professional","length":"medium"}'
```

```php
// Attach workflow
$result = $iris->agents->attachWorkflow(164, 8, [
    'priority' => 10,
    'is_enabled' => true,
    'config' => [
        'max_results' => 50,
        'include_linkedin' => true,
    ],
]);

echo "Attached: {$result['workflow_name']} ({$result['callable_name']})\n";
```

### Update Workflow Settings

```bash
# Update priority
./bin/iris sdk:call agents.updateWorkflowSettings 164 8 priority=20

# Disable workflow (keeps it attached but inactive)
./bin/iris sdk:call agents.updateWorkflowSettings 164 8 is_enabled=false

# Update configuration
./bin/iris sdk:call agents.updateWorkflowSettings 164 8 \
  config='{"max_results":100,"advanced_search":true}'

# Update multiple settings at once
./bin/iris sdk:call agents.updateWorkflowSettings 164 8 \
  priority=25 \
  is_enabled=true \
  config='{"max_results":75}'
```

```php
// Update priority
$iris->agents->updateWorkflowSettings(164, 8, [
    'priority' => 20,
]);

// Disable workflow
$iris->agents->updateWorkflowSettings(164, 8, [
    'is_enabled' => false,
]);

// Update configuration
$iris->agents->updateWorkflowSettings(164, 8, [
    'config' => [
        'max_results' => 100,
        'advanced_search' => true,
    ],
]);
```

### Detach Workflow from Agent

```bash
# Remove workflow from agent
./bin/iris sdk:call agents.detachWorkflow 164 8

# This completely removes the workflow attachment
# The agent can no longer execute this workflow
```

```php
// Detach workflow
$iris->agents->detachWorkflow(164, 8);
echo "Workflow detached successfully\n";
```

### Sync Multiple Workflows

Replace all workflow attachments at once:

```bash
# Sync workflows (replaces all existing attachments)
./bin/iris sdk:call agents.syncWorkflows 164 \
  workflows='[
    {"workflow_id":8,"priority":10,"is_enabled":true,"config":{"max_results":50}},
    {"workflow_id":1,"priority":5,"is_enabled":true}
  ]'

# This will:
# 1. Detach any workflows not in the list
# 2. Attach new workflows
# 3. Update existing workflows
```

```php
// Sync workflows
$result = $iris->agents->syncWorkflows(164, [
    [
        'workflow_id' => 8,
        'priority' => 10,
        'is_enabled' => true,
        'config' => ['max_results' => 50],
    ],
    [
        'workflow_id' => 1,
        'priority' => 5,
        'is_enabled' => true,
    ],
]);

echo "Synced {$result['synced_count']} workflows\n";
```

### Legacy Script (Backward Compatibility)

The older `agent-enable-workflow` script still works:

```bash
# Enable workflow by ID
./bin/agent-enable-workflow 164 8

# Enable workflow by name (limited mapping)
./bin/agent-enable-workflow 164 find_candidates
```

**Note:** This script uses the new API endpoints internally but has limited workflow name mappings. Use `sdk:call` for full control.

### Common Workflow IDs

For reference, here are common workflow template IDs:

| ID | Callable Name | Description |
|----|---------------|-------------|
| 1  | generate_newsletter | Multi-modal newsletter research and writing |
| 8  | find_candidates | LinkedIn candidate search with Boolean queries |
| ... | ... | (Check your database for more) |

To find workflow IDs, query the `workflow_templates` table or use the API.

---

## Real-World Examples

### Example 1: Complete Client Onboarding - Rodney Mayo

**Scenario:** Rodney Mayo requested an AI agent for his newsletter workflow. Track the entire delivery process.

```bash
# 1. Find the lead
./bin/iris sdk:call leads.search search="Rodney Mayo" bloq_id=40

# Lead ID: 24

# 2. Update status to Negotiation
./bin/iris sdk:call leads.update 24 status=Negotiation

# 3. Create the agent (via agents.create)
# Agent ID: 356

# 4. Add deliverable for the agent
./bin/iris sdk:call leads.deliverables.create 24 \
  type=link \
  title="Newsletter AI Agent" \
  external_url="https://app.heyiris.io/agent/simple/356" \
  description="Trained AI agent for newsletter content generation and curation" \
  user_id=193

# 5. Add note documenting requirements
./bin/iris sdk:call leads.addNote 24 \
  "Rodney requested AI agent for newsletter workflow. Built custom agent trained on his writing style. Agent can generate content ideas, write drafts, and curate relevant links."

# 6. Create follow-up task
./bin/iris sdk:call leads.tasks.create 24 \
  title="Schedule training session" \
  description="Walk Rodney through agent usage" \
  status=incomplete

# 7. Update status to Won after delivery
./bin/iris sdk:call leads.update 24 status=Won

# 8. Verify priority increased
./bin/iris sdk:call leads.aggregation.list sort=priority order=desc per_page=5
# Result: Rodney Mayo now shows priority 115 (Won + recent activity + tasks + notes)
```

### Example 2: Recruiter Tools for @gniice_

**Scenario:** @gniice_ is an event coordinator who needed recruiter tools. Built AI agent and documented the workflow.

```bash
# 1. Search for the lead
./bin/iris sdk:call leads.search search="gniice" bloq_id=40
# Lead ID: 53

# 2. Already Won status (event partnership)

# 3. Create recruiter AI agent
# Agent ID: 358

# 4. Add deliverable
./bin/iris sdk:call leads.deliverables.create 53 \
  type=link \
  title="AI Recruiter Assistant Agent" \
  external_url="https://app.heyiris.io/agent/simple/358?bloq=208" \
  description="AI agent for analyzing resumes, scoring candidates, and creating LinkedIn search queries. Built for recruiter workflow optimization." \
  user_id=193

# Deliverable ID: 335

# 5. Document the request and solution
curl -X POST "https://apiv2.heyiris.io/api/v1/leads/53/notes" \
  -H "Authorization: Bearer $IRIS_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"message": "Gniice asked for recruiter tools to help analyze resumes, score candidates, and create search queries for LinkedIn. We built out the AI Recruiter Assistant Agent for this workflow. Agent available at: https://app.heyiris.io/agent/simple/358?bloq=208"}'

# Note ID: 393

# 6. Check priority
./bin/iris sdk:call leads.aggregation.list sort=priority order=desc --json | grep -A 5 "gniice"
# Priority: 110 (Won + deliverable + notes + recent activity)
```

### Example 3: Texas Mini Series for @nsgbillz

**Scenario:** Client wants to produce a Texas mini series. Track conversations and requirements.

```bash
# 1. Find lead
./bin/iris sdk:call leads.search search="nsgbillz" bloq_id=40
# Lead ID: 65

# 2. Already Won status

# 3. Add detailed note about requirements
curl -X POST "https://apiv2.heyiris.io/api/v1/leads/65/notes" \
  -H "Authorization: Bearer $IRIS_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"message": "Client wants Texas mini series featuring local country artists. Discussed budget of $50k-75k for 6-episode series. Target release: Q2 2026. Interested in hybrid documentary/performance format."}'

# Note ID: 392

# 4. Create production tasks
./bin/iris sdk:call leads.tasks.create 65 \
  title="Artist research and outreach" \
  description="Identify 10-15 Texas country artists" \
  status=incomplete \
  priority=high

./bin/iris sdk:call leads.tasks.create 65 \
  title="Location scouting" \
  description="Find authentic Texas venues" \
  status=incomplete

./bin/iris sdk:call leads.tasks.create 65 \
  title="Draft production schedule" \
  status=incomplete

# 5. Track with aggregation
./bin/iris sdk:call leads.aggregation.get 65
# Shows: 21 notes, 3 tasks, high priority
```

### Example 4: Priority Management - Putting Leads On Hold

**Scenario:** Focus team resources on high-priority Won clients by putting lower-priority negotiation leads on hold.

```bash
# 1. Analyze current priorities
./bin/iris sdk:call leads.aggregation.list \
  sort=priority \
  order=desc \
  per_page=10 \
  --json

# Results show:
# - Top priorities: 90-115 (Won clients)
# - Lower priorities: 70 (Negotiation stage)

# 2. Update Robert Kerr to On Hold (Priority 70, Negotiation)
curl -X PUT "https://apiv2.heyiris.io/api/v1/leads/80" \
  -H "Authorization: Bearer $IRIS_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"status": "On Hold"}'

# 3. Update Maxx Baig to On Hold (Priority 70, Negotiation)
curl -X PUT "https://apiv2.heyiris.io/api/v1/leads/76" \
  -H "Authorization: Bearer $IRIS_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"status": "On Hold"}'

# 4. Verify changes
./bin/iris sdk:call leads.search search="Robert Kerr" bloq_id=40
./bin/iris sdk:call leads.search search="Maxx Baig" bloq_id=40

# Both now show "On Hold" status
```

### Example 5: Delivery Meeting Setup for Tha Juan

**Scenario:** Won client needs delivery meeting scheduled.

```bash
# 1. Find lead
./bin/iris sdk:call leads.search search="Tha Juan" bloq_id=40
# Lead ID: 412

# 2. Update to Won status
./bin/iris sdk:call leads.update 412 status=Won

# 3. Create delivery meeting task
./bin/iris sdk:call leads.tasks.create 412 \
  title="Setup delivery meeting" \
  description="Schedule meeting to review deliverables and get feedback" \
  status=incomplete \
  priority=high

# Task ID: 11

# 4. Check lead priority
./bin/iris sdk:call leads.aggregation.get 412
# Priority: 90 (Won + task + recent activity)
```

### Example 6: AI Recruiter Agent with Workflow Automation for @gniice_

**Scenario:** Built a recruiter AI agent with automated workflow for resume analysis and candidate search. Configure the agent with the find_candidates workflow.

```bash
# 1. Create the agent (already done)
# Agent ID: 358

# 2. Attach the find_candidates workflow
./bin/iris sdk:call agents.attachWorkflow 358 8 \
  priority=10 \
  is_enabled=true \
  config='{"max_results":50,"platforms":["linkedin","github"],"experience_filter":"mid,senior"}'

# 3. Verify workflow is attached
./bin/iris sdk:call agents.listWorkflows 358

# Output shows:
# {
#   "id": 8,
#   "name": "Find Candidates",
#   "callable_name": "find_candidates",
#   "is_enabled": true,
#   "priority": 10,
#   "config": {
#     "max_results": 50,
#     "platforms": ["linkedin", "github"],
#     "experience_filter": "mid,senior"
#   }
# }

# 4. Add deliverable for the lead
./bin/iris sdk:call leads.deliverables.create 53 \
  type=link \
  title="AI Recruiter Assistant Agent (with find_candidates workflow)" \
  external_url="https://app.heyiris.io/agent/simple/358?bloq=208" \
  description="AI agent with automated candidate search workflow. Can analyze resumes, score candidates, and generate LinkedIn Boolean queries. Configured to search LinkedIn and GitHub for mid-senior level candidates." \
  user_id=193

# 5. Document the workflow configuration
curl -X POST "https://apiv2.heyiris.io/api/v1/leads/53/notes" \
  -H "Authorization: Bearer $IRIS_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"message": "Configured recruiter agent with find_candidates workflow. Agent can now automatically generate Boolean search queries, extract candidate profiles from LinkedIn, and score candidates against job requirements. Workflow settings: max 50 results, searches LinkedIn + GitHub, filters for mid-senior experience levels."}'

# 6. Test the workflow by chatting with the agent
./bin/iris chat 358 "Find senior software engineers with Python and AWS experience in Austin, TX" --bloq=208

# The agent will automatically use the find_candidates workflow
```

### Example 7: Newsletter Agent with Multi-Modal Research Workflow

**Scenario:** Configure an agent to generate newsletters using the multi-modal research workflow.

```bash
# 1. Create newsletter agent
# Agent ID: 356

# 2. Attach the generate_newsletter workflow
./bin/iris sdk:call agents.attachWorkflow 356 1 \
  priority=20 \
  is_enabled=true \
  config='{"tone":"professional","newsletter_length":"standard","audience":"general audience"}'

# 3. List workflows to verify
./bin/iris sdk:call agents.listWorkflows 356

# 4. Update workflow settings if needed
./bin/iris sdk:call agents.updateWorkflowSettings 356 1 \
  config='{"tone":"casual","newsletter_length":"brief","audience":"tech professionals"}'

# 5. Test the workflow
./bin/iris chat 356 "Generate a newsletter about AI trends in 2026. Include these videos: https://www.youtube.com/watch?v=abc123" --bloq=40

# The agent will:
# - Extract video transcripts
# - Research the topic
# - Generate 3 outline options
# - Wait for user selection (HITL)
# - Write the full newsletter
```

### Example 8: Managing Multiple Workflows on One Agent

**Scenario:** Create a versatile agent that can handle both recruitment and content generation.

```bash
# 1. Create multi-purpose agent
# Agent ID: 400

# 2. Attach multiple workflows
./bin/iris sdk:call agents.attachWorkflow 400 8 \
  priority=10 \
  config='{"max_results":50}'

./bin/iris sdk:call agents.attachWorkflow 400 1 \
  priority=15 \
  config='{"tone":"professional"}'

# 3. List all workflows
./bin/iris sdk:call agents.listWorkflows 400

# Output shows both workflows:
# [
#   {
#     "id": 1,
#     "name": "Generate Newsletter",
#     "priority": 15,
#     "is_enabled": true
#   },
#   {
#     "id": 8,
#     "name": "Find Candidates",
#     "priority": 10,
#     "is_enabled": true
#   }
# ]

# 4. Temporarily disable recruitment workflow
./bin/iris sdk:call agents.updateWorkflowSettings 400 8 is_enabled=false

# 5. Re-enable later
./bin/iris sdk:call agents.updateWorkflowSettings 400 8 is_enabled=true

# 6. Replace all workflows at once (sync)
./bin/iris sdk:call agents.syncWorkflows 400 \
  workflows='[
    {"workflow_id":1,"priority":20,"is_enabled":true},
    {"workflow_id":8,"priority":5,"is_enabled":false}
  ]'
```

---

## Workflow Best Practices

### 1. **Always Search First**
Before updating, search to confirm the lead ID and current state:
```bash
./bin/iris sdk:call leads.search search="client name" bloq_id=40
```

### 2. **Use Aggregation for Context**
Get full context before making changes:
```bash
./bin/iris sdk:call leads.aggregation.get <lead_id>
```

### 3. **Document Everything with Notes**
Add notes for important conversations and decisions:
```bash
./bin/iris sdk:call leads.addNote <lead_id> "Detailed note about conversation..."
```

### 4. **Track Deliverables as You Create Them**
Immediately add deliverable when work is completed:
```bash
./bin/iris sdk:call leads.deliverables.create <lead_id> type=link title="..." external_url="..." user_id=193
```

### 5. **Create Tasks for Action Items**
Convert verbal agreements to trackable tasks:
```bash
./bin/iris sdk:call leads.tasks.create <lead_id> title="Action item" status=incomplete
```

### 6. **Monitor Priority Regularly**
Check top priorities weekly to focus efforts:
```bash
./bin/iris sdk:call leads.aggregation.list sort=priority order=desc per_page=20
```

### 7. **Configure Agent Workflows for Automation**
Attach workflows to agents to enable automated multi-step processes:
```bash
# List available workflows
./bin/iris sdk:call agents.listWorkflows <agent_id>

# Attach workflow with config
./bin/iris sdk:call agents.attachWorkflow <agent_id> <workflow_id> priority=10 config='{"key":"value"}'

# Update workflow settings
./bin/iris sdk:call agents.updateWorkflowSettings <agent_id> <workflow_id> is_enabled=true
```

### 8. **Document Workflow Configurations**
When adding agent deliverables, document which workflows are enabled:
```bash
./bin/iris sdk:call leads.addNote <lead_id> "Agent configured with find_candidates workflow (priority: 10, max_results: 50)"
```

### 9. **Use Direct API as Fallback**
When SDK has type issues, use curl:
```bash
curl -X PUT "https://apiv2.heyiris.io/api/v1/leads/<lead_id>" \
  -H "Authorization: Bearer $IRIS_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"status": "Won"}'
```

---

## Colorful CLI Output

The SDK provides beautiful, colorful output with emojis:

- 🔑 **ID** - Cyan
- 👤 **Name** - Bright blue
- 📊 **Status** - Color-coded (Won=green ✓, Negotiation=yellow ⚡)
- ✅ **Tasks** - Yellow
- 📝 **Notes** - Magenta
- 🔗 **URLs** - Underlined
- 📅 **Dates** - Gray

When output has >10 columns, compact mode automatically activates showing only the most relevant fields.

---

## Troubleshooting

### Authentication Errors
```bash
# Verify credentials
echo $IRIS_API_KEY
echo $IRIS_USER_ID

# Re-export
export IRIS_API_KEY=your_token_here
export IRIS_USER_ID=193
```

### Lead Class Type Error (Notes Field)
When encountering "Lead class type error: notes expects string but receives array":
```bash
# Use direct API call instead
curl -X PUT "https://apiv2.heyiris.io/api/v1/leads/<lead_id>" \
  -H "Authorization: Bearer $IRIS_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"status": "Won"}'
```

### addNote Parameter Issues
The CLI may have issues with string parameters:
```bash
# Use direct API instead
curl -X POST "https://apiv2.heyiris.io/api/v1/leads/<lead_id>/notes" \
  -H "Authorization: Bearer $IRIS_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"message": "Your note content"}'
```

---

## Outreach & Email

For AI-powered email generation and outreach management, see the dedicated guide:

**[OUTREACH_CLI_GUIDE.md](OUTREACH_CLI_GUIDE.md)**

Quick examples:

```bash
# Generate AI email draft
./bin/iris sdk:call leads.outreach 123 generateEmail \
  prompt="Follow up on our meeting about their AI needs" \
  tone=professional

# Send composed email
./bin/iris sdk:call leads.outreach 123 sendEmail \
  to_email="john@example.com" \
  subject="Quick follow-up" \
  body_html="<p>Hi John...</p>"

# Manage outreach checklist
./bin/iris sdk:call leads.outreachSteps 123 list
./bin/iris sdk:call leads.outreachSteps 123 create title="Initial Email" type=email
./bin/iris sdk:call leads.outreachSteps 123 complete 5
```

---

## Related Documentation

- **[README.md](README.md)** - Full SDK documentation
- **[AGENT_MANAGEMENT_CLI_GUIDE.md](AGENT_MANAGEMENT_CLI_GUIDE.md)** - Agent management guide
- **[OUTREACH_CLI_GUIDE.md](OUTREACH_CLI_GUIDE.md)** - Email generation and outreach
- **API Reference** - See README.md API Reference section

---

## Summary

The complete lead management workflow:

1. **Find** leads using search or aggregation
2. **Analyze** priority scores to focus efforts
3. **Update** status as deals progress
4. **Add deliverables** when work is completed
5. **Document** with notes for context
6. **Create tasks** for follow-ups
7. **Configure agent workflows** for automation
8. **Send outreach** via AI-generated emails
9. **Track outreach** with checklist steps
10. **Monitor** priorities regularly

### Agent Workflow Management

For agents with automated capabilities:
- **List workflows**: See what automations are available
- **Attach workflows**: Enable multi-step processes (recruitment, newsletters, etc.)
- **Configure settings**: Customize priority, enable/disable, and workflow-specific config
- **Update dynamically**: Adjust workflow settings without recreating agents
- **Sync workflows**: Replace all attachments at once for bulk updates

This workflow ensures nothing falls through the cracks and provides full visibility into client relationships and automated agent capabilities.
