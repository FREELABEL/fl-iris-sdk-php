# Agent Management CLI Guide

Quick reference for managing AI agents via the IRIS SDK CLI.

## Prerequisites

```bash
export IRIS_ENV=production
export IRIS_USER_ID=193

# Production token (from browser/frontend)
export IRIS_API_KEY="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiI5ZmIwZDY3OS1hMmJjLTRmMmEtODdlZS02MDk5NzMwMmMxMjQiLCJqdGkiOiJlNDI5NTBjYTNhM2E0NzhjYzBmZTExMzliNjFkYTU2NDVhZTU3YjUyOTFkNjRlNmQyOGJjNjkwNzIyMGE0ZmMzYWU1N2FmZDJiMDQzNDNmMCIsImlhdCI6MTc2MTc3NTYxNi41OTYzNzgsIm5iZiI6MTc2MTc3NTYxNi41OTYzOCwiZXhwIjoxNzkzMzExNjE2LjU4MjM5MSwic3ViIjoiIiwic2NvcGVzIjpbXX0.XifXvOEbBtaFkyMb4mCuMJ6jnFHin5z6Rq38DL53tMuY-JARYOMh6E49l59maxbCM1dpNMBFgXUMdg6cWqcCevmduobTHUvESfWF0mdsDWn78Xio7s1uSijJ0deNzKzv6DAMBh-hTEorCbuzGlXGEgLgVSDmSjFSTpM9TA9cQNE-8yuIVg6bivS6kz9t1xrzyrB76NwsdfIdcwEpgnqV8JlOsCWh6d621-XSZVs9TousY-ou5UpVNCnuQNjZYvIJeFIDynsu26xNsosN3E7hnY6YSCU1ybgNm0aH32vpG0pmDbi5wj-DNCe0zNRgYr96schsAVkD8iSG9Jt4b81qQc-vRPj6NuaqhPbIYwiOEt5PC-qC8i7LWpQ5owgv5B2Xwq0IYUPkVYIQXFQpeVdas_IaATMX48YGpac0MfgVGkV2KHmapftbgYKSyiY5y4NNbJjzvtKLBm_BL9ucPyLunI-wTPWGwGA2Pq2kyJ4u3GhkWaEtaHfXRRW7nGSPU-ZW28o6aE6GsqdwCjV6fsZpgSRjBZyd5fhURLkRWgR7-r5-UxMjQQQXf8lrnyb8uGtfa8gPraZbLFX9Psn51GU8vE7ZJ6Fx-_RS-7ziuGtBf6z9c04sB9lP4HVTeR2cBXRHUhuO1X97XdZ69r585F5rnbKwgzBHwD-AB_NoJYra5Mc"
```

**Note:** The production token above works with agent endpoints. Get your current token from the browser's network tab when using app.heyiris.io.

## List Agents

```bash
# List all agents
./bin/iris sdk:call agents.list

# Search agents
./bin/iris sdk:call agents.list search="recruiter" per_page=10

# Paginate results
./bin/iris sdk:call agents.list page=2 per_page=20
```

## Get Agent Details

```bash
# Get specific agent (note: may require different endpoint)
./bin/iris sdk:call agents.get 358
```

## Create Agent

```bash
# Basic agent
./bin/iris sdk:call agents.create \
  name="Marketing Assistant" \
  prompt="You are a helpful marketing assistant" \
  model="gpt-4o-mini"

# With integrations
./bin/iris sdk:call agents.create \
  name="Sales Agent" \
  prompt="You are a sales assistant" \
  model="gpt-4o-mini" \
  integrations='["gmail","google-drive"]'
```

## Update Agent

### Partial Update (Recommended - Only Updates Specified Fields)

```bash
# Update just the prompt (doesn't overwrite other fields)
./bin/iris sdk:call agents.patch 356 \
  initial_prompt="Focus on positive testimonials when requested..."

# Update just the name
./bin/iris sdk:call agents.patch 356 \
  name="New Agent Name"

# Update description only
./bin/iris sdk:call agents.patch 356 \
  description="Multi-purpose AI for testimonials and data analysis"
```

### Full Update (Overwrites ALL Fields)

```bash
# Update name and prompt
./bin/iris sdk:call agents.update 358 \
  name="Talent Recruiter Agent" \
  initial_prompt="You are an AI recruitment assistant..."
```

**⚠️ Important:** Use `agents.patch` for updating single fields, `agents.update` replaces all data.

### Full Configuration Update

```bash
# Update with complete settings
./bin/iris sdk:call agents.update 358 \
  name="Advanced Recruiter" \
  type="content" \
  icon="fas fa-user-tie" \
  initial_prompt="You are an AI recruitment assistant designed to support recruiters and hiring professionals..." \
  config='{"model":"gpt-4o-mini-2024-07-18","temperature":0.7,"maxTokens":2048,"provider":"openai"}' \
  settings='{"communicationStyle":"professional","responseMode":"balanced","responseLength":"balanced","functionCalling":false,"webAccess":false}'
```

### Update Specific Settings

```bash
# Update just the model
./bin/iris sdk:call agents.update 358 \
  config='{"model":"gpt-4o","temperature":0.8}'

# Update personality
./bin/iris sdk:call agents.update 358 \
  settings='{"communicationStyle":"friendly","responseLength":"detailed"}'

# Make agent public
./bin/iris sdk:call agents.update 358 \
  is_public=true \
  public_name="My Public Agent" \
  public_slug="my-agent"
```

## Chat with Agent

```bash
# Single message
./bin/iris sdk:call agents.chat 358 \
  message="Analyze this resume: John Doe - 5 years experience..."
odney Mayo's NCMA Agent for Positive Testimonials

```bash
# Set production credentials
export IRIS_API_KEY="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiI5ZmIwZDY3OS1hMmJjLTRmMmEtODdlZS02MDk5NzMwMmMxMjQiLCJqdGkiOiJlNDI5NTBjYTNhM2E0NzhjYzBmZTExMzliNjFkYTU2NDVhZTU3YjUyOTFkNjRlNmQyOGJjNjkwNzIyMGE0ZmMzYWU1N2FmZDJiMDQzNDNmMCIsImlhdCI6MTc2MTc3NTYxNi41OTYzNzgsIm5iZiI6MTc2MTc3NTYxNi41OTYzOCwiZXhwIjoxNzkzMzExNjE2LjU4MjM5MSwic3ViIjoiIiwic2NvcGVzIjpbXX0.XifXvOEbBtaFkyMb4mCuMJ6jnFHin5z6Rq38DL53tMuY-JARYOMh6E49l59maxbCM1dpNMBFgXUMdg6cWqcCevmduobTHUvESfWF0mdsDWn78Xio7s1uSijJ0deNzKzv6DAMBh-hTEorCbuzGlXGEgLgVSDmSjFSTpM9TA9cQNE-8yuIVg6bivS6kz9t1xrzyrB76NwsdfIdcwEpgnqV8JlOsCWh6d621-XSZVs9TousY-ou5UpVNCnuQNjZYvIJeFIDynsu26xNsosN3E7hnY6YSCU1ybgNm0aH32vpG0pmDbi5wj-DNCe0zNRgYr96schsAVkD8iSG9Jt4b81qQc-vRPj6NuaqhPbIYwiOEt5PC-qC8i7LWpQ5owgv5B2Xwq0IYUPkVYIQXFQpeVdas_IaATMX48YGpac0MfgVGkV2KHmapftbgYKSyiY5y4NNbJjzvtKLBm_BL9ucPyLunI-wTPWGwGA2Pq2kyJ4u3GhkWaEtaHfXRRW7nGSPU-ZW28o6aE6GsqdwCjV6fsZpgSRjBZyd5fhURLkRWgR7-r5-UxMjQQQXf8lrnyb8uGtfa8gPraZbLFX9Psn51GU8vE7ZJ6Fx-_RS-7ziuGtBf6z9c04sB9lP4HVTeR2cBXRHUhuO1X97XdZ69r585F5rnbKwgzBHwD-AB_NoJYra5Mc"
export IRIS_USER_ID=193

# Partial update - only changes the prompt, keeps everything else
./bin/iris sdk:call agents.patch 356 \
  initial_prompt="You are the Entropy NCMA Executive Leadership AI Assistant. When asked for testimonials, ALWAYS prioritize positive, constructive feedback. Filter out negative or critical comments unless specifically requested. Extract success stories, achievements, and satisfaction indicators. Format testimonials professionally for website and marketing use."

# Or use the PHP script for longer prompts
php update-rodney-agent.php
```

### Example 2: Update R
# With options
./bin/iris sdk:call agents.chat 358 \
  message="Write a job description" \
  bloq_id=208 \
  use_rag=true \
  model="gpt-4o"
```

## Delete Agent

```bash
# Delete an agent
./bin/iris sdk:call agents.delete 358
```

## Real-World Examples

### Example 1: Update Recruiter Agent for @gniice_

```bash
# Update the recruiter agent with full configuration
./bin/iris sdk:call agents.update 358 \
  name="Talent Recruiter Agent" \
  type="content" \
  icon="fas fa-user-tie" \
  description="AI agent for analyzing resumes, scoring candidates, and creating LinkedIn search queries" \
  initial_prompt="You are an AI recruitment assistant designed to support recruiters and hiring professionals in sourcing, evaluating, and onboarding top talent for various organizations.

## ROLE & COMMUNICATION STYLE
You are a pr3fessional, knowledgeable, and helpful recruitment assistant. Your tone is courteous, clear, and precise, aiming to facilitate a seamless recruitment experience.

## CORE KNOWLEDGE
- Understanding job descriptions across multiple industries
- Generating candidate profiles based on role requirements  
- Screening criteria and sourcing strategies
- Onboarding best practices

## INTERACTION FLOW
- When a user provides a job description, initiate the recruiter tool process
- Generate candidate profiles with qualification summaries
- Provide next steps for screening or outreach" \
  config='{"model":"gpt-4o-mini-2024-07-18","temperature":0.7,"maxTokens":2048,"provider":"openai"}'
```

### Example 2: Create Marketing Agent

```bash
./bin/iris sdk:call agents.create \
  name="Marketing Wizard" \
  prompt="You are an expert marketing strategist specializing in SEO, content marketing, and conversion optimization. You help businesses grow their online presence." \
  model="gpt-4o-mini" \
  type="assistant" \
  integrations='["gmail","google-drive"]'
```

### Example 4: Chat for Resume Analysis

```bash
./bin/iris sdk:call agents.chat 358 \
  message="Analyze this candidate: Jane Smith, Senior Developer with 8 years of React/Node.js experience. Led teams of 5-10 developers. Built 3 SaaS products from scratch. Looking for $150k-180k salary in Austin, TX."
```

## Configuration Options

### Model Options
- `gpt-4o-mini` - Fast, cost-effective
- `gpt-4o-mini-2024-07-18` - Latest mini version
- `gpt-4o` - Most capable
- `gpt-5-nano` - Experimental
- `claude-3-sonnet` - Anthropic
- `deepseek` - Open source alternative

### Settings Fields

```json
{
  "communicationStyle": "professional|friendly|formal|casual",
  "responseMode": "balanced|concise|detailed",
  "responseLength": "short|medium|balanced|long",
  "contextWindow": "5|10|20|50",
  "webAccess": true|false,
  "functionCalling": true|false,
  "memoryPersistence": true|false,
  "useKnowledgeBase": true|false
}
```

### Config Fields

```json
{
  "model": "gpt-4o-mini-2024-07-18",
  "temperature": 0.7,
  "maxTokens": 2048,
  "provider": "openai|anthropic|deepseek"
}
```

## Troubleshooting

### Authentication Error
```bash
# Ensure credentials are set
echo $IRIS_API_KEY
echo $IRIS_USER_ID

# Re-export if needed
export IRIS_API_KEY=your_token_here
export IRIS_USER_ID=193
```

### Route Not Found
Some agent endpoints may require client credentials (not just user token). Check API documentation for authentication requirements.

### JSON Formatting
When passing JSON via CLI, use single quotes around the JSON and double quotes inside:
```bash
./bin/iris sdk:call agents.update 358 config='{"model":"gpt-4o","temperature":0.8}'
```

## Related Resources

- Full SDK documentation: `sdk/php/README.md`
- Agent management example: `sdk/php/examples/agent-management.php`
- Lead deliverables (for tracking agents): `./bin/iris sdk:call leads.deliverables.create`
