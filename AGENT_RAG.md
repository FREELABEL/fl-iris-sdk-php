# Agent RAG (Retrieval-Augmented Generation)

This guide covers how to attach knowledge to agents and use RAG for intelligent context retrieval.

## Quick Start

```php
use IRIS\SDK\IRIS;

$iris = new IRIS(['api_key' => 'your-key', 'user_id' => 123]);

// Attach knowledge to an agent
$result = $iris->agents->attachKnowledge($agentId, $content, [
    'title' => 'Medical Records',
    'type' => 'medical_record',
    'description' => 'Patient health information'
]);

// Chat with the agent - RAG is automatically enabled
$response = $iris->chat->execute([
    'query' => 'What medications am I taking?',
    'agentId' => $agentId
]);

echo $response['summary']; // Response includes knowledge from attached documents
```

## How It Works

### Architecture Flow

```
User Query → ChatController
           ↓
           [Check: agent has file_attachments?]
           ↓ YES → enableRAG = true
           ↓
           RunWorkflowJob (queued)
           ↓
           IntentRouterNode → Pre-load RAG context
           ↓
           SummaryNode → Generate with RAG
           ↓
           BloqRAG → Query Pinecone vectors
           ↓
           FlApiPineconeVectorStore → Fetch content
           ↓
           Response returned with knowledge context
```

### Vector Store Metadata

When you call `attachKnowledge()`, the SDK stores vectors in Pinecone with this metadata:

```json
{
  "agent_id": 387,
  "user_id": 193,
  "title": "Medical Records",
  "description": "Patient health information",
  "source_type": "medical_record",
  "text": "The actual document content...",
  "created_at": "2025-12-29T01:13:56Z"
}
```

**Important:** The SDK stores content in the `text` field. The iris-api retrieves content from both `text` (SDK-indexed) and `content` (IRIS-indexed) fields for compatibility.

## API Reference

### `$iris->agents->attachKnowledge(agentId, content, metadata)`

Indexes content to the vector store AND updates the agent's `file_attachments` field.

**Parameters:**
- `agentId` (int): The agent ID
- `content` (string): The document content to index
- `metadata` (array): Optional metadata
  - `title` (string): Document title (used for display)
  - `type` (string): Document type (e.g., 'medical_record', 'family_contact')
  - `description` (string): Brief description

**Returns:**
```php
[
    'agent' => Agent,      // Updated agent object
    'vector_id' => string  // UUID of the indexed vector
]
```

**Example:**
```php
$medicalInfo = <<<MD
# Dorothy's Medical Information

## Medications
- Lisinopril 10mg - Morning
- Metformin 500mg - Morning
- Aspirin 81mg - Daily

## Allergies
- Penicillin (severe)
- Latex (mild)
MD;

$result = $iris->agents->attachKnowledge(387, $medicalInfo, [
    'title' => 'Medical Information',
    'type' => 'medical_record',
    'description' => 'Dorothy\'s medications and allergies'
]);

echo "Vector ID: {$result['vector_id']}\n";
echo "Total attachments: " . count($result['agent']->fileAttachments) . "\n";
```

### `$iris->agents->attachKnowledgeFile(agentId, filePath, metadata)`

Same as `attachKnowledge()` but reads content from a file.

**Example:**
```php
$result = $iris->agents->attachKnowledgeFile(387, 'medical_info.md', [
    'title' => 'Medical Information',
    'type' => 'medical_record'
]);
```

## File Attachments Structure

The agent's `file_attachments` field stores metadata about attached knowledge:

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

This structure triggers RAG enablement in the chat flow.

## Testing RAG

### Using the Test Script

```bash
# Create new test agent with knowledge
php test-agent-attach-knowledge.php

# Use existing agent
php test-agent-attach-knowledge.php 387
```

### Manual Verification

```php
// 1. Check agent has file attachments
$agent = $iris->agents->get(387);
var_dump($agent->fileAttachments);
// Should show array with title, type, vector_ids

// 2. Query directly
$response = $iris->chat->execute([
    'query' => 'What medications does Dorothy take in the morning?',
    'agentId' => 387
]);

// 3. Verify response contains knowledge
echo $response['summary'];
// Should mention Lisinopril, Metformin, Aspirin
```

## Troubleshooting

### RAG Not Working?

**Check 1: Does agent have file_attachments?**
```php
$agent = $iris->agents->get(387);
if (empty($agent->fileAttachments)) {
    echo "No file attachments - use attachKnowledge() to add them\n";
}
```

**Check 2: Are vectors properly indexed?**
```php
// Look for this log in iris-api:
// "🔧 AUTO-ENABLING RAG: Agent has file attachments"
```

**Check 3: Is content being retrieved?**
```php
// Look for these logs in iris-api:
// "FlApiPineconeVectorStore: Retrieved matches from Pinecone" (count > 0)
// "FlApiPineconeVectorStore: Using content from Pinecone metadata"
```

### Common Issues

| Issue | Cause | Fix |
|-------|-------|-----|
| "No relevant content found" | Query doesn't match indexed content | Try different phrasing |
| "Agent doesn't use file content" | `file_attachments` field empty | Use `attachKnowledge()` instead of `rag->index()` |
| "Vector store error" | Missing API keys | Check PINECONE_API_KEY in .env |
| "0 matches returned" | Vector similarity too low | Add more descriptive content or use different queries |

### Debug Logging

Check iris-api logs for detailed RAG flow:

```bash
# Local
docker compose logs iris-worker | grep -E "RAG|Pinecone|content_length"

# Production
doctl apps logs 68ad4e37-3502-4681-8f28-9c5725044dce fl-iris-worker --follow | grep RAG
```

Key log messages:
- `BloqRAG: Starting document retrieval` - RAG query initiated
- `FlApiPineconeVectorStore: Retrieved matches` - Vectors found (check count)
- `FlApiPineconeVectorStore: Using content from Pinecone metadata` - Content extracted successfully
- `BloqRAG: Adding document #0 to context` - Check content_length > 0

## Best Practices

### Document Preparation

1. **Keep content focused:** 1000-8000 tokens per document works best
2. **Use descriptive titles:** Helps with retrieval relevance
3. **Add meaningful types:** Enables filtering by document category
4. **Include key terms:** Embed relevant keywords naturally

### Chunking Large Documents

For documents > 8000 tokens, split into logical sections:

```php
$sections = [
    ['title' => 'Medical - Medications', 'content' => $medicationsSection],
    ['title' => 'Medical - Allergies', 'content' => $allergiesSection],
    ['title' => 'Medical - Providers', 'content' => $providersSection],
];

foreach ($sections as $section) {
    $iris->agents->attachKnowledge($agentId, $section['content'], [
        'title' => $section['title'],
        'type' => 'medical_record'
    ]);
}
```

### Removing Knowledge

```php
// Get current attachments
$agent = $iris->agents->get($agentId);
$attachments = $agent->fileAttachments;

// Remove specific attachment by index
array_splice($attachments, 0, 1);

// Update agent
$iris->agents->update($agentId, [
    'file_attachments' => $attachments
]);

// Optionally delete vector from Pinecone
$iris->rag->delete($vectorId);
```

## Agent-Level vs Bloq-Level RAG

### Agent-Level RAG (attachKnowledge)

Use when:
- Knowledge is specific to ONE agent
- Simple, direct file attachment needed
- No sharing across agents required
- Building personal assistants

```php
$iris->agents->attachKnowledge($agentId, $content, $metadata);
```

### Bloq-Level RAG

Use when:
- Knowledge should be shared across MULTIPLE agents
- Centralized knowledge management needed
- Updating knowledge for all agents at once
- Building agent teams with shared context

```php
// Create bloq
$bloq = $iris->bloqs->create('Shared Knowledge Base');

// Attach bloq to multiple agents
$iris->agents->update(387, ['bloq_id' => $bloq->id]);
$iris->agents->update(388, ['bloq_id' => $bloq->id]);

// Index with bloq_id
$iris->rag->index($content, [
    'bloq_id' => $bloq->id,
    'title' => 'Shared Document'
]);
```

## Technical Details

### Vector Store Implementation

The iris-api uses `FlApiPineconeVectorStore` which:
1. Queries Pinecone with `agent_id` OR `bloq_id` filter
2. Retrieves metadata including content
3. Extracts content from `text` (SDK) or `content` (IRIS) field
4. Falls back to database lookup if content not in metadata

### Metadata Field Compatibility

| Indexer | Content Field | Supported |
|---------|--------------|-----------|
| SDK `attachKnowledge()` | `text` | Yes |
| IRIS internal indexer | `content` | Yes |
| FL-API indexer | Database lookup | Yes |

The `FlApiPineconeVectorStore` checks all sources:
```php
$directContent = $metadata['content'] ?? $metadata['text'] ?? null;
if (!empty($directContent)) {
    return $directContent;
}
// Fallback to database lookup via source_id
```

### Filter Query Structure

RAG queries use Pinecone's `$or` filter:

```php
$filters = [
    '$or' => [
        ['bloq_id' => ['$eq' => 33]],
        ['agent_id' => ['$eq' => 387]]
    ]
];
```

This retrieves vectors matching EITHER the bloq OR the agent.

## Changelog

### 2025-12-29: SDK Content Field Fix
- Fixed `FlApiPineconeVectorStore` to read content from both `text` (SDK) and `content` (IRIS) metadata fields
- Previously SDK-indexed vectors weren't being read correctly because they use `text` not `content`
- Deployed to production: commit `663c638`

### 2025-12-28: attachKnowledge Methods Added
- Added `attachKnowledge()` and `attachKnowledgeFile()` to AgentsResource
- These methods properly update `file_attachments` field to enable RAG
- Created comprehensive test script `test-agent-attach-knowledge.php`

## See Also

- [RAG_FIX_COMPLETE.md](./RAG_FIX_COMPLETE.md) - Detailed bug fix documentation
- [RAG_BUG_INVESTIGATION.md](./RAG_BUG_INVESTIGATION.md) - Investigation notes
- [AGENT_CONFIGURATION_GUIDE.md](./AGENT_CONFIGURATION_GUIDE.md) - Full agent configuration
