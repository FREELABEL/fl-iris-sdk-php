# IRIS SDK CLI - Dynamic Proxy

Single lightweight CLI that dynamically accesses all SDK resources and methods.

## Installation

```bash
composer require symfony/console
chmod +x bin/iris
```

## Configuration

Set environment variables:
```bash
export IRIS_API_KEY=your_api_key
export IRIS_USER_ID=your_user_id
```

Or pass inline:
```bash
bin/iris call leads.list --api-key=xxx --user-id=123
```

## Usage Pattern

```bash
bin/iris call <resource>.<method> [params] [--options]
```

## Examples

### Leads
```bash
# List all leads
bin/iris call leads.list

# Get specific lead
bin/iris call leads.get 123

# Lead aggregation stats
bin/iris call leads.aggregation.statistics --json

# Lead aggregation list with filters
bin/iris call leads.aggregation.list has_incomplete_tasks=1

# Get lead requirements
bin/iris call leads.aggregation.requirements 123
```

### Agents
```bash
# List agents
bin/iris call agents.list

# Chat with agent
bin/iris call agents.chat agent_id=5 message="Hello"

# Create agent
bin/iris call agents.create name="My Agent" type=assistant
```

### Workflows
```bash
# List workflows
bin/iris call workflows.list

# Get workflow
bin/iris call workflows.get 42

# Create workflow
bin/iris call workflows.create '{"name":"Test","steps":[]}'
```

### Bloqs
```bash
# List bloqs
bin/iris call bloqs.list

# Get bloq
bin/iris call bloqs.get 10
```

### RAG (Document Management)
```bash
# List documents
bin/iris call rag.list

# Upload document
bin/iris call rag.upload file=/path/to/doc.pdf

# Search documents
bin/iris call rag.search query="contract terms"
```

## Output Formats

### JSON Output
```bash
bin/iris call leads.list --json
```

### Raw Output (no formatting)
```bash
bin/iris call leads.get 123 --raw
```

### Default (Smart Tables)
Automatically formats arrays as tables, objects as key-value pairs.

## Parameter Types

The CLI auto-detects types:
- `true`/`false` → boolean
- `123` → integer
- `12.5` → float
- `null` → null
- `{"key":"val"}` → JSON object
- `[1,2,3]` → JSON array
- `"text"` → string

## Nested Resources

Access nested resources with dot notation:
```bash
bin/iris call leads.aggregation.statistics
bin/iris call leads.tasks 123
bin/iris call leads.activities 123
```

## For Autonomous Agents

Perfect for programmatic access:
```bash
# Platform AI Agent - Find high-priority work
LEADS=$(bin/iris call leads.aggregation.list has_incomplete_tasks=1 --json)

# SDK AI Agent - Get requirements
REQS=$(bin/iris call leads.aggregation.requirements 123 --json)

# QA Engineer Agent - Monitor stats
STATS=$(bin/iris call leads.aggregation.statistics --json)
```

## Extensibility

Add new SDK resources/methods → automatically available in CLI.
No code changes needed to CLI - it's a pure proxy.
