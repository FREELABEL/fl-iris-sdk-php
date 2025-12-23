# Outreach CLI Guide

Complete guide for managing lead outreach via the IRIS SDK CLI, including AI-powered email generation and sending.

## Table of Contents

1. [Quick Start](#quick-start)
2. [Email Generation & Sending](#email-generation--sending)
3. [Outreach Steps (Checklist)](#outreach-steps-checklist)
4. [Outreach Tracking](#outreach-tracking)
5. [Real-World Examples](#real-world-examples)

---

## Quick Start

```bash
# Set credentials
export IRIS_ENV=production
export IRIS_USER_ID=193
export IRIS_API_KEY="your_token_here"

# Generate and send an email to a lead
./bin/iris sdk:call leads.outreach 123 generateEmail prompt="Follow up on our meeting"
./bin/iris sdk:call leads.outreach 123 sendEmail to_email="john@example.com" subject="Quick follow-up" body_html="<p>Hi John...</p>"

# Manage outreach checklist
./bin/iris sdk:call leads.outreachSteps 123 list
./bin/iris sdk:call leads.outreachSteps 123 create title="Initial Email" type=email
./bin/iris sdk:call leads.outreachSteps 123 complete 5
```

---

## Email Generation & Sending

### Generate AI Email Draft

Use AI to generate a personalized email based on lead context (notes, tags, history).

```bash
# Basic email generation
./bin/iris sdk:call leads.outreach 123 generateEmail \
  prompt="Follow up on our meeting about their AI needs"

# With tone and options
./bin/iris sdk:call leads.outreach 123 generateEmail \
  prompt="Initial cold outreach introducing our platform" \
  tone=professional \
  include_cta=true \
  max_length=medium

# With specific agent/profile context
./bin/iris sdk:call leads.outreach 123 generateEmail \
  prompt="Send proposal follow-up" \
  profile_id=456 \
  agent_id=789
```

**Tone Options:** `professional`, `casual`, `friendly`, `urgent`

**Max Length Options:** `short`, `medium`, `long`

**Response:**
```json
{
  "success": true,
  "draft": {
    "subject": "Quick follow-up on our AI discussion",
    "body": "<p>Hi John,...</p>"
  },
  "context_used": {
    "lead_notes": 3,
    "profile_info": true
  },
  "lead": {
    "id": 123,
    "name": "John Doe",
    "email": "john@example.com"
  }
}
```

### Revise Existing Draft

Refine an AI-generated email with additional instructions:

```bash
./bin/iris sdk:call leads.outreach 123 generateEmail \
  prompt="Make it more urgent and add a deadline" \
  options='{"revision_mode":true,"current_subject":"Quick follow-up","current_body":"<p>Hi John...</p>"}'
```

### Send Composed Email

Send the email via Resend API (records in lead history automatically):

```bash
./bin/iris sdk:call leads.outreach 123 sendEmail \
  to_email="john@example.com" \
  to_name="John Doe" \
  subject="Quick follow-up on our AI discussion" \
  body_html="<p>Hi John,</p><p>Great chatting with you...</p>" \
  sender_name="Alex Mayo"
```

**Required Parameters:**
- `to_email` - Recipient email
- `subject` - Email subject
- `body_html` - HTML body content

**Optional Parameters:**
- `to_name` - Recipient name
- `body_text` - Plain text version
- `from_email` - Sender email (defaults to system)
- `sender_name` - Sender display name

**Response:**
```json
{
  "success": true,
  "message": "Email sent successfully",
  "email": {
    "id": 456,
    "subject": "Quick follow-up on our AI discussion",
    "to_email": "john@example.com",
    "sent_at": "2025-12-22T15:30:00Z"
  },
  "lead": {
    "id": 123,
    "nickname": "John Doe"
  }
}
```

### Generate and Send (Combined)

For automated workflows, generate and send in one step (PHP SDK only):

```php
$result = $iris->leads->outreach(123)->generateAndSend(
    'john@example.com',
    'Initial cold outreach introducing our AI platform',
    [
        'tone' => 'professional',
        'sender_name' => 'Alex Mayo',
        'to_name' => 'John',
    ]
);

if ($result['success']) {
    echo "Email sent! ID: {$result['email']['id']}\n";
}
```

---

## Outreach Steps (Checklist)

Manage a structured outreach strategy with trackable steps.

### List Steps

```bash
./bin/iris sdk:call leads.outreachSteps 123 list
```

**Response:**
```json
{
  "success": true,
  "data": {
    "steps": [
      {"id": 1, "title": "Initial Email", "type": "email", "is_completed": true, "order": 0},
      {"id": 2, "title": "Follow-up Call", "type": "phone", "is_completed": false, "order": 1},
      {"id": 3, "title": "LinkedIn Connect", "type": "linkedin", "is_completed": false, "order": 2}
    ],
    "stats": {
      "total": 3,
      "completed": 1,
      "pending": 2,
      "progress_percent": 33
    },
    "outreach_types": ["email", "phone", "sms", "visit", "linkedin", "social", "mail", "other"]
  }
}
```

### Create Step

```bash
# Simple step
./bin/iris sdk:call leads.outreachSteps 123 create \
  title="Send introduction email" \
  type=email

# Detailed step
./bin/iris sdk:call leads.outreachSteps 123 create \
  title="Discovery call" \
  type=phone \
  instructions="Discuss project requirements and timeline" \
  due_date="2025-01-15"
```

**Step Types:** `email`, `phone`, `sms`, `visit`, `linkedin`, `social`, `mail`, `other`

### Update Step

```bash
# Update title and instructions
./bin/iris sdk:call leads.outreachSteps 123 update 5 \
  title="Follow-up email with proposal" \
  instructions="Attach pricing PDF"

# Mark as completed with notes
./bin/iris sdk:call leads.outreachSteps 123 update 5 \
  is_completed=true \
  notes="Email sent, waiting for response"
```

### Complete / Reopen Step

```bash
# Mark as completed
./bin/iris sdk:call leads.outreachSteps 123 complete 5

# With completion notes
./bin/iris sdk:call leads.outreachSteps 123 complete 5 notes="Left voicemail"

# Reopen (mark incomplete)
./bin/iris sdk:call leads.outreachSteps 123 reopen 5
```

### Delete Step

```bash
./bin/iris sdk:call leads.outreachSteps 123 delete 5
```

### Reorder Steps

```bash
# Provide step IDs in desired order
./bin/iris sdk:call leads.outreachSteps 123 reorder step_ids='[5,3,4,6,7]'
```

### Initialize Default Strategy

Create a predefined outreach strategy for new leads:

```bash
./bin/iris sdk:call leads.outreachSteps 123 initializeDefault
```

### Clear All Steps

```bash
./bin/iris sdk:call leads.outreachSteps 123 clearAll
```

---

## Outreach Tracking

### Check Eligibility

Verify if a lead is eligible for outreach:

```bash
./bin/iris sdk:call leads.outreach 123 checkEligibility
```

### Get Outreach Info

Get comprehensive outreach information:

```bash
./bin/iris sdk:call leads.outreach 123 getInfo
```

**Response includes:**
- Lead details
- Eligibility status
- Outreach stats (attempts, last contact)
- Recent outreach history

### Record Outreach Attempt

Manually record an outreach attempt:

```bash
./bin/iris sdk:call leads.outreach 123 recordAttempt \
  content="Called and left voicemail about pricing" \
  metadata='{"channel":"phone","outcome":"voicemail"}'
```

### Set Auto-Respond

Enable/disable automatic responses for a lead:

```bash
# Enable
./bin/iris sdk:call leads.outreach 123 setAutoRespond enabled=true

# Disable
./bin/iris sdk:call leads.outreach 123 setAutoRespond enabled=false
```

---

## Real-World Examples

### Example 1: Complete Outreach Flow for New Lead

```bash
# 1. Find the lead
./bin/iris sdk:call leads.search search="John Smith" bloq_id=40
# Lead ID: 456

# 2. Initialize default outreach strategy
./bin/iris sdk:call leads.outreachSteps 456 initializeDefault

# 3. Generate introduction email
./bin/iris sdk:call leads.outreach 456 generateEmail \
  prompt="Initial introduction to our AI platform. Mention we specialize in workflow automation." \
  tone=professional \
  include_cta=true

# 4. Review draft, then send
./bin/iris sdk:call leads.outreach 456 sendEmail \
  to_email="john.smith@company.com" \
  to_name="John Smith" \
  subject="AI-Powered Workflow Automation for Your Team" \
  body_html="<p>Hi John,</p><p>I wanted to reach out...</p>" \
  sender_name="Alex Mayo"

# 5. Mark first outreach step as completed
./bin/iris sdk:call leads.outreachSteps 456 complete 1 notes="Introduction email sent"

# 6. Check outreach progress
./bin/iris sdk:call leads.outreachSteps 456 list
```

### Example 2: Follow-up Campaign

```bash
# Check if lead is eligible for follow-up
./bin/iris sdk:call leads.outreach 123 checkEligibility

# Generate follow-up email
./bin/iris sdk:call leads.outreach 123 generateEmail \
  prompt="Gentle follow-up since they haven't responded to our initial email. Offer a quick demo call." \
  tone=friendly \
  max_length=short

# Send it
./bin/iris sdk:call leads.outreach 123 sendEmail \
  to_email="john@example.com" \
  subject="Quick follow-up + demo offer" \
  body_html="<p>Hi John, just checking in...</p>"

# Record in outreach steps
./bin/iris sdk:call leads.outreachSteps 123 complete 2 notes="Follow-up email sent, offered demo"
```

### Example 3: Multi-Channel Outreach Tracking

```bash
# Create custom outreach steps
./bin/iris sdk:call leads.outreachSteps 789 create title="LinkedIn connection request" type=linkedin
./bin/iris sdk:call leads.outreachSteps 789 create title="Comment on their recent post" type=social
./bin/iris sdk:call leads.outreachSteps 789 create title="Send personalized email" type=email
./bin/iris sdk:call leads.outreachSteps 789 create title="Schedule discovery call" type=phone

# After completing LinkedIn step
./bin/iris sdk:call leads.outreachSteps 789 complete 1 notes="Connection accepted!"

# Record the social engagement
./bin/iris sdk:call leads.outreach 789 recordAttempt \
  content="Commented on their AI strategy post, got a like back" \
  metadata='{"channel":"linkedin","outcome":"engagement"}'

# Mark social step complete
./bin/iris sdk:call leads.outreachSteps 789 complete 2
```

### Example 4: Automated Email via Script

```bash
#!/bin/bash
# automated-outreach.sh

LEAD_ID=$1
TO_EMAIL=$2
PROMPT=$3

# Generate draft
DRAFT=$(./bin/iris sdk:call leads.outreach $LEAD_ID generateEmail prompt="$PROMPT" --json)

# Extract subject and body
SUBJECT=$(echo $DRAFT | jq -r '.draft.subject')
BODY=$(echo $DRAFT | jq -r '.draft.body')

# Send email
./bin/iris sdk:call leads.outreach $LEAD_ID sendEmail \
  to_email="$TO_EMAIL" \
  subject="$SUBJECT" \
  body_html="$BODY"

echo "Email sent to $TO_EMAIL"
```

---

## PHP SDK Examples

```php
<?php
use IRIS\SDK\IRIS;

$iris = new IRIS([
    'api_key' => 'your_api_key',
    'user_id' => 193,
]);

// Generate email
$draft = $iris->leads->outreach(123)->generateEmail(
    'Follow up on AI platform demo',
    ['tone' => 'professional', 'include_cta' => true]
);

echo "Generated: {$draft['draft']['subject']}\n";

// Send email
$result = $iris->leads->outreach(123)->sendEmail([
    'to_email' => 'john@example.com',
    'to_name' => 'John Doe',
    'subject' => $draft['draft']['subject'],
    'body_html' => $draft['draft']['body'],
    'sender_name' => 'Alex Mayo',
]);

if ($result['success']) {
    echo "Sent! Email ID: {$result['email']['id']}\n";
}

// Manage outreach steps
$steps = $iris->leads->outreachSteps(123)->list();
echo "Progress: {$steps['data']['stats']['progress_percent']}%\n";

// Complete a step
$iris->leads->outreachSteps(123)->complete(5, 'Email sent successfully');
```

---

## Troubleshooting

### Email Not Sending

1. Check Resend API is configured in production
2. Verify `to_email` is valid
3. Check lead exists: `./bin/iris sdk:call leads.get 123`

### AI Generation Failing

1. Ensure OpenAI API key is configured
2. Check prompt is at least 5 characters
3. Verify lead has enough context (notes, tags)

### Outreach Steps Not Saving

1. Verify lead ID exists
2. Check `type` is valid: email, phone, sms, visit, linkedin, social, mail, other
3. Ensure `title` is provided

---

## Related Documentation

- **[README.md](README.md)** - Full SDK documentation
- **[LEAD_MANAGEMENT_WORKFLOW.md](LEAD_MANAGEMENT_WORKFLOW.md)** - Complete lead workflow guide
- **[AGENT_MANAGEMENT_CLI_GUIDE.md](AGENT_MANAGEMENT_CLI_GUIDE.md)** - Agent management
