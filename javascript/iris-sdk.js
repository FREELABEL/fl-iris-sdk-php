/**
 * IRIS JavaScript SDK
 * 
 * Client-side wrapper for IRIS AI Platform REST API
 * Provides easy integration for React, Vue, Angular, and vanilla JS apps
 * 
 * @version 1.0.0
 * @author IRIS AI Platform
 */

class IRISClient {
  /**
   * Initialize IRIS SDK
   * 
   * @param {Object} config
   * @param {string} config.apiKey - Your IRIS API key
   * @param {number} config.userId - Your user ID
   * @param {string} [config.apiUrl] - IRIS API URL (default: production)
   * @param {string} [config.flApiUrl] - FL-API URL for leads/profiles (default: production)
   * @param {string} [config.environment='production'] - Environment: production or local
   */
  constructor(config) {
    if (!config.apiKey) {
      throw new Error('IRIS SDK: apiKey is required');
    }
    if (!config.userId) {
      throw new Error('IRIS SDK: userId is required');
    }

    this.apiKey = config.apiKey;
    this.userId = config.userId;
    this.environment = config.environment || 'production';

    // Production URLs
    this.apiUrl = config.apiUrl || 'https://heyiris.io';
    this.flApiUrl = config.flApiUrl || 'https://apiv2.heyiris.io';

    // Local development URLs
    if (this.environment === 'local') {
      this.apiUrl = config.apiUrl || 'https://local.iris.freelabel.net';
      this.flApiUrl = config.flApiUrl || 'https://local.raichu.freelabel.net';
    }

    // Initialize resource proxies
    this.agents = new AgentsResource(this);
    this.chat = new ChatResource(this);
    this.workflows = new WorkflowsResource(this);
    this.bloqs = new BloqsResource(this);
    this.leads = new LeadsResource(this);
  }

  /**
   * Make API request to IRIS or FL-API
   * Auto-routes based on endpoint pattern
   */
  async request(method, endpoint, data = null, options = {}) {
    // Determine which API to use based on endpoint
    const baseUrl = this._getBaseUrl(endpoint);
    const url = `${baseUrl}${endpoint}`;

    const config = {
      method: method.toUpperCase(),
      headers: {
        'Authorization': `Bearer ${this.apiKey}`,
        'Content-Type': 'application/json',
        'X-User-ID': this.userId.toString(),
        ...options.headers,
      },
    };

    if (data && method !== 'GET') {
      config.body = JSON.stringify(data);
    }

    try {
      const response = await fetch(url, config);
      
      if (!response.ok) {
        const error = await response.json().catch(() => ({}));
        throw new IRISError(
          error.message || `HTTP ${response.status}: ${response.statusText}`,
          response.status,
          error
        );
      }

      return await response.json();
    } catch (error) {
      if (error instanceof IRISError) {
        throw error;
      }
      throw new IRISError(`Network error: ${error.message}`, 0, error);
    }
  }

  /**
   * Route requests to correct API based on endpoint pattern
   */
  _getBaseUrl(endpoint) {
    const flApiPatterns = [
      '/leads',
      '/deliverables',
      '/profile',
      '/profiles',
      '/services',
      '/users/',
    ];

    const usesFlApi = flApiPatterns.some(pattern => endpoint.startsWith(pattern));
    return usesFlApi ? this.flApiUrl : this.apiUrl;
  }
}

/**
 * Custom error class for IRIS SDK errors
 */
class IRISError extends Error {
  constructor(message, statusCode, originalError = null) {
    super(message);
    this.name = 'IRISError';
    this.statusCode = statusCode;
    this.originalError = originalError;
  }
}

/**
 * Agents Resource - Manage AI agents
 */
class AgentsResource {
  constructor(client) {
    this.client = client;
  }

  /**
   * List all agents
   * @param {Object} params - Query parameters (search, per_page, page)
   */
  async list(params = {}) {
    const query = new URLSearchParams(params).toString();
    return await this.client.request('GET', `/iris/agents?${query}`);
  }

  /**
   * Get agent by ID
   */
  async get(agentId) {
    return await this.client.request('GET', `/iris/agents/${agentId}`);
  }

  /**
   * Create new agent
   * @param {Object} data - Agent configuration
   */
  async create(data) {
    return await this.client.request('POST', '/iris/agents', data);
  }

  /**
   * Update agent (full update)
   * @param {number} agentId
   * @param {Object} data - Complete agent configuration
   */
  async update(agentId, data) {
    return await this.client.request('PUT', `/iris/agents/${agentId}`, data);
  }

  /**
   * Patch agent (partial update) - RECOMMENDED
   * @param {number} agentId
   * @param {Object} data - Fields to update
   */
  async patch(agentId, data) {
    return await this.client.request('PATCH', `/iris/agents/${agentId}`, data);
  }

  /**
   * Delete agent
   */
  async delete(agentId) {
    return await this.client.request('DELETE', `/iris/agents/${agentId}`);
  }

  /**
   * Chat with agent (simple, blocking)
   * @param {number} agentId
   * @param {Array} messages - Chat messages [{role: 'user', content: '...'}]
   */
  async chat(agentId, messages) {
    return await this.client.request('POST', `/iris/agents/${agentId}/chat`, { messages });
  }

  /**
   * Get agent settings
   */
  async getSettings(agentId) {
    return await this.client.request('GET', `/iris/agents/${agentId}/settings`);
  }

  /**
   * Update agent settings (partial)
   */
  async updateSettings(agentId, settings) {
    return await this.client.request('PATCH', `/iris/agents/${agentId}/settings`, settings);
  }

  /**
   * Get agent URLs (embed, public)
   */
  async getUrls(agentId) {
    const agent = await this.get(agentId);
    const baseUrl = 'https://app.heyiris.io';
    
    return {
      simple: `${baseUrl}/agent/simple/${agentId}${agent.bloq_id ? `?bloq=${agent.bloq_id}` : ''}`,
      embed: `${baseUrl}/agent/simple/${agentId}${agent.bloq_id ? `?bloq=${agent.bloq_id}` : ''}`,
      public: agent.public_slug ? `${baseUrl}/agent/${agent.public_slug}` : null,
    };
  }
}

/**
 * Chat Resource - Execute agents with workflows
 */
class ChatResource {
  constructor(client) {
    this.client = client;
  }

  /**
   * Execute agent workflow (blocking with optional polling)
   * @param {Object} params
   * @param {number} params.agentId - Agent ID
   * @param {string} params.query - User query/instruction
   * @param {number} [params.bloqId] - Knowledge base ID (for RAG context)
   * @param {Object} [params.context] - Additional context (client_id, current_date, etc.)
   * @param {function} [progressCallback] - Optional progress callback
   */
  async execute(params, progressCallback = null) {
    const { agentId, query, bloqId, context } = params;

    // Start workflow
    const startResponse = await this.client.request('POST', '/chat/execute', {
      agentId,
      query,
      bloqId,
      context,
    });

    const workflowId = startResponse.workflow_id || startResponse.id;

    // Poll for completion if callback provided
    if (progressCallback) {
      return await this._pollStatus(workflowId, progressCallback);
    }

    // Otherwise return start response
    return startResponse;
  }

  /**
   * Start agent workflow (async, returns immediately)
   */
  async start(params) {
    return await this.client.request('POST', '/chat/execute', params);
  }

  /**
   * Get workflow status
   */
  async getStatus(workflowId) {
    return await this.client.request('GET', `/chat/status/${workflowId}`);
  }

  /**
   * Resume paused workflow (for HITL approval)
   */
  async resume(workflowId, approval) {
    return await this.client.request('POST', `/chat/resume/${workflowId}`, approval);
  }

  /**
   * Get chat history
   */
  async history(params = {}) {
    const query = new URLSearchParams(params).toString();
    return await this.client.request('GET', `/chat/history?${query}`);
  }

  /**
   * Get workflow statistics
   */
  async stats() {
    return await this.client.request('GET', '/chat/stats');
  }

  /**
   * Poll workflow status until completion
   * @private
   */
  async _pollStatus(workflowId, callback, interval = 1000) {
    while (true) {
      const status = await this.getStatus(workflowId);
      
      if (callback) {
        callback(status);
      }

      if (status.status === 'completed') {
        return status;
      }

      if (status.status === 'failed') {
        throw new IRISError(status.error || 'Workflow failed', 500, status);
      }

      await new Promise(resolve => setTimeout(resolve, interval));
    }
  }
}

/**
 * Workflows Resource - Multi-step workflows
 */
class WorkflowsResource {
  constructor(client) {
    this.client = client;
  }

  /**
   * Execute workflow
   */
  async execute(params) {
    return await this.client.request('POST', '/workflows/execute', params);
  }

  /**
   * Get workflow status
   */
  async getStatus(workflowId) {
    return await this.client.request('GET', `/workflows/${workflowId}/status`);
  }
}

/**
 * Bloqs Resource - Knowledge bases
 */
class BloqsResource {
  constructor(client) {
    this.client = client;
  }

  /**
   * List bloqs
   */
  async list(params = {}) {
    const query = new URLSearchParams(params).toString();
    return await this.client.request('GET', `/bloqs?${query}`);
  }

  /**
   * Get bloq by ID
   */
  async get(bloqId) {
    return await this.client.request('GET', `/bloqs/${bloqId}`);
  }

  /**
   * Create bloq
   */
  async create(title, data = {}) {
    return await this.client.request('POST', '/bloqs', { title, ...data });
  }

  /**
   * Add content to bloq
   */
  async addContent(bloqId, data) {
    return await this.client.request('POST', `/bloqs/${bloqId}/content`, data);
  }

  /**
   * Query bloq knowledge base (RAG)
   */
  async query(bloqId, question, topK = 5) {
    return await this.client.request('POST', `/bloqs/${bloqId}/query`, { question, topK });
  }
}

/**
 * Leads Resource - CRM lead management
 */
class LeadsResource {
  constructor(client) {
    this.client = client;
  }

  /**
   * Search leads
   */
  async search(params) {
    const query = new URLSearchParams(params).toString();
    return await this.client.request('GET', `/leads/search?${query}`);
  }

  /**
   * Get lead by ID
   */
  async get(leadId) {
    return await this.client.request('GET', `/leads/${leadId}`);
  }

  /**
   * Update lead
   */
  async update(leadId, data) {
    return await this.client.request('PUT', `/leads/${leadId}`, data);
  }

  /**
   * Add note to lead
   */
  async addNote(leadId, message) {
    return await this.client.request('POST', `/leads/${leadId}/notes`, { message });
  }

  /**
   * Deliverables sub-resource
   */
  deliverables(leadId) {
    return new LeadDeliverablesResource(this.client, leadId);
  }

  /**
   * Tasks sub-resource
   */
  tasks(leadId) {
    return new LeadTasksResource(this.client, leadId);
  }
}

/**
 * Lead Deliverables Sub-Resource
 */
class LeadDeliverablesResource {
  constructor(client, leadId) {
    this.client = client;
    this.leadId = leadId;
  }

  async list() {
    return await this.client.request('GET', `/leads/${this.leadId}/deliverables`);
  }

  async create(data) {
    return await this.client.request('POST', `/leads/${this.leadId}/deliverables`, data);
  }

  async update(deliverableId, data) {
    return await this.client.request('PUT', `/leads/${this.leadId}/deliverables/${deliverableId}`, data);
  }

  async delete(deliverableId) {
    return await this.client.request('DELETE', `/leads/${this.leadId}/deliverables/${deliverableId}`);
  }
}

/**
 * Lead Tasks Sub-Resource
 */
class LeadTasksResource {
  constructor(client, leadId) {
    this.client = client;
    this.leadId = leadId;
  }

  async all() {
    return await this.client.request('GET', `/leads/${this.leadId}/tasks`);
  }

  async create(data) {
    return await this.client.request('POST', `/leads/${this.leadId}/tasks`, data);
  }

  async update(taskId, data) {
    return await this.client.request('PUT', `/leads/${this.leadId}/tasks/${taskId}`, data);
  }

  async delete(taskId) {
    return await this.client.request('DELETE', `/leads/${this.leadId}/tasks/${taskId}`);
  }
}

// Export for different module systems
if (typeof module !== 'undefined' && module.exports) {
  // CommonJS (Node.js)
  module.exports = { IRISClient, IRISError };
} else if (typeof define === 'function' && define.amd) {
  // AMD (RequireJS)
  define([], function() {
    return { IRISClient, IRISError };
  });
} else {
  // Browser globals
  window.IRIS = { IRISClient, IRISError };
}
