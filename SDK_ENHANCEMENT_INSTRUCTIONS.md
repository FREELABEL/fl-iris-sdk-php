# IRIS SDK Enhancement Instructions

## 🎯 Overview

Based on building an elderly care assistant, I've identified critical gaps between the REST API capabilities and SDK functionality. The API supports comprehensive agent configuration, but the SDK only exposes basic CRUD operations.

## 🚨 Original Pain Points Encountered

### 1. **Fragmented Setup Process**
- **Problem**: Had to run 4 separate PHP scripts to create one complete agent
- **Impact**: Complex deployment, high chance of errors, poor developer experience
- **Evidence**: Created `create-grandma-care-agent.php`, `setup-grandma-knowledge.php`, `setup-grandma-voice.php`, `create-grandma-daily-reminders.php`

### 2. **Scheduler Integration Unclear**
- **Problem**: Created schedules in knowledge base but no visibility into actual execution
- **Impact**: No confidence that recurring tasks (medication reminders) are actually firing
- **Evidence**: Workflows created but unclear connection to native scheduler

### 3. **API vs SDK Capability Gap**
- **Problem**: REST API supports unified agent configuration, SDK requires multiple separate calls
- **Impact**: Developers must manually reverse-engineer the API to use full features
- **Evidence**: PUT `/api/v1/users/{id}/bloqs/agents/{id}` supports complete config object

### 4. **No Template System**
- **Problem**: Every agent requires custom script development
- **Impact**: High barrier to entry, inconsistent implementations
- **Evidence**: Common use cases like elderly care require 500+ lines of custom code

## 📋 Required SDK Enhancements

### 1. **Unified Agent Creation Method**

**Current SDK:**
```php
$agent = $iris->agents->create([
    'name' => 'Grandma Helper',
    'prompt' => 'You are a caring assistant...'
]);
// Then 4 more separate scripts for setup
```

**Target SDK:**
```php
$agent = $iris->agents->createFromConfig([
    'name' => 'Grandma Helper',
    'type' => 'content',
    'initial_prompt' => 'You are a caring assistant...',
    'settings' => [
        'schedule' => [
            'enabled' => true,
            'timezone' => 'America/New_York',
            'recurring_tasks' => [
                [
                    'name' => 'Morning Medication',
                    'time' => '08:00',
                    'message' => 'Good morning! Time for your medications',
                    'channels' => ['voice', 'sms']
                ]
            ]
        ],
        'agentIntegrations' => [
            'gmail' => true,
            'google-calendar' => true,
            'slack' => false
        ],
        'enabledFunctions' => [
            'manageLeads' => true,
            'deepResearch' => false
        ]
    ]
]);
```

### 2. **Schedule Management API**

**Missing Methods to Add:**
```php
// Create recurring schedules
$iris->agents->schedule($agentId, [
    'task_name' => 'Evening Check-in',
    'cron' => '0 20 * * *',
    'message' => 'How was your day? Need anything?',
    'timezone' => 'America/New_York'
]);

// List all schedules
$schedules = $iris->agents->getSchedules($agentId);

// Update existing schedule
$iris->agents->updateSchedule($agentId, $scheduleId, $newConfig);

// Delete schedule
$iris->agents->deleteSchedule($agentId, $scheduleId);
```

### 3. **Integration Management**

**Missing Methods to Add:**
```php
// Get current integrations status
$integrations = $iris->agents->getIntegrations($agentId);

// Enable integrations
$iris->agents->setIntegrations($agentId, [
    'gmail' => true,
    'google-calendar' => true,
    'slack' => false
]);

// Test integration connectivity
$test = $iris->agents->testIntegration($agentId, 'gmail');
```

### 4. **Settings Management**

**Missing Methods to Add:**
```php
// Get full settings object
$settings = $iris->agents->getSettings($agentId);

// Bulk settings update
$iris->agents->updateSettings($agentId, [
    'responseMode' => 'balanced',
    'contextWindow' => '10',
    'memoryPersistence' => true,
    'communicationStyle' => 'professional'
]);

// Reset to defaults
$iris->agents->resetSettings($agentId);
```

### 5. **Template System**

**Priority Feature - Template-Based Agent Creation:**
```php
// Built-in templates
$templates = $iris->agents->listTemplates();
// Returns: ['elderly-care', 'customer-support', 'sales-assistant', 'research-agent']

// Create from template
$agent = $iris->agents->createFromTemplate('elderly-care', [
    'name' => 'Grandma Helper',
    'user_id' => 193,
    'customizations' => [
        'medication_times' => ['08:00', '12:00', '18:00', '21:00'],
        'family_contacts' => ['+1234567890'],
        'timezone' => 'America/New_York',
        'voice_settings' => [
            'language' => 'en-US',
            'speaking_rate' => 0.9
        ]
    ]
]);

// Custom template definition
$iris->agents->defineTemplate('health-monitor', [
    'description' => 'Health monitoring and medication reminder agent',
    'default_settings' => [...],
    'required_integrations' => ['google-calendar', 'gmail'],
    'prompt_template' => 'You are a health monitoring assistant...'
]);
```

## 🔧 Implementation Plan

### Phase 1: Core SDK Methods (High Priority)
1. **Implement `AgentsResource::createFromConfig()`**
   - Map to existing PUT `/users/{id}/bloqs/agents/{id}` endpoint
   - Support full settings object from API documentation
   
2. **Add Schedule Management Methods**
   - `schedule()`, `getSchedules()`, `updateSchedule()`, `deleteSchedule()`
   - Handle recurring tasks array structure
   
3. **Add Integration Management**
   - `getIntegrations()`, `setIntegrations()`, `testIntegration()`
   - Map to settings.agentIntegrations object

### Phase 2: Template System (Medium Priority)
1. **Template Definition API**
   - Create template storage mechanism
   - Define template structure (prompt, settings, integrations)
   
2. **Template Engine**
   - Implement `createFromTemplate()` method
   - Support customization overrides
   
3. **Built-in Templates**
   - elderly-care, customer-support, sales-assistant, research-agent
   - Include common configurations and prompts

### Phase 3: Quality of Life (Low Priority)
1. **Bulk Operations**
   - `updateSettings()`, `resetSettings()`
   - Batch integration updates
   
2. **Validation Helpers**
   - Validate schedule cron expressions
   - Check integration dependencies
   
3. **Migration Tools**
   - Upgrade existing agents to new format
   - Import/export configuration

## 📁 Files to Modify

### New Files to Create:
```
src/Resources/Agents/
├── AgentConfig.php                    # Configuration class
├── AgentTemplate.php                   # Template definition
├── AgentSchedule.php                   # Schedule management
├── AgentIntegration.php                # Integration management
└── Templates/
    ├── ElderlyCareTemplate.php
    ├── CustomerSupportTemplate.php
    └── SalesAssistantTemplate.php
```

### Files to Modify:
```
src/Resources/AgentsResource.php         # Add new methods
src/Resources/Agents/Agent.php         # Add config handling
tests/Unit/AgentsResourceTest.php        # Add new test cases
src/IRIS.php                         # Register new methods
```

## 🧪 Testing Requirements

### Unit Tests to Add:
```php
public function test_create_agent_from_full_config()
public function test_schedule_management()
public function test_integration_enable_disable()
public function test_template_based_creation()
public function test_recurring_task_execution()
public function test_timezone_handling()
```

### Integration Tests:
```php
public function test_medication_reminder_fires()
public function test_voice_integration_setup()
public function test_family_contact_workflow()
```

## 🚀 Production Readiness

### Queue System Questions to Verify:
1. **Schedule Execution**: How to verify recurring tasks are processed?
2. **Queue Monitoring**: Dashboard or API endpoint for queue health?
3. **Error Handling**: What happens if scheduled task fails?
4. **Scaling**: Performance with thousands of scheduled agents?
5. **Timezones**: How are timezones handled in queue processing?

### Monitoring Requirements:
- Queue depth metrics
- Task execution success/failure rates
- Schedule drift detection
- Integration health monitoring

## 💡 Quick Wins (Day 1)

Based on the existing API structure, these could be implemented immediately:

1. **Full Config Create** - Just expose existing PUT endpoint
2. **Schedule CRUD** - Map to settings.schedule object
3. **Integration Status** - Expose settings.agentIntegrations
4. **Basic Template** - Hardcode elderly-care template

## 📊 Success Metrics

After implementation, success would be:
- **50% reduction** in code needed to create common agents
- **Unified API** covering all REST endpoints
- **Template system** with 4+ built-in templates
- **Production-ready** scheduling with monitoring

## 🎯 Final Recommendation

**Priority Order:**
1. Implement full config agent creation (immediate impact)
2. Add schedule management (core functionality)  
3. Build template system (developer experience)
4. Add monitoring tools (production readiness)

The REST API is already powerful and comprehensive. The SDK just needs to expose what the backend already supports.