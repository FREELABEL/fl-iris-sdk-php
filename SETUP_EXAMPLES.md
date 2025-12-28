# IRIS SDK - Agent Setup Examples

Complete examples for creating and configuring AI agents using the IRIS SDK.

---

## Table of Contents

- [Quick Start](#quick-start)
- [Template-Based Creation](#template-based-creation)
- [Custom Configuration](#custom-configuration)
- [Schedule Management](#schedule-management)
- [Integration Management](#integration-management)
- [Complete Examples](#complete-examples)

---

## Quick Start

### Create Agent from Template (Easiest)

```php
<?php
require_once 'vendor/autoload.php';

$iris = new IRIS\SDK\IRIS([
    'api_key' => $_ENV['IRIS_API_KEY'],
    'user_id' => $_ENV['IRIS_USER_ID'],
]);

// Create elderly care assistant in one line
$agent = $iris->agents->createFromTemplate('elderly-care', [
    'name' => 'Grandma Helper'
]);

echo "Agent created! ID: {$agent->id}\n";
```

---

## Template-Based Creation

### Available Templates

```php
// List all available templates
$templates = $iris->agents->listTemplates();
print_r($templates);

/* Output:
[
    'elderly-care' => [
        'name' => 'Elderly Care Assistant',
        'description' => 'Caring assistant for elderly individuals...',
        'icon' => 'fas fa-heart'
    ],
    'customer-support' => [...],
    'sales-assistant' => [...],
    'research-agent' => [...]
]
*/
```

### 1. Elderly Care Assistant

Perfect for: Medication reminders, safety monitoring, companionship

```php
$agent = $iris->agents->createFromTemplate('elderly-care', [
    'name' => 'Grandma\'s Helper',
    'description' => 'Personal care assistant for grandmother',
    'settings' => [
        'schedule' => [
            'timezone' => 'America/Chicago',
            'recurring_tasks' => [
                [
                    'name' => 'Morning Medication',
                    'time' => '08:00',
                    'message' => 'Good morning! Time for your medications'
                ],
                [
                    'name' => 'Lunch Medication',
                    'time' => '12:00',
                    'message' => 'Lunchtime medications'
                ],
                [
                    'name' => 'Evening Medication',
                    'time' => '18:00',
                    'message' => 'Evening medications'
                ],
                [
                    'name' => 'Bedtime Medication',
                    'time' => '21:00',
                    'message' => 'Time for bedtime medications'
                ]
            ]
        ]
    ]
]);
```

### 2. Customer Support Assistant

Perfect for: Support tickets, FAQs, troubleshooting

```php
$agent = $iris->agents->createFromTemplate('customer-support', [
    'name' => 'Support Bot',
    'description' => 'Handles customer inquiries and support tickets',
    'settings' => [
        'agentIntegrations' => [
            'gmail' => true,
            'slack' => true,
            'google-drive' => true
        ],
        'enabledFunctions' => [
            'manageLeads' => true,
            'deepResearch' => true
        ]
    ]
]);
```

### 3. Sales Assistant

Perfect for: Lead qualification, scheduling demos, follow-ups

```php
$agent = $iris->agents->createFromTemplate('sales-assistant', [
    'name' => 'Sales Pro',
    'description' => 'Qualifies leads and schedules meetings',
    'settings' => [
        'agentIntegrations' => [
            'gmail' => true,
            'google-calendar' => true,
            'slack' => true
        ],
        'enabledFunctions' => [
            'manageLeads' => true,
            'marketResearch' => true
        ]
    ]
]);
```

### 4. Research Agent

Perfect for: Market research, competitive analysis, data gathering

```php
$agent = $iris->agents->createFromTemplate('research-agent', [
    'name' => 'Research Bot',
    'description' => 'Conducts deep research and analysis',
    'settings' => [
        'enabledFunctions' => [
            'deepResearch' => true,
            'marketResearch' => true
        ],
        'responseMode' => 'detailed'
    ]
]);
```

---

## Custom Configuration

### Full Custom Agent Setup

```php
$agent = $iris->agents->createFromConfig([
    'name' => 'My Custom Agent',
    'type' => 'content',
    'icon' => 'fas fa-robot',
    'description' => 'A custom agent for specific needs',
    'initial_prompt' => <<<PROMPT
You are a professional assistant specialized in [YOUR DOMAIN].

Your responsibilities:
- [Responsibility 1]
- [Responsibility 2]
- [Responsibility 3]

Communication style:
- Professional and friendly
- Clear and concise
- Action-oriented
PROMPT,
    'config' => [
        'model' => 'gpt-4o-mini',
        'temperature' => 0.7,
        'maxTokens' => 2048
    ],
    'settings' => [
        'schedule' => [
            'enabled' => true,
            'timezone' => 'America/New_York',
            'recurring_tasks' => [
                [
                    'name' => 'Daily Report',
                    'time' => '09:00',
                    'message' => 'Generating daily report'
                ]
            ]
        ],
        'agentIntegrations' => [
            'gmail' => true,
            'google-calendar' => false,
            'slack' => true,
            'google-drive' => true
        ],
        'enabledFunctions' => [
            'manageLeads' => true,
            'deepResearch' => false,
            'marketResearch' => true
        ],
        'responseMode' => 'balanced',
        'communicationStyle' => 'professional',
        'responseLength' => 'concise',
        'memoryPersistence' => true,
        'useKnowledgeBase' => true
    ]
]);
```

---

## Schedule Management

### Get Current Schedule

```php
$schedule = $iris->agents->getSchedule($agentId);
print_r($schedule);

/* Output:
[
    'enabled' => true,
    'timezone' => 'America/New_York',
    'recurring_tasks' => [
        ['name' => 'Morning Check', 'time' => '08:00', 'message' => '...'],
        ['name' => 'Evening Check', 'time' => '20:00', 'message' => '...']
    ]
]
*/
```

### Set Complete Schedule

```php
$agent = $iris->agents->setSchedule($agentId, [
    'enabled' => true,
    'timezone' => 'America/Los_Angeles',
    'frequency' => 'always_on',
    'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
    'active_hours' => [
        'start' => '09:00',
        'end' => '17:00'
    ],
    'recurring_tasks' => [
        [
            'name' => 'Morning Standup',
            'time' => '09:00',
            'message' => 'Daily standup reminder'
        ],
        [
            'name' => 'End of Day Report',
            'time' => '17:00',
            'message' => 'Generate end of day report'
        ]
    ]
]);
```

### Add Single Task

```php
// Add a task without overwriting existing tasks
$agent = $iris->agents->addScheduledTask($agentId, [
    'name' => 'Lunch Break Reminder',
    'time' => '12:00',
    'message' => 'Time for lunch!'
]);
```

### Remove Task

```php
// Remove a specific task by name
$agent = $iris->agents->removeScheduledTask($agentId, 'Lunch Break Reminder');
```

### Medication Reminder Example

```php
// Perfect for elderly care or health monitoring
$medicationTimes = [
    ['name' => 'Morning Meds', 'time' => '08:00', 'message' => 'Time for morning medications'],
    ['name' => 'Noon Meds', 'time' => '12:00', 'message' => 'Lunchtime medications'],
    ['name' => 'Evening Meds', 'time' => '18:00', 'message' => 'Evening medications'],
    ['name' => 'Bedtime Meds', 'time' => '21:00', 'message' => 'Bedtime medications']
];

$agent = $iris->agents->setSchedule($agentId, [
    'enabled' => true,
    'timezone' => 'America/Chicago',
    'recurring_tasks' => $medicationTimes
]);
```

---

## Integration Management

### Get Integration Status

```php
$integrations = $iris->agents->getIntegrations($agentId);
print_r($integrations);

/* Output:
[
    'gmail' => true,
    'slack' => false,
    'google-calendar' => true,
    'google-drive' => false
]
*/
```

### Enable Multiple Integrations

```php
$agent = $iris->agents->setIntegrations($agentId, [
    'gmail' => true,
    'google-calendar' => true,
    'slack' => true,
    'google-drive' => true,
    'github' => false,
    'trello' => false
]);
```

### Enable Single Integration

```php
// Enable Gmail
$agent = $iris->agents->enableIntegration($agentId, 'gmail');

// Enable Google Calendar
$agent = $iris->agents->enableIntegration($agentId, 'google-calendar');
```

### Disable Integration

```php
$agent = $iris->agents->disableIntegration($agentId, 'slack');
```

### Available Integrations

```php
// All available integrations:
$availableIntegrations = [
    'gmail',
    'slack',
    'github',
    'trello',
    'discord',
    'google-drive',
    'google-calendar'
];
```

---

## Function Management

### Get Enabled Functions

```php
$functions = $iris->agents->getEnabledFunctions($agentId);
print_r($functions);

/* Output:
[
    'manageLeads' => true,
    'deepResearch' => false,
    'marketResearch' => true,
    'travelAgent' => false
]
*/
```

### Set Enabled Functions

```php
$agent = $iris->agents->setEnabledFunctions($agentId, [
    'manageLeads' => true,
    'deepResearch' => true,
    'marketResearch' => false,
    'staffManagement' => false,
    'eventCoordination' => true,
    'businessProposal' => false,
    'brandAnalytics' => true,
    'travelAgent' => false
]);
```

---

## Complete Examples

### Example 1: Complete Elderly Care Setup

```php
<?php
require_once 'vendor/autoload.php';

use IRIS\SDK\IRIS;

// Initialize SDK
$iris = new IRIS([
    'api_key' => $_ENV['IRIS_API_KEY'],
    'user_id' => $_ENV['IRIS_USER_ID'],
]);

// Create agent from template
$agent = $iris->agents->createFromTemplate('elderly-care', [
    'name' => 'Grandma\'s Helper',
    'description' => 'Personal care assistant for 85-year-old grandmother',
    'settings' => [
        'schedule' => [
            'timezone' => 'America/Chicago',
            'recurring_tasks' => [
                ['name' => 'Morning Meds', 'time' => '08:00'],
                ['name' => 'Lunch Meds', 'time' => '12:00'],
                ['name' => 'Water Break', 'time' => '15:00'],
                ['name' => 'Evening Meds', 'time' => '18:00'],
                ['name' => 'Bedtime Meds', 'time' => '21:00']
            ]
        ]
    ]
]);

// Enable Gmail for family notifications
$iris->agents->enableIntegration($agent->id, 'gmail');

// Enable Google Calendar for appointments
$iris->agents->enableIntegration($agent->id, 'google-calendar');

// Test the agent
$response = $iris->agents->chat($agent->id, [
    ['role' => 'user', 'content' => 'What should I be doing right now?']
]);

echo "Agent created: {$agent->id}\n";
echo "Response: {$response->content}\n";
```

### Example 2: Customer Support with Knowledge Base

```php
<?php
require_once 'vendor/autoload.php';

use IRIS\SDK\IRIS;

$iris = new IRIS([
    'api_key' => $_ENV['IRIS_API_KEY'],
    'user_id' => $_ENV['IRIS_USER_ID'],
]);

// Create support agent
$agent = $iris->agents->createFromTemplate('customer-support', [
    'name' => 'Support Bot',
    'description' => '24/7 customer support assistant'
]);

// Upload knowledge base files
$knowledgeBaseId = 40; // Your knowledge base ID
$iris->agents->uploadAndAttachFiles($agent->id, [
    '/path/to/product-docs.pdf',
    '/path/to/faq.md',
    '/path/to/troubleshooting-guide.pdf'
], $knowledgeBaseId);

// Enable integrations
$iris->agents->setIntegrations($agent->id, [
    'gmail' => true,
    'slack' => true,
    'google-drive' => true
]);

// Enable functions
$iris->agents->setEnabledFunctions($agent->id, [
    'manageLeads' => true,
    'deepResearch' => true
]);

echo "Support agent ready: {$agent->id}\n";
```

### Example 3: Sales Assistant with Scheduling

```php
<?php
require_once 'vendor/autoload.php';

use IRIS\SDK\IRIS;

$iris = new IRIS([
    'api_key' => $_ENV['IRIS_API_KEY'],
    'user_id' => $_ENV['IRIS_USER_ID'],
]);

// Create sales assistant
$agent = $iris->agents->createFromTemplate('sales-assistant', [
    'name' => 'Sales Pro',
    'description' => 'Lead qualification and meeting scheduling'
]);

// Set business hours schedule
$iris->agents->setSchedule($agent->id, [
    'enabled' => true,
    'timezone' => 'America/New_York',
    'frequency' => 'business_hours',
    'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
    'active_hours' => [
        'start' => '09:00',
        'end' => '17:00'
    ],
    'recurring_tasks' => [
        ['name' => 'Morning Lead Review', 'time' => '09:00'],
        ['name' => 'Midday Follow-ups', 'time' => '13:00'],
        ['name' => 'End of Day Summary', 'time' => '17:00']
    ]
]);

// Enable calendar and email
$iris->agents->setIntegrations($agent->id, [
    'gmail' => true,
    'google-calendar' => true,
    'slack' => true
]);

// Enable lead management
$iris->agents->setEnabledFunctions($agent->id, [
    'manageLeads' => true,
    'marketResearch' => true
]);

echo "Sales agent ready: {$agent->id}\n";
```

### Example 4: Research Agent with Deep Research

```php
<?php
require_once 'vendor/autoload.php';

use IRIS\SDK\IRIS;

$iris = new IRIS([
    'api_key' => $_ENV['IRIS_API_KEY'],
    'user_id' => $_ENV['IRIS_USER_ID'],
]);

// Create research agent with GPT-4 for better analysis
$agent = $iris->agents->createFromConfig([
    'name' => 'Research Bot',
    'type' => 'content',
    'description' => 'Deep research and competitive analysis',
    'initial_prompt' => 'You are a research assistant specialized in gathering and analyzing information...',
    'config' => [
        'model' => 'gpt-4o', // Use GPT-4 for complex research
        'temperature' => 0.3, // Lower temperature for factual accuracy
        'maxTokens' => 4096
    ],
    'settings' => [
        'agentIntegrations' => [
            'google-drive' => true
        ],
        'enabledFunctions' => [
            'deepResearch' => true,
            'marketResearch' => true
        ],
        'responseMode' => 'detailed',
        'responseLength' => 'detailed'
    ]
]);

// Execute research query
$workflow = $iris->agents->multiStep($agent->id, 
    'Research the top 5 competitors in the AI chatbot space and create a comparison report'
);

echo "Research agent ready: {$agent->id}\n";
echo "Workflow started: {$workflow->id}\n";
```

### Example 5: Update Existing Agent

```php
<?php
require_once 'vendor/autoload.php';

use IRIS\SDK\IRIS;

$iris = new IRIS([
    'api_key' => $_ENV['IRIS_API_KEY'],
    'user_id' => $_ENV['IRIS_USER_ID'],
]);

$agentId = 335; // Existing agent ID

// Add new scheduled tasks to existing agent
$iris->agents->addScheduledTask($agentId, [
    'name' => 'Weekly Report',
    'time' => '09:00',
    'message' => 'Generate weekly summary report'
]);

// Enable new integrations
$iris->agents->enableIntegration($agentId, 'slack');
$iris->agents->enableIntegration($agentId, 'google-drive');

// Update functions
$iris->agents->setEnabledFunctions($agentId, [
    'manageLeads' => true,
    'deepResearch' => true,
    'marketResearch' => false
]);

// Get updated agent
$agent = $iris->agents->get($agentId);
echo "Agent updated: {$agent->name}\n";
```

---

## API Reference Quick Guide

### Agent Creation
- `createFromTemplate($template, $customizations)` - Create from template
- `createFromConfig($config)` - Create with full configuration
- `createFromArray($data)` - Create from simple array

### Schedule Management
- `getSchedule($agentId)` - Get schedule configuration
- `setSchedule($agentId, $schedule)` - Set complete schedule
- `addScheduledTask($agentId, $task)` - Add single task
- `removeScheduledTask($agentId, $taskName)` - Remove task by name

### Integration Management
- `getIntegrations($agentId)` - Get integration status
- `setIntegrations($agentId, $integrations)` - Set multiple integrations
- `enableIntegration($agentId, $integration)` - Enable single integration
- `disableIntegration($agentId, $integration)` - Disable integration

### Function Management
- `getEnabledFunctions($agentId)` - Get enabled functions
- `setEnabledFunctions($agentId, $functions)` - Set enabled functions

### Template Management
- `listTemplates()` - List all available templates

---

## Tips & Best Practices

### 1. Start with Templates
Use templates as a starting point and customize as needed. They include best practices and optimized defaults.

### 2. Set Correct Timezone
Always specify the timezone for scheduled tasks to ensure they fire at the correct local time.

```php
'schedule' => [
    'timezone' => 'America/Chicago', // Critical for accurate timing
]
```

### 3. Test Schedules
After setting up schedules, test them to ensure they fire as expected.

```php
$schedule = $iris->agents->getSchedule($agentId);
print_r($schedule); // Verify configuration
```

### 4. Use Meaningful Task Names
Name your scheduled tasks clearly so they're easy to manage later.

```php
// Good
['name' => 'Morning Medication Reminder', 'time' => '08:00']

// Bad
['name' => 'Task 1', 'time' => '08:00']
```

### 5. Enable Only Needed Integrations
Only enable integrations your agent actually uses to keep it focused and secure.

```php
$iris->agents->setIntegrations($agentId, [
    'gmail' => true,        // Need this
    'google-calendar' => true, // Need this
    'slack' => false,       // Don't need
    'github' => false       // Don't need
]);
```

### 6. Version Control Your Configurations
Save your agent configurations as PHP files for version control and easy replication.

---

## Troubleshooting

### Schedule Not Firing?
- Check timezone is correct
- Verify `enabled: true` in schedule
- Check `active_hours` if using business hours mode

### Integration Not Working?
- Verify integration is enabled
- Check OAuth connection in web UI
- Ensure agent has permission to use integration

### Template Not Found?
```php
// List available templates
$templates = $iris->agents->listTemplates();
print_r(array_keys($templates));
```

---

## More Resources

- [README.md](README.md) - Project overview
- [TECHNICAL.md](TECHNICAL.md) - Complete API documentation
- [QUICKSTART.md](QUICKSTART.md) - Quick setup guide
- [SDK_IMPROVEMENTS_COMPLETE.md](SDK_IMPROVEMENTS_COMPLETE.md) - Latest enhancements

---

**Need help?** Open an issue or contact support@heyiris.io
