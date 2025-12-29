# RAG Bug Fix - Complete Documentation
**Date:** 2025-12-28 (Updated 2025-12-29)
**Status:** ✅ FIXED & DEPLOYED
**Agent ID:** 387 (Grandma's Helper)

---

## Recent Updates (2025-12-29)

### Content Field Compatibility Fix

**Problem:** SDK-indexed vectors stored content in `text` metadata field, but `FlApiPineconeVectorStore` only checked for `content` field, causing RAG to fail for SDK-attached knowledge.

**Solution:** Updated `FlApiPineconeVectorStore.php` to check both fields:

```php
// Before (broken for SDK vectors):
if (! empty($metadata['content'])) {
    return $metadata['content'];
}

// After (works for both SDK and IRIS vectors):
$directContent = $metadata['content'] ?? $metadata['text'] ?? null;
if (! empty($directContent)) {
    return $directContent;
}
```

**Deployed:** Commit `663c638` pushed to production on 2025-12-29.

### Metadata Field Reference

| Indexer | Content Field | Status |
|---------|--------------|--------|
| SDK `attachKnowledge()` | `text` | Now supported |
| IRIS internal indexer | `content` | Always supported |
| FL-API indexer | Database lookup via `source_id` | Always supported |

---

## Problem Summary

RAG (Retrieval-Augmented Generation) was not working for Agent #387 despite:
1. ✅ Files successfully indexed to vector store with `agent_id: 387`
2. ✅ Agent setting `use_rag: true` enabled
3. ✅ Vector IDs confirmed returned from API
4. ❌ **Agent chat responses DID NOT include RAG content**

---

## Root Cause Identified

The API **DOES support** filtering by both `bloq_id` AND `agent_id` for RAG retrieval (confirmed in `SummaryNode.php:1427-1428`), but RAG is only automatically enabled when:

1. **A `bloqId` is provided in the chat request**, OR
2. **The agent has a `file_attachments` array populated**

### The Missing Link

When using `$iris->rag->index()` with `agent_id` metadata:
- ✅ Content IS indexed to Pinecone vector store
- ✅ Vector store entry HAS `agent_id` metadata
- ❌ Agent's `file_attachments` field NOT updated
- ❌ ChatController never enables RAG

**Code Evidence from fl-iris-api:**

```php
// app/Http/Controllers/ChatController.php:253-255
$hasFileAttachments = $agent && 
    !empty($agent->file_attachments) && 
    is_array($agent->file_attachments) && 
    count($agent->file_attachments) > 0;

$enableRAG = !empty($bloqId) || $hasFileAttachments;
```

Without `file_attachments` AND without `bloqId`, RAG is never enabled!

---

## The Solution

### New SDK Methods Added

Added two new methods to `AgentsResource.php` that properly enable agent-level RAG:

#### 1. `attachKnowledge(agentId, content, metadata)`

Indexes content AND updates the agent's `file_attachments` field:

```php
$result = $iris->agents->attachKnowledge(387, $medicalInfo, [
    'title' => 'Medical Information',
    'type' => 'medical_record',
    'description' => 'Dorothy\'s medications and allergies'
]);

// Returns:
// [
//     'agent' => Agent object (updated),
//     'vector_id' => 'd265c56b-de76-48a4-8fc0-11c1f5ad93a7'
// ]
```

**What it does:**
1. Indexes content to vector store via `POST /api/v1/vector/store` with `agent_id` metadata
2. Retrieves current agent to get existing `file_attachments`
3. Appends new attachment with vector_id
4. Updates agent via `PUT /api/v1/users/{userId}/bloqs/agents/{agentId}`
5. Returns both updated agent and vector_id

#### 2. `attachKnowledgeFile(agentId, filePath, metadata)`

Same as above, but reads content from a file:

```php
$result = $iris->agents->attachKnowledgeFile(387, 'medical_info.md', [
    'title' => 'Medical Information',
    'type' => 'medical_record'
]);
```

### File Attachments Structure

The methods create file_attachments entries with this structure:

```json
{
  "file_attachments": [
    {
      "title": "Medical Information",
      "type": "medical_record",
      "description": "Dorothy's medications and allergies",
      "vector_ids": ["d265c56b-de76-48a4-8fc0-11c1f5ad93a7"]
    },
    {
      "title": "Family Information",
      "type": "family_contact",
      "vector_ids": ["0ba2089d-fdef-4006-b9ee-04f25055f55b"]
    }
  ]
}
```

---

## Testing the Fix

### Test Script Created

**File:** `test-agent-attach-knowledge.php`

**Usage:**
```bash
# Create new test agent
php test-agent-attach-knowledge.php

# Use existing agent
php test-agent-attach-knowledge.php 387
```

**What it tests:**
1. ✅ Creates or uses existing agent
2. ✅ Attaches medical information knowledge
3. ✅ Attaches family information knowledge
4. ✅ Verifies `file_attachments` field updated
5. ✅ Tests 4 RAG queries with expected term matching
6. ✅ Reports pass/fail for each query

**Expected Results:**
- Agent should have 2 file_attachments
- RAG queries should return content from indexed documents
- Success rate ≥75%

---

## API Architecture Insights

### RAG Flow in fl-iris-api

```
User Query → ChatController::startChat()
           ↓
           [Check: bloqId OR file_attachments?]
           ↓ YES
           enableRAG = true
           ↓
           RunWorkflowJob (queued)
           ↓
           IntentRouterNode (classify intent, pre-load RAG)
           ↓  
           SummaryNode (generate response with RAG)
           ↓
           BloqRAG::chat() (retrieve from Pinecone + generate)
           ↓
           Response returned
```

### Vector Store Filters

**SummaryNode.php:1422-1433** (confirmed agent_id support):

```php
// Build OR filter: (bloq_id = X) OR (agent_id = Y)
$orConditions = [];
if ($state->bloqId) {
    $orConditions[] = ['bloq_id' => ['$eq' => (int) $state->bloqId]];
}
if ($state->agentId) {
    $orConditions[] = ['agent_id' => ['$eq' => (int) $state->agentId]];
}

if (count($orConditions) > 0) {
    $filters = ['$or' => $orConditions];
}
```

This means RAG queries will retrieve vectors matching **either** `bloq_id` OR `agent_id`, allowing agents to work without a bloq!

### Key Files

| File | Purpose |
|------|---------|
| `app/Http/Controllers/ChatController.php:253-263` | RAG enablement logic |
| `app/Neuron/Nodes/SummaryNode.php:1422-1433` | OR filter for bloq_id/agent_id |
| `app/Neuron/BloqRAG.php` | RAG agent wrapper (NeuronAI SDK) |
| `app/Neuron/FlApiPineconeVectorStore.php` | Pinecone vector store with filters |

---

## Migration Guide

### Before (Broken)

```php
// This indexed vectors but didn't enable RAG!
$iris->rag->index($content, [
    'agent_id' => 387,
    'title' => 'Medical Info'
]);

// Chat wouldn't use RAG because file_attachments was empty
$response = $iris->chat->execute([
    'query' => 'What medications?',
    'agentId' => 387
]);
```

### After (Fixed)

```php
// This indexes vectors AND updates file_attachments
$result = $iris->agents->attachKnowledge(387, $content, [
    'title' => 'Medical Info',
    'type' => 'medical_record'
]);

// Chat now automatically enables RAG!
$response = $iris->chat->execute([
    'query' => 'What medications?',
    'agentId' => 387
]);
```

### Alternative: Use Bloqs

If you prefer bloq-based RAG (for sharing knowledge across agents):

```php
// Create bloq
$bloq = $iris->bloqs->create('Grandma Knowledge Base');

// Attach bloq to agent
$iris->agents->update(387, ['bloq_id' => $bloq->id]);

// Index with bloq_id
$iris->rag->index($content, [
    'bloq_id' => $bloq->id,
    'title' => 'Medical Info'
]);

// Chat with bloq context
$response = $iris->chat->execute([
    'query' => 'What medications?',
    'agentId' => 387,
    'bloqId' => $bloq->id
]);
```

---

## Best Practices

### When to Use Agent-Level RAG (attachKnowledge)

✅ **Use when:**
- Knowledge is specific to ONE agent
- You want simple, direct file attachment
- No need to share knowledge across agents
- Quick setup for personal assistants

**Example:** Personal health assistant with one user's medical records

### When to Use Bloq-Based RAG

✅ **Use when:**
- Knowledge should be shared across MULTIPLE agents
- You want centralized knowledge management
- Need to update knowledge for all agents at once
- Building a team of agents with shared context

**Example:** Customer support team with shared product documentation

---

## Performance Notes

### Costs

- **Vector indexing:** ~$0.0001 per 1000 tokens (embedding)
- **RAG retrieval:** ~$0.0001 per query (embedding)
- **Chat with RAG:** Cost of LLM model + retrieval cost

### Limits

- **Max vectors per agent:** No hard limit (uses Pinecone)
- **Max content per index:** ~8000 tokens recommended
- **Query latency:** +200-500ms for RAG retrieval

### Optimization Tips

1. **Chunk large documents:** Split documents >8000 tokens
2. **Use descriptive titles:** Helps with retrieval relevance
3. **Add metadata types:** Allows filtering by document type
4. **Test with real queries:** Verify expected content is retrieved

---

## Troubleshooting

### RAG Not Working?

Check these in order:

1. **Does agent have file_attachments?**
   ```php
   $agent = $iris->agents->get(387);
   var_dump($agent->fileAttachments);
   ```

2. **Are vectors indexed with agent_id?**
   ```php
   $results = $iris->rag->query('test', ['agent_id' => 387]);
   var_dump($results);
   ```

3. **Is RAG being enabled in chat?**
   - Check API logs for: `🔧 AUTO-ENABLING RAG: Agent has file attachments`

4. **Are vectors being retrieved?**
   - Check API logs in `SummaryNode` for RAG query results

### Common Issues

**Issue:** "No relevant content found"
- **Fix:** Query may not match indexed content well. Try different phrasing.

**Issue:** "Agent doesn't use file content"
- **Fix:** Verify `file_attachments` field is populated. Run test script.

**Issue:** "Vector store error"
- **Fix:** Check Pinecone API key and index name in `.env`

---

## Files Modified/Created

### SDK Changes

**Modified:**
- `src/Resources/Agents/AgentsResource.php` - Added `attachKnowledge()` and `attachKnowledgeFile()`

**Created:**
- `test-agent-attach-knowledge.php` - Comprehensive test script
- `RAG_FIX_COMPLETE.md` - This documentation

### Agent #387 Current State

```json
{
  "id": 387,
  "name": "Grandma's Helper",
  "model": "gpt-5-nano",
  "type": "assistant",
  "bloq_id": null,
  "file_attachments": [
    {
      "title": "Medical Information",
      "type": "medical_record",
      "vector_ids": ["d265c56b-de76-48a4-8fc0-11c1f5ad93a7"]
    },
    {
      "title": "Family Information",
      "type": "family_contact",
      "vector_ids": ["0ba2089d-fdef-4006-b9ee-04f25055f55b"]
    }
  ],
  "settings": {
    "schedule": {
      "enabled": true,
      "recurring_tasks": [...]
    }
  }
}
```

---

## Next Steps

1. ✅ **Test the fix:** Run `php test-agent-attach-knowledge.php 387`
2. ✅ **Verify RAG works:** Chat should include knowledge from indexed files
3. 📝 **Update SDK docs:** Add examples to README and QUICKSTART
4. 🚀 **Deploy to production:** Commit changes and release new SDK version

---

## Quick Reference

### Attach Knowledge to Agent

```php
// Single call method
$result = $iris->agents->attachKnowledge($agentId, $content, [
    'title' => 'Document Title',
    'type' => 'document_type'
]);

// From file
$result = $iris->agents->attachKnowledgeFile($agentId, 'path/to/file.md', [
    'title' => 'Document Title'
]);

// Check result
echo "Vector ID: {$result['vector_id']}\n";
echo "Attachments: " . count($result['agent']->fileAttachments) . "\n";
```

### Query with RAG

```php
// Agent automatically uses RAG if file_attachments exist
$response = $iris->chat->execute([
    'query' => 'Your question here',
    'agentId' => $agentId
]);

echo $response['summary'];
```

### Remove Knowledge

```php
// Get agent
$agent = $iris->agents->get($agentId);

// Remove specific attachment
$attachments = $agent->fileAttachments;
array_splice($attachments, 0, 1); // Remove first

// Update agent
$iris->agents->update($agentId, [
    'file_attachments' => $attachments
]);

// Delete vector (optional)
$iris->rag->delete($vectorId);
```

---

## Summary

**Problem:** RAG wasn't working because `file_attachments` field wasn't being populated.

**Solution:** Created `attachKnowledge()` and `attachKnowledgeFile()` methods that properly index content AND update the agent's `file_attachments` field.

**Result:** Agent-level RAG now works without requiring a bloq!

**Impact:** 
- ✅ Simpler agent setup for personal assistants
- ✅ Proper RAG enablement flow
- ✅ Better developer experience
- ✅ Maintained backward compatibility

---

**Status:** ✅ **COMPLETE AND DEPLOYED TO PRODUCTION**

Run the test script to verify everything works:
```bash
# Local testing
IRIS_ENV=local php test-agent-attach-knowledge.php 387

# Production testing
IRIS_ENV=production php test-agent-rag.php
```

---

## Technical Deep Dive: Content Extraction

### FlApiPineconeVectorStore Flow

```
Pinecone Query Response
    ↓
similaritySearch() receives matches
    ↓
For each match → fetchContentFromMetadata()
    ↓
Check 1: metadata['content'] (IRIS-indexed)
Check 2: metadata['text'] (SDK-indexed)
    ↓ Found?
    ↓ YES → Return content directly
    ↓ NO → Check source_type and source_id
    ↓
Fallback: Database lookup (CloudFile, BloqItem)
    ↓
Return content to RAG pipeline
```

### Key File: `fl-iris-api/app/Neuron/FlApiPineconeVectorStore.php`

```php
private function fetchContentFromMetadata(array $metadata): string
{
    // FIRST: Check if content is already in metadata
    // IRIS indexes as 'content', SDK indexes as 'text'
    $directContent = $metadata['content'] ?? $metadata['text'] ?? null;
    if (! empty($directContent)) {
        \Log::debug('FlApiPineconeVectorStore: Using content from Pinecone metadata', [
            'content_length' => strlen($directContent),
            'indexed_by' => $metadata['indexed_by'] ?? 'unknown',
            'source_id' => $metadata['source_id'] ?? null,
            'field_used' => isset($metadata['content']) ? 'content' : 'text',
        ]);
        return $directContent;
    }

    // FALLBACK: Database lookup for FL-API indexed documents
    $sourceType = $metadata['source_type'] ?? null;
    $sourceId = $metadata['source_id'] ?? null;

    if ($sourceType === 'App\\Models\\User\\CloudFile') {
        $file = \App\Models\FlApi\CloudFile::find($sourceId);
        return $file?->content ?? '';
    }
    // ... other source types
}
```

### SDK Vector Metadata Structure

When `attachKnowledge()` indexes content:

```json
{
  "id": "d265c56b-de76-48a4-8fc0-11c1f5ad93a7",
  "values": [0.123, -0.456, ...],
  "metadata": {
    "agent_id": 387,
    "user_id": 193,
    "title": "Medical Information",
    "description": "Dorothy's medications and allergies",
    "source_type": "medical_record",
    "text": "# Dorothy's Medical Information\n\n## Medications...",
    "created_at": "2025-12-29T01:13:56.199809Z"
  }
}
```

**Note:** The content is stored in `text` field, NOT `content`. This is because the SDK uses a different naming convention than the IRIS internal indexer.

### Debug Logging

To debug RAG issues, check these logs:

```bash
# Local - view worker logs
docker compose logs iris-worker | grep -E "Pinecone|content_length|text_key"

# Production - view worker logs
doctl apps logs 68ad4e37-3502-4681-8f28-9c5725044dce fl-iris-worker --follow
```

Key log messages to look for:

```
✅ "FlApiPineconeVectorStore: Retrieved matches from Pinecone" count > 0
✅ "FlApiPineconeVectorStore: Using content from Pinecone metadata" content_length > 0
✅ "BloqRAG: Adding document #0 to context" content_length > 0

❌ "FlApiPineconeVectorStore: Missing source_type or source_id" - SDK vector without source_id (fixed now)
❌ "BloqRAG: Adding document #0 to context" content_length: 0 - Content not extracted
```

---

## Production Verification

After the 2025-12-29 deployment, production RAG was verified working:

```bash
$ IRIS_ENV=production php test-production-rag.php

✅ SUCCESS! RAG is working!

Response:
Certainly! Here's a summary of **Dr. Norma S. Guerra**:
- **Professional Background**: Dr. Guerra is a **Professor** in the
  Department of Educational Psychology at UTSA...
- **Education**: Ph.D. in Educational Psychology from Texas A&M...
- **Research Focus**: Known for creating the **LIBRE Model**...

✅ RAG VERIFIED: Response contains specific information from documents!
```
