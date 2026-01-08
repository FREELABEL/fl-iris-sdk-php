# Dima POC: Phone Number Management Agents

**Objective:** Integrate IRIS agent orchestration into Dima's React phone management app to automate number lifecycle rules.

---

## POC Scope

Build 3 intelligent agents for phone number management:

1. **Unused Number Releaser** - Auto-release numbers with no calls for 72+ hours
2. **Low Usage Detector** - Flag numbers with only 1 call in specific day
3. **Tag-Based Optimizer** - Custom rules based on number tags

**Success Criteria:**
- ✅ Agents call Dima's existing phone API
- ✅ Agents run on schedule (e.g., daily at 3am)
- ✅ React app can trigger agents on-demand
- ✅ Dashboard shows agent execution status
- ✅ Multi-tenant context (client_id) works

---

## Architecture

```
┌─────────────────────────────────────────────────────────┐
│           Dima's React App (Frontend)                    │
│  - Buy numbers UI                                        │
│  - View number status                                    │
│  - Trigger agent runs manually                           │
└───────────────┬─────────────────────────────────────────┘
                │
                │ REST API calls
                ▼
┌─────────────────────────────────────────────────────────┐
│         Dima's Custom Phone API (Backend)                │
│  - GET /api/numbers (fetch numbers)                      │
│  - POST /api/numbers/{id}/release (release number)       │
│  - GET /api/numbers/{id}/calls (get call history)        │
│  - POST /api/numbers/{id}/tag (add tags)                 │
└───────────────┬─────────────────────────────────────────┘
                │
                │ Custom Functions
                ▼
┌─────────────────────────────────────────────────────────┐
│          IRIS Agent Orchestration Layer                  │
│                                                           │
│  Agent 1: Unused Number Releaser                         │
│  - Scheduled: Daily at 3am                               │
│  - Tools: [fetchNumbers, releaseNumber, getCalls]        │
│  - Logic: If no calls for 72h → release                  │
│                                                           │
│  Agent 2: Low Usage Detector                             │
│  - Scheduled: Daily at 4am                               │
│  - Tools: [fetchNumbers, getCalls, tagNumber]            │
│  - Logic: If 1 call in day → tag as "low-usage"         │
│                                                           │
│  Agent 3: Tag-Based Optimizer                            │
│  - Scheduled: Weekly                                     │
│  - Tools: [fetchNumbers, analyzeUsage, sendAlert]        │
│  - Logic: Custom rules based on tags                     │
└─────────────────────────────────────────────────────────┘
```

---

## Phase 1: Custom API Integration

### Step 1: Define Custom Functions (Tools)

Dima's agents need to call his phone API. We'll define custom functions that IRIS agents can use.

**Custom Functions Needed:**

```yaml
# functions.yaml - OpenAI Function Calling Spec

- name: fetchNumbers
  description: Fetch all phone numbers for a client
  parameters:
    type: object
    properties:
      client_id:
        type: string
        description: The client ID (tenant)
      filter:
        type: string
        description: Optional filter (e.g., "unused", "low-usage")
    required: [client_id]
  endpoint: https://dima-phone-api.com/api/numbers
  method: GET
  auth: Bearer {DIMA_API_KEY}

- name: releaseNumber
  description: Release a phone number to avoid charges
  parameters:
    type: object
    properties:
      number_id:
        type: string
        description: The phone number ID
      client_id:
        type: string
        description: The client ID (tenant)
      reason:
        type: string
        description: Reason for release (for audit log)
    required: [number_id, client_id]
  endpoint: https://dima-phone-api.com/api/numbers/{number_id}/release
  method: POST
  auth: Bearer {DIMA_API_KEY}

- name: getCallHistory
  description: Get call history for a phone number
  parameters:
    type: object
    properties:
      number_id:
        type: string
        description: The phone number ID
      days:
        type: integer
        description: Number of days to look back
    required: [number_id]
  endpoint: https://dima-phone-api.com/api/numbers/{number_id}/calls
  method: GET
  auth: Bearer {DIMA_API_KEY}

- name: tagNumber
  description: Add a tag to a phone number
  parameters:
    type: object
    properties:
      number_id:
        type: string
        description: The phone number ID
      tag:
        type: string
        description: Tag to add (e.g., "low-usage", "auto-release")
    required: [number_id, tag]
  endpoint: https://dima-phone-api.com/api/numbers/{number_id}/tag
  method: POST
  auth: Bearer {DIMA_API_KEY}

- name: sendAlert
  description: Send alert to admin
  parameters:
    type: object
    properties:
      message:
        type: string
        description: Alert message
      severity:
        type: string
        enum: [info, warning, critical]
    required: [message]
  endpoint: https://dima-phone-api.com/api/alerts
  method: POST
  auth: Bearer {DIMA_API_KEY}
```

### Step 2: Register Functions in IRIS

**Via CLI:**

```bash
# Add custom function to agent
./bin/iris sdk:call agents.addCustomFunction 11 \
  name="fetchNumbers" \
  description="Fetch all phone numbers for a client" \
  endpoint="https://dima-phone-api.com/api/numbers" \
  method="GET" \
  parameters='{"client_id":{"type":"string","required":true}}'
```

**Via Web UI:**
- Go to Agent → Functions tab
- Click "Add Custom Function"
- Paste OpenAI function spec YAML
- Save

**Via PHP SDK:**

```php
$iris->agents->addCustomFunction(11, [
    'name' => 'fetchNumbers',
    'description' => 'Fetch all phone numbers for a client',
    'endpoint' => 'https://dima-phone-api.com/api/numbers',
    'method' => 'GET',
    'auth' => ['type' => 'bearer', 'token' => env('DIMA_API_KEY')],
    'parameters' => [
        'type' => 'object',
        'properties' => [
            'client_id' => ['type' => 'string', 'required' => true],
        ],
    ],
]);
```

---

## Phase 2: Create Agents

### Agent 1: Unused Number Releaser

**Agent Configuration:**

```php
use IRIS\SDK\Resources\Agents\AgentSettings;
use IRIS\SDK\Resources\Agents\AgentScheduleConfig;

// Create schedule
$schedule = new AgentScheduleConfig([
    'enabled' => true,
    'timezone' => 'America/Los_Angeles',
]);

// Daily at 3am
$schedule->addRecurringTask([
    'time' => '03:00',
    'frequency' => 'daily',
    'message' => 'Check for unused phone numbers and auto-release',
    'channels' => ['api'],
]);

// Build settings
$settings = new AgentSettings();
$settings->enableFunction('fetchNumbers')
         ->enableFunction('getCallHistory')
         ->enableFunction('releaseNumber')
         ->enableFunction('sendAlert');

// Create agent
$agent = $iris->agents->createFromConfig([
    'name' => 'Unused Number Releaser',
    'prompt' => <<<PROMPT
You are an intelligent phone number lifecycle manager.

**Your Task:**
1. Fetch all phone numbers for client_id: {{client_id}}
2. For each number, check call history for the last 72 hours
3. If a number has ZERO calls in 72 hours, release it
4. Send alert summarizing how many numbers were released

**Rules:**
- Only release numbers with 0 calls in last 72 hours
- Always include reason: "Auto-released: No usage for 72+ hours"
- Log each release action
- Send summary alert at end

**Context:**
- Current date: {{current_date}}
- Client ID: {{client_id}}

Execute this workflow autonomously.
PROMPT,
    'model' => 'gpt-4o-mini',
    'settings' => [
        'schedule' => $schedule->toArray(),
        'enabledFunctions' => [
            'fetchNumbers' => true,
            'getCallHistory' => true,
            'releaseNumber' => true,
            'sendAlert' => true,
        ],
        'responseMode' => 'autonomous',
        'contextWindow' => 50,
    ],
]);

echo "Created agent #{$agent->id}: {$agent->name}\n";
```

**Via CLI:**

```bash
# Create agent
./bin/iris sdk:call agents.create \
  name="Unused Number Releaser" \
  prompt="You are an intelligent phone number lifecycle manager. Your task is to fetch all phone numbers, check call history for 72 hours, and auto-release numbers with zero calls." \
  model="gpt-4o-mini"

# Agent ID: 500

# Add schedule
./bin/iris sdk:call agents.updateSchedule 500 \
  enabled=true \
  timezone="America/Los_Angeles" \
  recurring_tasks='[{"time":"03:00","frequency":"daily","message":"Check unused numbers","channels":["api"]}]'
```

---

## Phase 3: React App Integration

Dima's React app needs to trigger agents and check status.

### Option 1: REST API (Recommended)

**Trigger Agent from React:**

```typescript
// src/services/irisAgentService.ts
import axios from 'axios';

const IRIS_API_URL = process.env.REACT_APP_IRIS_API_URL;
const IRIS_API_KEY = process.env.REACT_APP_IRIS_API_KEY;

export const irisAgent = {
  /**
   * Execute an agent with context
   */
  async execute(agentId: number, context: Record<string, any>) {
    const response = await axios.post(
      `${IRIS_API_URL}/chat/execute`,
      {
        agentId,
        query: 'Run scheduled task',
        bloqId: 40, // Optional knowledge base
        context, // Pass client_id, current_date, etc.
      },
      {
        headers: {
          'Authorization': `Bearer ${IRIS_API_KEY}`,
          'Content-Type': 'application/json',
        },
      }
    );

    return response.data;
  },

  /**
   * Check agent execution status
   */
  async getStatus(workflowId: string) {
    const response = await axios.get(
      `${IRIS_API_URL}/chat/status/${workflowId}`,
      {
        headers: {
          'Authorization': `Bearer ${IRIS_API_KEY}`,
        },
      }
    );

    return response.data;
  },

  /**
   * Get agent execution history
   */
  async getHistory(agentId: number, limit = 20) {
    const response = await axios.get(
      `${IRIS_API_URL}/chat/history`,
      {
        params: { agentId, limit },
        headers: {
          'Authorization': `Bearer ${IRIS_API_KEY}`,
        },
      }
    );

    return response.data;
  },
};
```

**Usage in React Component:**

```tsx
// src/components/PhoneNumbers/NumberOptimizer.tsx
import React, { useState } from 'react';
import { irisAgent } from '@/services/irisAgentService';

export const NumberOptimizer: React.FC = () => {
  const [isRunning, setIsRunning] = useState(false);
  const [result, setResult] = useState<any>(null);

  const handleRunAgent = async () => {
    setIsRunning(true);

    try {
      // Execute agent with context
      const execution = await irisAgent.execute(500, {
        client_id: 'client_123',
        current_date: new Date().toISOString(),
      });

      // Poll for status
      const workflowId = execution.workflow_id;
      let status = await irisAgent.getStatus(workflowId);

      while (status.status === 'running') {
        await new Promise(resolve => setTimeout(resolve, 2000));
        status = await irisAgent.getStatus(workflowId);
      }

      setResult(status);
    } catch (error) {
      console.error('Agent execution failed:', error);
    } finally {
      setIsRunning(false);
    }
  };

  return (
    <div className="p-4">
      <h2>Phone Number Optimizer</h2>
      
      <button
        onClick={handleRunAgent}
        disabled={isRunning}
        className="btn btn-primary"
      >
        {isRunning ? 'Running Agent...' : 'Run Unused Number Check'}
      </button>

      {result && (
        <div className="mt-4 p-4 bg-gray-100 rounded">
          <h3>Result:</h3>
          <pre>{JSON.stringify(result, null, 2)}</pre>
        </div>
      )}
    </div>
  );
};
```

### Option 2: Server-Side Proxy (More Secure)

If you don't want to expose IRIS API keys in React:

**Backend API (Node.js/Express):**

```typescript
// server/routes/agents.ts
import express from 'express';
import { IRIS } from '@iris-ai/sdk'; // Hypothetical JS SDK

const router = express.Router();
const iris = new IRIS({
  apiKey: process.env.IRIS_API_KEY,
  userId: process.env.IRIS_USER_ID,
});

router.post('/agents/:agentId/execute', async (req, res) => {
  const { agentId } = req.params;
  const { context } = req.body;

  try {
    const result = await iris.chat.execute({
      agentId: parseInt(agentId),
      query: 'Run scheduled task',
      context,
    });

    res.json(result);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

export default router;
```

**React calls your backend:**

```typescript
// Frontend
const response = await fetch('/api/agents/500/execute', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    context: {
      client_id: 'client_123',
      current_date: new Date().toISOString(),
    },
  }),
});

const result = await response.json();
```

---

## Phase 4: Orchestration Dashboard

**What Dima Wants to See:**

```
╔═══════════════════════════════════════════════════════════╗
║           Agent Orchestration Dashboard                   ║
╠═══════════════════════════════════════════════════════════╣
║                                                            ║
║  Today's Summary (Dec 26, 2024)                           ║
║  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━  ║
║                                                            ║
║  🤖 Total Agents: 3                                       ║
║  ▶️  Executions: 150                                      ║
║  ✅ Succeeded: 100 (66.7%)                                ║
║  ⏱️  Timed Out: 30 (20%)                                  ║
║  ❌ Failed: 20 (13.3%)                                    ║
║  🔄 Retried: 15                                           ║
║                                                            ║
║  Agent Breakdown                                          ║
║  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━  ║
║                                                            ║
║  1. Unused Number Releaser (#500)                         ║
║     Status: ✅ Running                                    ║
║     Last Run: 2 hours ago                                 ║
║     Success Rate: 95% (19/20 runs today)                  ║
║     Avg Duration: 12.3s                                   ║
║     Actions Taken: Released 45 numbers                    ║
║                                                            ║
║  2. Low Usage Detector (#501)                             ║
║     Status: ⏱️  Timeout                                   ║
║     Last Run: 1 hour ago (timed out after 5min)           ║
║     Success Rate: 60% (12/20 runs today)                  ║
║     Avg Duration: 45.2s                                   ║
║     [View Logs] [Retry]                                   ║
║                                                            ║
║  3. Tag-Based Optimizer (#502)                            ║
║     Status: ✅ Completed                                  ║
║     Last Run: 30 mins ago                                 ║
║     Success Rate: 100% (5/5 runs today)                   ║
║     Avg Duration: 8.1s                                    ║
║                                                            ║
╚═══════════════════════════════════════════════════════════╝
```

**API Endpoints Needed:**

```bash
# Get dashboard summary
GET /api/agents/dashboard/summary
GET /api/agents/dashboard/summary?date=2024-12-26

# Get agent execution history
GET /api/agents/{agentId}/executions?limit=100&status=failed

# Get metrics
GET /api/agents/metrics?start_date=2024-12-20&end_date=2024-12-26
```

**Response Schema:**

```typescript
interface DashboardSummary {
  date: string;
  totals: {
    agents: number;
    executions: number;
    succeeded: number;
    failed: number;
    timedOut: number;
    retried: number;
  };
  agents: Array<{
    id: number;
    name: string;
    status: 'running' | 'completed' | 'failed' | 'timeout';
    lastRun: string;
    successRate: number;
    avgDuration: number;
    executionsToday: number;
    actionsSummary: string; // e.g., "Released 45 numbers"
  }>;
}
```

---

## Phase 5: Testing Checklist

### ✅ **Test 1: Custom Function Integration**

```bash
# Test that agent can call Dima's API
./bin/iris chat 500 "Fetch all phone numbers for client_123"

# Expected: Agent calls fetchNumbers() and returns list
```

### ✅ **Test 2: Scheduled Execution**

```bash
# Verify schedule is set
./bin/iris sdk:call agents.getSchedule 500

# Manually trigger scheduled task
./bin/iris sdk:call agents.runScheduledTask 500 0

# Check execution log
./bin/iris sdk:call agents.getExecutionHistory 500
```

### ✅ **Test 3: Multi-Tenant Context**

```bash
# Pass client_id in context
./bin/iris chat 500 "Run workflow" --context='{"client_id":"client_123"}'

# Verify agent uses correct client_id in API calls
```

### ✅ **Test 4: React Integration**

```typescript
// In React app
const result = await irisAgent.execute(500, {
  client_id: 'client_123',
  current_date: new Date().toISOString(),
});

console.log('Agent result:', result);
```

### ✅ **Test 5: Error Handling**

```bash
# Simulate API timeout
# Verify agent retries 3 times
# Verify timeout is logged in dashboard
```

### ✅ **Test 6: Dashboard Visibility**

```bash
# Check dashboard shows agent executions
GET /api/agents/dashboard/summary

# Verify metrics are accurate
```

---

## Next Steps

1. **Alex:** Set up Dima's IRIS account
2. **Dima:** Share phone API endpoints + auth credentials
3. **Together:** Define custom functions YAML spec
4. **Alex:** Register functions in IRIS agent
5. **Together:** Test agent execution via CLI
6. **Dima:** Integrate React app → IRIS API
7. **Together:** Build orchestration dashboard API
8. **Test:** Run POC end-to-end

---

## Questions for Dima

1. What is the base URL for your phone API? (e.g., `https://phone-api.com`)
2. What auth method does your API use? (Bearer token, API key, OAuth)
3. What are the exact endpoint paths for:
   - Fetch numbers: `GET /api/numbers`?
   - Release number: `POST /api/numbers/{id}/release`?
   - Get call history: `GET /api/numbers/{id}/calls`?
4. Do you want agents to run on schedule only, or also on-demand from React?
5. What timezone for scheduled tasks?
6. What should happen if an agent fails 3 times in a row?

---

## Estimated Timeline

- **Week 1:** Custom function setup + Agent creation (5-8 hours)
- **Week 2:** React integration + Testing (5-8 hours)
- **Week 3:** Dashboard API + Monitoring (8-10 hours)
- **Week 4:** Production deployment + Handoff (3-5 hours)

**Total POC: 20-30 hours**
