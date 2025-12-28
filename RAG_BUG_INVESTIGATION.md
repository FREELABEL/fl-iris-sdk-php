# RAG Bug Investigation & Fix Required

## Date: 2025-12-28
## Status: 🔴 BLOCKED - Potential Core API Bug

---

## Problem Summary

**RAG (Retrieval-Augmented Generation) is not working for Agent #387 despite:**
1. ✅ Files successfully indexed to vector store with `agent_id: 387`
2. ✅ Agent setting `use_rag: true` enabled
3. ✅ Vector IDs confirmed returned from API
4. ❌ **Agent chat responses DO NOT include RAG content**

---

## Hypothesis: Core RAG Logic Bug

### The Issue
**RAG might only be enabled when an agent has a `bloq_id` attached, but it SHOULD also work for files attached directly to an agent via `agent_id`.**

### Why This Matters
- Files indexed with `agent_id` metadata should be retrievable by that agent
- Current behavior suggests RAG only works with bloq-based knowledge bases
- This limits flexibility - agents should be able to have direct file attachments without requiring a bloq

---

## What We Tested

### 1. Created Agent #387 "Grandma's Helper"
```bash
Agent ID: 387
Model: gpt-5-nano
Type: assistant
Bloq ID: null (no bloq attached)
Settings: {"use_rag": true, "schedule": {...}}
```

### 2. Successfully Indexed Files to Vector Store
**Script:** `setup-grandma-knowledge-rag.php`

```php
// Medical Information
$iris->rag->index($content, [
    'agent_id' => 387,
    'title' => 'Medical Information',
    'type' => 'medical_record',
]);
// Result: Vector ID d265c56b-de76-48a4-8fc0-11c1f5ad93a7 ✅

// Family Information  
$iris->rag->index($content, [
    'agent_id' => 387,
    'title' => 'Family Information',
    'type' => 'family_contact',
]);
// Result: Vector ID 0ba2089d-fdef-4006-b9ee-04f25055f55b ✅
```

**Indexed Content:**
- `demo-files/grandma_medical_info.md` (2133 bytes) - Medications, allergies, doctors, conditions
- `demo-files/grandma_family_info.md` (2211 bytes) - Family contacts, birthdays, emergency info

### 3. Enabled RAG on Agent
```php
$iris->agents->update(387, [
    'settings' => [
        'use_rag' => true,
    ]
]);
// Result: ✅ Successfully updated
```

### 4. Tested Agent Chat - RAG NOT Working ❌
**Test Questions:**
```
Q: "What medications does Dorothy take in the morning?"
A: "I don't have the specific details about Dorothy's morning medications..."
```

**Expected:** Agent should retrieve from vector store:
- Lisinopril 10mg
- Metformin 500mg  
- Aspirin 81mg

**Actual:** Agent has no knowledge of the indexed content.

---

## Root Cause Analysis

### Possible Causes

#### 1. **API Requires `bloq_id` for RAG (Most Likely)**
The API's RAG retrieval logic might be:
```python
# Pseudo-code of suspected API logic
if agent.bloq_id:
    rag_results = vector_store.query(query, filters={'bloq_id': agent.bloq_id})
else:
    rag_results = []  # ❌ BUG: Should also check agent_id!
```

**Should be:**
```python
if agent.bloq_id:
    rag_results = vector_store.query(query, filters={'bloq_id': agent.bloq_id})
elif agent.id:
    rag_results = vector_store.query(query, filters={'agent_id': agent.id})
else:
    rag_results = []
```

#### 2. **Chat Endpoint Not Passing Agent Context**
The `/iris/v1/chat/workflows/simple` endpoint might not be passing agent metadata to RAG retrieval.

#### 3. **RAG Query Filter Mismatch**
Vector store indexed with `agent_id`, but queries use `bloq_id` filter only.

---

## SDK Issues Found & Fixed

### 1. ✅ Fixed: `addMemory()` Validation Error
**Problem:** API required `content` field in multipart upload.

**Fix Applied:**
```php
// src/Resources/Agents/AgentsResource.php:398
public function addMemory(int|string $agentId, string $filePath, array $metadata = []): bool
{
    // Read file content and add to metadata
    if (!isset($metadata['content'])) {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \InvalidArgumentException("Failed to read file: {$filePath}");
        }
        $metadata['content'] = $content;
    }
    
    $this->http->upload(
        "/api/v1/bloqs/agents/{$agentId}/add-memory",
        $filePath,
        $metadata
    );

    return true;
}
```

**Status:** Still failed with validation errors - endpoint might be wrong or agent needs bloq_id first.

### 2. ✅ Fixed: HTTP Client Error Handling
**Problem:** Exception constructor received array instead of string.

**Fix Applied:**
```php
// src/Http/Client.php:308
protected function handleClientException(ClientException $e): IRISException
{
    // ...
    $message = $body['message'] ?? $body['error'] ?? $e->getMessage();
    
    // Ensure message is a string
    if (is_array($message)) {
        $message = json_encode($message);
    }
    // ...
}
```

---

## Tasks for Next Agent

### Priority 1: Investigate & Fix Core API RAG Logic

#### Task 1.1: Check API RAG Retrieval Logic
**Action:** Review backend code for chat/workflow RAG integration.

**Files to Check:**
- Chat endpoint handler (`/iris/v1/chat/workflows/simple`)
- RAG retrieval service
- Vector store query logic

**Question:** Does RAG query use BOTH `bloq_id` and `agent_id` filters?

#### Task 1.2: Test Bloq-Based RAG
**Action:** Create bloq, attach to agent, upload files to bloq, test if RAG works.

```php
// Create bloq
$bloq = $iris->bloqs->create('Grandma Knowledge Base', [
    'description' => 'Medical and family info'
]);

// Update agent with bloq_id
$iris->agents->update(387, [
    'bloq_id' => $bloq->id,
    'settings' => ['use_rag' => true]
]);

// Index content with bloq_id
$iris->rag->index($content, [
    'bloq_id' => $bloq->id,
    'title' => 'Medical Info'
]);

// Test chat
$result = $iris->chat->execute([
    'query' => 'What medications does Dorothy take?',
    'agentId' => 387
]);
```

**Expected:** If RAG works with `bloq_id` but not `agent_id`, confirms the bug.

#### Task 1.3: Fix API to Support Agent-Level RAG
**Required Changes:**
1. Update RAG retrieval to check `agent_id` when `bloq_id` is null
2. Update chat endpoint to pass agent metadata to RAG
3. Add tests for both bloq-based and agent-based RAG

### Priority 2: SDK Improvements

#### Task 2.1: Clarify `addMemory()` Endpoint
**Question:** What is `/api/v1/bloqs/agents/{$agentId}/add-memory` supposed to do?

**Options:**
- Does it require agent to have a bloq_id first?
- Should it create a bloq automatically?
- Is there a different endpoint for agent-level file uploads?

**Recommendation:** If agent needs bloq first, update method signature and docs:
```php
/**
 * Add a file to the agent's memory (knowledge base).
 * 
 * NOTE: Agent must have a bloq_id attached. If not, create a bloq first
 * and update the agent's bloq_id before calling this method.
 * 
 * @throws \InvalidArgumentException if agent has no bloq_id
 */
public function addMemory(int|string $agentId, string $filePath, array $metadata = []): bool
```

#### Task 2.2: Add Helper Method for Agent + Bloq Setup
```php
// src/Resources/Agents/AgentsResource.php
public function createWithKnowledgeBase(AgentConfig $config, string $bloqTitle): Agent
{
    // Create bloq
    $bloq = $this->bloqs->create($bloqTitle, [
        'description' => 'Knowledge base for ' . $config->name
    ]);
    
    // Create agent with bloq attached
    $config->withKnowledgeBase($bloq->id);
    $agent = $this->create($config);
    
    return $agent;
}
```

### Priority 3: Complete RAG Evaluation Suite

Once RAG is working, run comprehensive evaluation:

**Script:** `test-grandma-rag-evaluation.php` (already created)

**Test Categories:**
- Medical Information (4 tests)
- Family Information (4 tests)
- Integration Tests (3 tests)
- Edge Cases (2 tests)

**Success Criteria:** ≥75% pass rate

---

## Files Created/Modified

### New Scripts
- ✅ `demo-files/grandma_medical_info.md` - Test medical data
- ✅ `demo-files/grandma_family_info.md` - Test family data
- ✅ `setup-grandma-knowledge.php` - Upload via addMemory() (failed)
- ✅ `setup-grandma-knowledge-rag.php` - Upload via RAG index (success)
- ✅ `test-grandma-rag-evaluation.php` - Comprehensive evaluation suite (ready)

### Modified SDK Files
- ✅ `src/Resources/Agents/AgentsResource.php:398` - Fixed addMemory() to include content
- ✅ `src/Http/Client.php:308` - Fixed error handling for array messages

### Uncommitted Changes
```bash
git status
# Modified:
#   src/Resources/Agents/AgentsResource.php
#   src/Http/Client.php
# Untracked:
#   demo-files/
#   setup-grandma-knowledge.php
#   setup-grandma-knowledge-rag.php
#   test-grandma-rag-evaluation.php
#   RAG_BUG_INVESTIGATION.md
```

---

## Quick Test Commands

```bash
# Check agent configuration
php -r "
require 'vendor/autoload.php';
\$iris = new IRIS\SDK\IRIS([...]);
\$agent = \$iris->agents->get(387);
echo 'Bloq ID: ' . (\$agent->bloqId ?? 'null') . PHP_EOL;
echo 'use_rag: ' . json_encode(\$agent->settings['use_rag'] ?? false) . PHP_EOL;
"

# Index files to vector store (works)
php setup-grandma-knowledge-rag.php

# Test agent chat (RAG not working)
php -r "
require 'vendor/autoload.php';
\$iris = new IRIS\SDK\IRIS([...]);
\$result = \$iris->chat->execute([
    'query' => 'What medications does Dorothy take in the morning?',
    'agentId' => 387
]);
echo \$result['summary'] . PHP_EOL;
"

# Once fixed, run evaluation suite
php test-grandma-rag-evaluation.php
```

---

## Expected Outcome

After fixing the bug, agent should respond:

**Q:** "What medications does Dorothy take in the morning?"

**A:** "Dorothy takes three medications in the morning at 8:00 AM:
1. **Lisinopril 10mg** for blood pressure (take with food)
2. **Metformin 500mg** for diabetes (take with breakfast)  
3. **Aspirin 81mg** for heart health (daily baby aspirin)

Would you like me to set reminders for these?"

---

## Contact & Handoff

**Agent:** OpenCode (Session 2025-12-28)  
**User:** Mayo Alexander  
**Environment:** Production (heyiris.io)  
**Agent ID:** 387 (Grandma's Helper)  
**User ID:** 193

**Next Steps:**
1. Investigate API RAG logic with backend team
2. Test bloq-based RAG as comparison
3. Implement fix to support agent-level RAG
4. Run evaluation suite
5. Document RAG best practices in SDK

---

## Additional Context

### Agent Configuration (Current)
```json
{
  "id": 387,
  "name": "Grandma's Helper",
  "model": "gpt-5-nano",
  "type": "assistant",
  "bloq_id": null,
  "settings": {
    "use_rag": true,
    "schedule": {
      "enabled": true,
      "timezone": "America/Chicago",
      "recurring_tasks": [
        {"name": "Morning Medication", "time": "08:00", "message": "...", "channels": ["chat"]},
        {"name": "Afternoon Medication", "time": "14:00", "message": "...", "channels": ["chat"]},
        {"name": "Evening Medication", "time": "20:00", "message": "...", "channels": ["chat"]},
        {"name": "Water Reminder", "time": "10:00", "message": "...", "channels": ["chat"]},
        {"name": "Lunch Reminder", "time": "12:00", "message": "...", "channels": ["chat"]}
      ]
    }
  }
}
```

### Vector Store Entries (Confirmed)
```
Vector ID: d265c56b-de76-48a4-8fc0-11c1f5ad93a7
  - agent_id: 387
  - title: Medical Information
  - type: medical_record
  - content: 2133 bytes

Vector ID: 0ba2089d-fdef-4006-b9ee-04f25055f55b
  - agent_id: 387
  - title: Family Information
  - type: family_contact
  - content: 2211 bytes
```

### API Endpoints Used
```
POST /api/v1/vector/store                      ✅ Working (files indexed)
POST /api/v1/vector/search                     ⚠️  Query field validation issue
POST /iris/v1/chat/workflows/simple            ✅ Working (but no RAG)
POST /api/v1/bloqs/agents/{id}/add-memory      ❌ Validation error
PUT  /api/v1/users/{userId}/bloqs/agents/{id}  ✅ Working (agent update)
GET  /api/v1/users/{userId}/bloqs/agents/{id}  ✅ Working (agent fetch)
```

---

**Good luck! This is a critical bug to fix for proper RAG functionality.** 🚀
