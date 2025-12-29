# Servis.ai CLI Integration Guide

The IRIS SDK provides comprehensive CLI access to Servis.ai healthcare case management data. This guide covers common use cases for querying patient cases, tracking outreach, and getting case status.

## Quick Start

```bash
# Test connection
iris servis-ai test --user-id=193

# Look up a case by ID
iris servis-ai case CAS111798 --user-id=193

# Get activity timeline (notes, calls)
iris servis-ai timeline CAS111798 --user-id=193
```

## Available Commands

| Command | Description |
|---------|-------------|
| `servis-ai test` | Test connection to Servis.ai |
| `servis-ai apps` | List all available entities/apps |
| `servis-ai case <ID>` | Get case details by ID |
| `servis-ai timeline <ID>` | Get case activity timeline |
| `servis-ai analyze <ID>` | Comprehensive case analysis |
| `servis-ai fields [entity]` | List fields for an entity |
| `servis-ai execute <fn> [json]` | Execute any function |

---

## Common Use Cases

### "When was the last time we reached out to a patient?"

Use the `timeline` command to see all outreach activity:

```bash
# Get timeline for a case
iris servis-ai timeline CAS111798 --user-id=193
```

**Output:**
```
Timeline: CAS111798
===================
 [NOTE] December 23, 2025 11:01 AM - Kristen Montero
   Reached out to patient again - left another voicemail and another text...

 [PHONE_CALL] 12/23/25 11:00am - Kristen Montero
   Status: Completed

 [PHONE_CALL] 12/23/25 10:59am - Kristen Montero
   Status: Completed

 [NOTE] December 19, 2025 3:49 PM - Kristen Montero
   Notified Brittany patient has not responded...

 Total: 5 activities
```

**Key insight**: The timeline shows the last outreach was **December 23, 2025** with multiple call attempts.

### "Does this patient have lawyer representation yet?"

Use the `case` command to check representation status:

```bash
iris servis-ai case CAS111798 --user-id=193 --json | grep -i "law_firm\|stage"
```

Or for a human-readable format:
```bash
iris servis-ai case CAS111798 --user-id=193
```

Look for these key fields:
- `law_firm` - The assigned law firm (null = no representation)
- `stage_name` - Current workflow stage
- `case_status` - Active, Pending, etc.

**Example output:**
```
field_values:
  law_firm: null                    # <-- No representation yet
  stage_name: Intake                # <-- Still in intake stage
  case_status: Active
  case_manager: Kristen Montero
```

### "What is the current status of a case?"

```bash
iris servis-ai case CAS111798 --user-id=193
```

Key fields to look for:
- `stage_name`: Current workflow stage (Intake, Coordinating Care, Treating, etc.)
- `case_status`: Active, Closed, etc.
- `case_type`: Type of case
- `surgical_candidate`: Yes/No for surgical cases

### "Who is assigned to this case?"

```bash
iris servis-ai case CAS111798 --user-id=193 --json | grep -E "case_manager|patient_navigator|law_firm"
```

Look for:
- `case_manager` - Primary case manager
- `patient_navigator` - Patient navigator
- `law_firm` - Assigned law firm

---

## Workflow Stages

Cases progress through these stages (in order):

| Stage # | Stage Name |
|---------|------------|
| 1 | Intake |
| 2 | Coordinating Care |
| 3 | Treating |
| 4 | Packaging |
| 5 | Legal Review |
| 6 | Negotiating |
| 7 | Awaiting Payment |
| 8 | Processing Payment |
| 9 | Closed |

---

## Advanced Queries

### Search for cases by patient name

```bash
iris servis-ai execute list_activities '{"entity":"case_record","pattern":"Johnson","limit":10}' --user-id=193
```

### Get all phone calls for a case

```bash
iris servis-ai execute list_activities '{"entity":"phone_call_fa","pattern":"CAS111798","limit":20}' --user-id=193
```

### Get all notes for a case

```bash
iris servis-ai execute list_activities '{"entity":"note_fa","pattern":"CAS111798","limit":20}' --user-id=193
```

### List all case managers

```bash
iris servis-ai execute list_account_users --user-id=193
```

---

## JSON Output

Add `--json` for machine-readable output:

```bash
# Get timeline as JSON
iris servis-ai timeline CAS111798 --user-id=193 --json

# Pipe to jq for filtering
iris servis-ai timeline CAS111798 --user-id=193 --json | jq '.timeline[] | select(.type == "phone_call")'
```

---

## Case Data Fields Reference

### Essential Case Fields

| Field | Description | Example |
|-------|-------------|---------|
| `seq_id` | Case ID | CAS111798 |
| `patient_name` | Patient full name | Janeiece Johnson |
| `date_of_referral` | When case was created | 2025-12-19 |
| `date_of_incident` | Incident date | 2025-12-10 |
| `stage_name` | Current stage | Intake |
| `case_status` | Status | Active |
| `case_type` | Type of case | Personal Injury |
| `law_firm` | Assigned law firm | null (none) |
| `case_manager` | Assigned manager | Kristen Montero |

### Timeline Activity Types

| Type | Description |
|------|-------------|
| `note` | Case notes added by staff |
| `phone_call` | Phone call records |

---

## Environment Configuration

Set in `.env` or via environment variables:

```bash
# For local development
IRIS_ENV=local
FL_API_LOCAL_URL=http://localhost:8000
IRIS_LOCAL_API_KEY=your_api_key

# For production
IRIS_ENV=production
IRIS_API_URL=https://apiv2.heyiris.io
```

Override per-command:
```bash
IRIS_ENV=local iris servis-ai case CAS111798 --user-id=193
```

---

## Example Workflows

### Daily Case Review

```bash
#!/bin/bash
# Check status of active cases

for CASE_ID in CAS111798 CAS111799 CAS111800; do
  echo "=== $CASE_ID ==="
  iris servis-ai case $CASE_ID --user-id=193 --json | jq '{
    patient: .case_data.field_values.patient_name.display_value,
    stage: .case_data.field_values.stage_name,
    status: .case_data.field_values.case_status.display_value,
    law_firm: .case_data.field_values.law_firm.display_value
  }'
done
```

### Outreach Report

```bash
#!/bin/bash
# Get last outreach date for a case

CASE_ID=$1
LAST_ACTIVITY=$(iris servis-ai timeline $CASE_ID --user-id=193 --json | jq -r '.timeline[0].formatted_date // "No activity"')
echo "Last outreach for $CASE_ID: $LAST_ACTIVITY"
```

---

## Troubleshooting

### "No activities found"
- Verify the case ID is correct
- Check you have access to this case in Servis.ai

### "Connection failed"
- Verify SERVIS_AI_CLIENT_ID and SERVIS_AI_CLIENT_SECRET are set
- Check your user has Servis.ai integration enabled

### "Fields not found" errors
- Some fields may not exist on all entities
- Use `iris servis-ai fields case_record` to see available fields

---

## Available Functions

Execute any function with `iris servis-ai execute <function> [json_params]`:

| Function | Description |
|----------|-------------|
| `list_apps` | List all entities/apps |
| `list_activities` | Search records by pattern |
| `list_account_users` | Get all account users |
| `get_case_details` | Get case by ID |
| `get_case_timeline` | Get notes/calls for case |
| `analyze_case_comprehensive` | Full case analysis |
| `list_app_fields` | Get entity field definitions |

---

## Programmatic Usage (PHP SDK)

```php
use IRIS\SDK\IRIS;

$iris = new IRIS(['user_id' => 193]);

// Get case details
$case = $iris->servisAi->case('CAS111798');

// Get timeline
$timeline = $iris->servisAi->execute('get_case_timeline', [
    'case_id' => 'CAS111798'
]);

// Check for lawyer representation
$hasLawyer = !empty($case['case_data']['field_values']['law_firm']['value']);

// Get last outreach date
$lastActivity = $timeline['timeline'][0]['formatted_date'] ?? 'No activity';
```

---

## Quick Reference

```bash
# Connection test
iris servis-ai test

# Case lookup
iris servis-ai case CAS111798

# Activity timeline
iris servis-ai timeline CAS111798

# Search by name
iris servis-ai execute list_activities '{"pattern":"Johnson","limit":5}'

# JSON output
iris servis-ai case CAS111798 --json
```
