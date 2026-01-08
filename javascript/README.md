# IRIS JavaScript SDK

Client-side JavaScript/TypeScript wrapper for the IRIS AI Platform REST API. Perfect for React, Vue, Angular, and vanilla JS applications.

## Installation

### Option 1: Direct Include (Browser)

```html
<script src="https://cdn.jsdelivr.net/npm/@iris-ai/sdk@latest/dist/iris-sdk.min.js"></script>
<script>
  const iris = new IRIS.IRISClient({
    apiKey: 'your_api_key',
    userId: 193,
  });
</script>
```

### Option 2: NPM/Yarn (Coming Soon)

```bash
npm install @iris-ai/sdk
# or
yarn add @iris-ai/sdk
```

```javascript
import { IRISClient } from '@iris-ai/sdk';

const iris = new IRISClient({
  apiKey: 'your_api_key',
  userId: 193,
});
```

### Option 3: Local Development

```bash
# Copy the SDK file to your project
cp fl-docker-dev/sdk/php/javascript/iris-sdk.js src/lib/
```

```javascript
import { IRISClient } from './lib/iris-sdk.js';
```

---

## Quick Start

### Initialize SDK

```javascript
const iris = new IRISClient({
  apiKey: process.env.REACT_APP_IRIS_API_KEY,
  userId: 193,
  environment: 'production', // or 'local'
});
```

### Execute an Agent

```javascript
// Simple execution
const result = await iris.chat.execute({
  agentId: 500,
  query: 'Check for unused phone numbers',
  context: {
    client_id: 'client_123',
    current_date: new Date().toISOString(),
  },
});

console.log('Result:', result.summary);
```

### Execute with Progress Tracking

```javascript
const result = await iris.chat.execute(
  {
    agentId: 500,
    query: 'Analyze phone number usage',
    bloqId: 40, // Optional: knowledge base context
  },
  (status) => {
    console.log('Status:', status.status);
    console.log('Progress:', status.progress || 'N/A');
  }
);

console.log('Final result:', result);
```

---

## React Integration

### Example: Phone Number Optimizer Component

```tsx
// src/components/PhoneNumberOptimizer.tsx
import React, { useState } from 'react';
import { IRISClient } from '@iris-ai/sdk';

const iris = new IRISClient({
  apiKey: process.env.REACT_APP_IRIS_API_KEY!,
  userId: parseInt(process.env.REACT_APP_USER_ID!),
});

export const PhoneNumberOptimizer: React.FC = () => {
  const [isRunning, setIsRunning] = useState(false);
  const [progress, setProgress] = useState<string>('');
  const [result, setResult] = useState<any>(null);

  const handleRunAgent = async () => {
    setIsRunning(true);
    setProgress('Starting agent...');

    try {
      const result = await iris.chat.execute(
        {
          agentId: 500, // Unused Number Releaser
          query: 'Check for phone numbers with no calls in last 72 hours and auto-release them',
          context: {
            client_id: 'client_123',
            current_date: new Date().toISOString(),
          },
        },
        (status) => {
          setProgress(`Status: ${status.status} - ${status.message || ''}`);
        }
      );

      setResult(result);
      setProgress('Completed!');
    } catch (error) {
      console.error('Agent execution failed:', error);
      setProgress(`Error: ${error.message}`);
    } finally {
      setIsRunning(false);
    }
  };

  return (
    <div className="p-6 bg-white rounded-lg shadow">
      <h2 className="text-2xl font-bold mb-4">Phone Number Optimizer</h2>
      
      <button
        onClick={handleRunAgent}
        disabled={isRunning}
        className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:bg-gray-400"
      >
        {isRunning ? 'Running Agent...' : 'Run Unused Number Check'}
      </button>

      {progress && (
        <div className="mt-4 p-3 bg-gray-100 rounded">
          <p className="text-sm text-gray-700">{progress}</p>
        </div>
      )}

      {result && (
        <div className="mt-4 p-4 bg-green-50 rounded">
          <h3 className="font-semibold mb-2">Result:</h3>
          <pre className="text-sm overflow-auto">
            {JSON.stringify(result, null, 2)}
          </pre>
        </div>
      )}
    </div>
  );
};
```

### Example: Custom Hook

```typescript
// src/hooks/useIRISAgent.ts
import { useState, useCallback } from 'react';
import { IRISClient, IRISError } from '@iris-ai/sdk';

const iris = new IRISClient({
  apiKey: process.env.REACT_APP_IRIS_API_KEY!,
  userId: parseInt(process.env.REACT_APP_USER_ID!),
});

interface AgentExecutionState {
  isRunning: boolean;
  progress: string;
  result: any;
  error: string | null;
}

export const useIRISAgent = (agentId: number) => {
  const [state, setState] = useState<AgentExecutionState>({
    isRunning: false,
    progress: '',
    result: null,
    error: null,
  });

  const execute = useCallback(
    async (query: string, context?: Record<string, any>) => {
      setState({
        isRunning: true,
        progress: 'Starting...',
        result: null,
        error: null,
      });

      try {
        const result = await iris.chat.execute(
          {
            agentId,
            query,
            context,
          },
          (status) => {
            setState((prev) => ({
              ...prev,
              progress: `${status.status}: ${status.message || ''}`,
            }));
          }
        );

        setState({
          isRunning: false,
          progress: 'Completed',
          result,
          error: null,
        });

        return result;
      } catch (error) {
        const errorMessage = error instanceof IRISError 
          ? error.message 
          : 'Unknown error';

        setState({
          isRunning: false,
          progress: '',
          result: null,
          error: errorMessage,
        });

        throw error;
      }
    },
    [agentId]
  );

  return {
    ...state,
    execute,
  };
};
```

**Usage:**

```tsx
function MyComponent() {
  const agent = useIRISAgent(500);

  const handleCheck = async () => {
    await agent.execute('Check for unused numbers', {
      client_id: 'client_123',
    });
  };

  return (
    <div>
      <button onClick={handleCheck} disabled={agent.isRunning}>
        Run Check
      </button>
      {agent.progress && <p>{agent.progress}</p>}
      {agent.result && <pre>{JSON.stringify(agent.result, null, 2)}</pre>}
      {agent.error && <p className="error">{agent.error}</p>}
    </div>
  );
}
```

---

## API Reference

### IRISClient

#### Constructor

```typescript
new IRISClient(config: {
  apiKey: string;
  userId: number;
  environment?: 'production' | 'local';
  apiUrl?: string;
  flApiUrl?: string;
})
```

### Agents Resource

#### `agents.list(params?)`
List all agents.

```javascript
const agents = await iris.agents.list({
  search: 'recruiter',
  per_page: 20,
  page: 1,
});
```

#### `agents.get(agentId)`
Get agent by ID.

```javascript
const agent = await iris.agents.get(500);
```

#### `agents.create(data)`
Create new agent.

```javascript
const agent = await iris.agents.create({
  name: 'Phone Number Manager',
  initial_prompt: 'You manage phone numbers...',
  model: 'gpt-4o-mini',
});
```

#### `agents.patch(agentId, data)`
Update agent (partial update - **RECOMMENDED**).

```javascript
await iris.agents.patch(500, {
  initial_prompt: 'Updated instructions...',
});
```

#### `agents.update(agentId, data)`
Update agent (full update - overwrites all fields).

```javascript
await iris.agents.update(500, {
  name: 'Updated Name',
  initial_prompt: 'New prompt...',
  // ... all fields required
});
```

#### `agents.delete(agentId)`
Delete agent.

```javascript
await iris.agents.delete(500);
```

#### `agents.chat(agentId, messages)`
Simple chat with agent (no workflow tracking).

```javascript
const response = await iris.agents.chat(500, [
  { role: 'user', content: 'Hello!' },
]);
```

#### `agents.getSettings(agentId)`
Get agent settings.

```javascript
const settings = await iris.agents.getSettings(500);
```

#### `agents.updateSettings(agentId, settings)`
Update agent settings.

```javascript
await iris.agents.updateSettings(500, {
  responseMode: 'balanced',
  communicationStyle: 'professional',
});
```

#### `agents.getUrls(agentId)`
Get shareable URLs for agent.

```javascript
const urls = await iris.agents.getUrls(500);
// { simple, embed, public }
```

---

### Chat Resource

#### `chat.execute(params, progressCallback?)`
Execute agent with workflow tracking.

```javascript
const result = await iris.chat.execute(
  {
    agentId: 500,
    query: 'Run workflow',
    bloqId: 40, // Optional
    context: { client_id: '123' }, // Optional
  },
  (status) => {
    console.log('Progress:', status);
  }
);
```

#### `chat.start(params)`
Start agent workflow (async, returns immediately).

```javascript
const { workflow_id } = await iris.chat.start({
  agentId: 500,
  query: 'Process data',
});

// Poll manually
const status = await iris.chat.getStatus(workflow_id);
```

#### `chat.getStatus(workflowId)`
Get workflow status.

```javascript
const status = await iris.chat.getStatus('wf_123');
// { status: 'running' | 'completed' | 'failed', ... }
```

#### `chat.resume(workflowId, approval)`
Resume paused workflow (HITL approval).

```javascript
await iris.chat.resume('wf_123', {
  approved: true,
  comment: 'Looks good!',
});
```

#### `chat.history(params?)`
Get chat history.

```javascript
const history = await iris.chat.history({
  status: 'completed',
  per_page: 20,
});
```

#### `chat.stats()`
Get workflow statistics.

```javascript
const stats = await iris.chat.stats();
// { total_workflows, success_rate, ... }
```

---

### Bloqs Resource

#### `bloqs.list(params?)`
List knowledge bases.

```javascript
const bloqs = await iris.bloqs.list({ per_page: 20 });
```

#### `bloqs.get(bloqId)`
Get bloq by ID.

```javascript
const bloq = await iris.bloqs.get(40);
```

#### `bloqs.create(title, data?)`
Create bloq.

```javascript
const bloq = await iris.bloqs.create('Customer Support KB', {
  description: 'Support docs and FAQs',
});
```

#### `bloqs.addContent(bloqId, data)`
Add content to bloq.

```javascript
await iris.bloqs.addContent(40, {
  title: 'Refund Policy',
  content: 'Our refund policy...',
});
```

#### `bloqs.query(bloqId, question, topK?)`
Query bloq (RAG search).

```javascript
const results = await iris.bloqs.query(40, 'What is the refund policy?', 5);
```

---

### Leads Resource

#### `leads.search(params)`
Search leads.

```javascript
const leads = await iris.leads.search({
  bloq_id: 40,
  status: 'Won,Negotiation',
  search: 'john',
});
```

#### `leads.get(leadId)`
Get lead by ID.

```javascript
const lead = await iris.leads.get(412);
```

#### `leads.update(leadId, data)`
Update lead.

```javascript
await iris.leads.update(412, {
  status: 'Won',
  lead_type: 'client',
});
```

#### `leads.addNote(leadId, message)`
Add note to lead.

```javascript
await iris.leads.addNote(412, 'Meeting went well. Closing next week.');
```

#### `leads.deliverables(leadId)`
Access deliverables sub-resource.

```javascript
// List deliverables
const deliverables = await iris.leads.deliverables(412).list();

// Create deliverable
await iris.leads.deliverables(412).create({
  type: 'link',
  title: 'AI Agent',
  external_url: 'https://app.heyiris.io/agent/500',
  user_id: 193,
});

// Update deliverable
await iris.leads.deliverables(412).update(335, {
  title: 'Updated Title',
});

// Delete deliverable
await iris.leads.deliverables(412).delete(335);
```

#### `leads.tasks(leadId)`
Access tasks sub-resource.

```javascript
// List tasks
const tasks = await iris.leads.tasks(412).all();

// Create task
await iris.leads.tasks(412).create({
  title: 'Follow up call',
  status: 'incomplete',
  priority: 'high',
});

// Update task
await iris.leads.tasks(412).update(11, {
  status: 'complete',
});

// Delete task
await iris.leads.tasks(412).delete(11);
```

---

## Error Handling

```javascript
import { IRISError } from '@iris-ai/sdk';

try {
  const result = await iris.chat.execute({
    agentId: 500,
    query: 'Process data',
  });
} catch (error) {
  if (error instanceof IRISError) {
    console.error('IRIS Error:', {
      message: error.message,
      statusCode: error.statusCode,
      original: error.originalError,
    });
    
    if (error.statusCode === 401) {
      // Handle authentication error
    } else if (error.statusCode === 404) {
      // Handle not found
    }
  } else {
    console.error('Network error:', error);
  }
}
```

---

## Environment Configuration

### React (.env)

```bash
REACT_APP_IRIS_API_KEY=your_api_key_here
REACT_APP_USER_ID=193
REACT_APP_IRIS_ENV=production
```

### Next.js (.env.local)

```bash
NEXT_PUBLIC_IRIS_API_KEY=your_api_key_here
NEXT_PUBLIC_USER_ID=193
NEXT_PUBLIC_IRIS_ENV=production
```

### Vue (.env)

```bash
VUE_APP_IRIS_API_KEY=your_api_key_here
VUE_APP_USER_ID=193
VUE_APP_IRIS_ENV=production
```

---

## TypeScript Support (Coming Soon)

Full TypeScript definitions will be available in the NPM package:

```typescript
import { IRISClient, ChatExecuteParams, Agent, Lead } from '@iris-ai/sdk';

const iris = new IRISClient({ ... });

const result: WorkflowResult = await iris.chat.execute({ ... });
const agent: Agent = await iris.agents.get(500);
const lead: Lead = await iris.leads.get(412);
```

---

## Server-Side Usage (Node.js)

The SDK works in Node.js for server-side agent execution:

```javascript
// server.js (Express)
const { IRISClient } = require('@iris-ai/sdk');

const iris = new IRISClient({
  apiKey: process.env.IRIS_API_KEY,
  userId: 193,
});

app.post('/api/agents/:agentId/execute', async (req, res) => {
  try {
    const result = await iris.chat.execute({
      agentId: req.params.agentId,
      query: req.body.query,
      context: req.body.context,
    });
    
    res.json(result);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});
```

---

## Examples

See more examples in the `/examples` directory:
- `react-phone-manager/` - React phone number management with IRIS agents
- `vue-crm-dashboard/` - Vue CRM dashboard with lead management
- `vanilla-js-chat/` - Vanilla JS agent chat interface

---

## Support

- **Documentation:** https://docs.heyiris.io
- **GitHub:** https://github.com/iris-ai/sdk
- **Email:** support@heyiris.io

---

## License

MIT License - see LICENSE file for details.
