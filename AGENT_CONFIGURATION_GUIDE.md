# IRIS SDK Enhanced Agent Configuration Guide

## 🎯 Overview

The IRIS SDK now includes **comprehensive agent configuration** features that expose the full power of the REST API. You can now create fully-configured agents in a single call, including settings, integrations, schedules, and more.

## 🚀 What's New

### Before (Basic Agent Creation)
```php
// Old way - had to run 4 separate scripts
$agent = $iris->agents->create(['name' => 'Helper', 'prompt' => 'You help...']);
// Then manually configure integrations...
// Then set up schedules...
// Then enable functions...
// Then configure voice settings...
```

### After (Unified Configuration)
```php
// New way - everything in one call!
$agent = $iris->agents->createFromConfig([
    'name' => 'Grandma Helper',
    'type' => 'content',
    'initial_prompt' => 'You are a caring assistant...',
    'settings' => [
        'schedule' => [
            'enabled' => true,
            'timezone' => 'America/New_York',
            'recurring_tasks' => [
                ['name' => 'Morning Meds', 'time' => '08:00', 'message' => '...', 'channels' => ['voice', 'sms']]
            ]
        ],
        'agentIntegrations' => ['gmail' => true, 'google-calendar' => true],
        'enabledFunctions' => ['manageLeads' => true],
        'voiceSettings' => ['language' => 'en-US', 'speaking_rate' => 0.9]
    ]
]);
```

## 📋 New Features

### 1. Unified Agent Creation (`createFromConfig`)

Create agents with complete configuration in one call:

```php
$agent = $iris->agents->createFromConfig([
    'name' => 'Sales Assistant',
    'type' => 'content',
    'initial_prompt' => 'You are a professional sales assistant...',
    'bloq_id' => 40,  // Knowledge base
    'settings' => [
        'agentIntegrations' => [
            'gmail' => true,
            'google-calendar' => true,
            'slack' => false,
        ],
        'enabledFunctions' => [
            'manageLeads' => true,
            'deepResearch' => true,
        ],
        'schedule' => [
            'enabled' => true,
            'timezone' => 'America/New_York',
            'recurring_tasks' => [/* ... */]
        ],
        'responseMode' => 'balanced',
        'communicationStyle' => 'professional',
        'memoryPersistence' => true,
    ]
]);
```

### 2. Settings Management

Get and update agent settings:

```php
// Get current settings
$settings = $iris->agents->getSettings($agentId);
echo "Response mode: {$settings->responseMode}\n";
echo "Enabled integrations: " . implode(', ', $settings->getEnabledIntegrations());

// Update settings
$iris->agents->updateSettings($agentId, [
    'responseMode' => 'creative',
    'contextWindow' => '20',
    'memoryPersistence' => true,
]);

// Reset to defaults
$iris->agents->resetSettings($agentId);
```

### 3. Integration Management

Manage agent integrations easily:

```php
// Get integration status
$integrations = $iris->agents->getIntegrations($agentId);
// Returns: ['gmail' => true, 'slack' => false, ...]

// Enable/disable bulk
$iris->agents->setIntegrations($agentId, [
    'gmail' => true,
    'google-calendar' => true,
    'slack' => false,
]);

// Enable single integration
$iris->agents->enableIntegration($agentId, 'gmail');

// Disable single integration
$iris->agents->disableIntegration($agentId, 'slack');

// Test integration
$result = $iris->agents->testIntegration($agentId, 'gmail');
```

### 4. Schedule Configuration

Configure recurring tasks and reminders:

```php
use IRIS\SDK\Resources\Agents\AgentScheduleConfig;

// Create medication reminder schedule
$schedule = AgentScheduleConfig::medicationReminders(
    times: ['08:00', '12:00', '18:00', '21:00'],
    message: 'Time for your medication',
    channels: ['voice', 'sms']
);

// Apply to agent
$iris->agents->updateSchedule($agentId, $schedule);

// Get current schedule
$currentSchedule = $iris->agents->getSchedule($agentId);

// Create daily check-in
$schedule = AgentScheduleConfig::dailyCheckIn(
    time: '20:00',
    message: 'How was your day?',
    channels: ['voice']
);
```

### 5. Agent Templates

Use pre-built templates for common use cases:

```php
// List available templates
$templates = $iris->agents->listTemplates();
// Returns: ['elderly-care', 'customer-support', 'sales-assistant', 'research-agent', 'educational-tutor', 'leadership-coach']

// Create from template
$agent = $iris->agents->createFromTemplate('elderly-care', [
    'name' => 'Grandma Helper',
    'medication_times' => ['08:00', '12:00', '18:00', '21:00'],
    'timezone' => 'America/New_York',
    'voice_settings' => [
        'language' => 'en-US',
        'speaking_rate' => 0.9,  // Slower for clarity
    ]
]);

// Register custom template
$iris->agents->registerTemplate(new MyCustomTemplate());
```

## 📚 Built-In Templates

### 1. Elderly Care (`elderly-care`)

For medication reminders, health monitoring, and family contact management.

```php
$agent = $iris->agents->createFromTemplate('elderly-care', [
    'name' => 'Care Assistant',
    'medication_times' => ['08:00', '12:00', '18:00', '21:00'],
    'family_contacts' => ['+1234567890'],
    'timezone' => 'America/New_York',
    'voice_settings' => ['language' => 'en-US', 'speaking_rate' => 0.9],
    'additional_tasks' => [
        [
            'name' => 'Hydration Reminder',
            'time' => '14:00',
            'message' => 'Remember to drink water!',
            'channels' => ['voice'],
            'frequency' => 'daily',
        ]
    ]
]);
```

**Default features:**
- Warm, patient communication style
- 4x daily medication reminders
- Daily evening check-in
- Google Calendar & Gmail integration
- Slower voice rate for clarity

### 2. Customer Support (`customer-support`)

For helpdesk automation and ticket management.

```php
$agent = $iris->agents->createFromTemplate('customer-support', [
    'name' => 'Support Bot',
]);
```

**Default features:**
- Professional, empathetic tone
- Slack & Gmail integration
- Lead/ticket management enabled
- Knowledge base search optimized

### 3. Sales Assistant (`sales-assistant`)

For lead management and CRM automation.

```php
$agent = $iris->agents->createFromTemplate('sales-assistant', [
    'name' => 'Sales Bot',
]);
```

**Default features:**
- Persuasive, professional tone
- Gmail & Google Calendar integration
- Lead management & research enabled
- Proactive response mode

### 4. Research Agent (`research-agent`)

For deep research and analysis tasks.

```php
$agent = $iris->agents->createFromTemplate('research-agent', [
    'name' => 'Research Assistant',
]);
```

**Default features:**
- Analytical, thorough tone
- Google Drive integration
- Deep research function enabled
- Larger context window (20)

### 5. Educational Tutor (`educational-tutor`)

For personalized learning and homework help.

```php
$agent = $iris->agents->createFromTemplate('educational-tutor', [
    'name' => 'Learning Tutor',
    'subject_focus' => 'Mathematics',
    'grade_level' => '8th grade',
]);
```

**Default features:**
- Encouraging, patient tone
- Daily study reminders (9am, 7pm)
- Google Drive & Calendar integration
- Deep research for explanations
- Memory persistence for progress tracking

### 6. Leadership Coach (`leadership-coach`)

For executive coaching and professional development.

```php
$agent = $iris->agents->createFromTemplate('leadership-coach', [
    'name' => 'Executive Coach',
    'focus_areas' => ['delegation', 'communication', 'strategy'],
]);
```

**Default features:**
- Thought-provoking, reflective tone
- Weekly reflection reminders
- Monthly goal review
- Google Calendar & Gmail integration
- Extended context window (20)

## 🎨 Creating Custom Templates

```php
use IRIS\SDK\Resources\Agents\AgentTemplate;
use IRIS\SDK\Resources\Agents\AgentSettings;

class MyCustomTemplate extends AgentTemplate
{
    public function getName(): string
    {
        return 'my-custom-template';
    }

    public function getDescription(): string
    {
        return 'My custom agent template';
    }

    public function getDefaultConfig(): array
    {
        return [
            'name' => 'Custom Agent',
            'type' => 'content',
            'initial_prompt' => 'Your custom prompt here...',
        ];
    }

    public function getRequiredIntegrations(): array
    {
        return ['gmail', 'slack'];
    }

    public function getDefaultSettings(): AgentSettings
    {
        $settings = new AgentSettings();
        $settings->enableIntegrations(['gmail', 'slack']);
        $settings->enableFunction('manageLeads');
        return $settings;
    }

    public function validate(array $customizations): void
    {
        // Add custom validation logic
        if (isset($customizations['required_field']) && empty($customizations['required_field'])) {
            throw new \InvalidArgumentException('required_field is required');
        }
    }
}

// Register and use
$iris->agents->registerTemplate(new MyCustomTemplate());
$agent = $iris->agents->createFromTemplate('my-custom-template', [
    'name' => 'My Agent',
    'required_field' => 'value',
]);
```

## 🔧 Advanced Configuration

### AgentSettings Class

```php
use IRIS\SDK\Resources\Agents\AgentSettings;

$settings = new AgentSettings(
    agentIntegrations: ['gmail' => true, 'slack' => true],
    enabledFunctions: ['manageLeads' => true, 'deepResearch' => false],
    schedule: ['enabled' => true, 'timezone' => 'UTC'],
    responseMode: 'balanced',  // 'creative', 'balanced', 'precise'
    contextWindow: '10',
    memoryPersistence: true,
    communicationStyle: 'professional',  // 'professional', 'casual', 'warm', 'analytical'
    voiceSettings: ['language' => 'en-US', 'speaking_rate' => 1.0],
);

// Fluent API
$settings
    ->enableIntegration('google-calendar')
    ->disableIntegration('slack')
    ->enableFunction('deepResearch')
    ->withResponseMode('creative')
    ->withCommunicationStyle('casual');
```

### AgentScheduleConfig Class

```php
use IRIS\SDK\Resources\Agents\AgentScheduleConfig;

$schedule = new AgentScheduleConfig(
    enabled: true,
    timezone: 'America/New_York',
);

$schedule
    ->addTask([
        'name' => 'Morning Standup',
        'time' => '09:00',
        'message' => 'Time for daily standup!',
        'channels' => ['slack'],
        'frequency' => 'daily',
    ])
    ->addTask([
        'name' => 'Weekly Report',
        'time' => '17:00',
        'message' => 'Generate weekly report',
        'channels' => ['email'],
        'frequency' => 'weekly',
    ]);

// Helper methods
$schedule = AgentScheduleConfig::medicationReminders(['08:00', '20:00']);
$schedule = AgentScheduleConfig::dailyCheckIn('18:00', 'How are you feeling?');
```

## 📖 Complete Example: Elderly Care Setup

```php
<?php
require 'vendor/autoload.php';

use IRIS\SDK\IRIS;

$iris = new IRIS([
    'api_key' => $_ENV['IRIS_API_KEY'],
    'user_id' => 193,
]);

// Option 1: Using template (recommended - fastest setup)
$agent = $iris->agents->createFromTemplate('elderly-care', [
    'name' => 'Grandma Helper',
    'medication_times' => ['08:00', '12:00', '18:00', '21:00'],
    'timezone' => 'America/New_York',
    'voice_settings' => [
        'language' => 'en-US',
        'speaking_rate' => 0.9,
    ],
    'additional_tasks' => [
        [
            'name' => 'Dinner Reminder',
            'time' => '18:00',
            'message' => 'Time to prepare dinner!',
            'channels' => ['voice'],
            'frequency' => 'daily',
        ]
    ]
]);

// Option 2: Using unified configuration (full control)
$agent = $iris->agents->createFromConfig([
    'name' => 'Grandma Helper',
    'type' => 'content',
    'initial_prompt' => 'You are a caring assistant for elderly care...',
    'bloq_id' => 40,
    'settings' => [
        'schedule' => [
            'enabled' => true,
            'timezone' => 'America/New_York',
            'recurring_tasks' => [
                [
                    'name' => 'Morning Medication',
                    'time' => '08:00',
                    'message' => 'Good morning! Time for your medications',
                    'channels' => ['voice', 'sms'],
                    'frequency' => 'daily',
                ],
                [
                    'name' => 'Lunch Medication',
                    'time' => '12:00',
                    'message' => 'Time for your midday medication',
                    'channels' => ['voice', 'sms'],
                    'frequency' => 'daily',
                ],
                [
                    'name' => 'Dinner Medication',
                    'time' => '18:00',
                    'message' => 'Time for your evening medication',
                    'channels' => ['voice', 'sms'],
                    'frequency' => 'daily',
                ],
                [
                    'name' => 'Bedtime Medication',
                    'time' => '21:00',
                    'message' => 'Time for your bedtime medication',
                    'channels' => ['voice', 'sms'],
                    'frequency' => 'daily',
                ],
                [
                    'name' => 'Evening Check-in',
                    'time' => '20:00',
                    'message' => 'How was your day? Need anything?',
                    'channels' => ['voice'],
                    'frequency' => 'daily',
                ],
            ]
        ],
        'agentIntegrations' => [
            'google-calendar' => true,
            'gmail' => true,
        ],
        'communicationStyle' => 'warm',
        'responseMode' => 'conversational',
        'memoryPersistence' => true,
        'voiceSettings' => [
            'language' => 'en-US',
            'speaking_rate' => 0.9,  // Slower for clarity
            'pitch' => 0,
            'volume' => 1.0,
        ],
    ]
]);

echo "Created agent: {$agent->name} (ID: {$agent->id})\n";
echo "Agent URL: {$agent->getSimpleUrl()}\n";

// Verify configuration
$settings = $iris->agents->getSettings($agent->id);
echo "Integrations: " . implode(', ', $settings->getEnabledIntegrations()) . "\n";

$schedule = $iris->agents->getSchedule($agent->id);
echo "Scheduled tasks: " . count($schedule->recurringTasks) . "\n";
```

## 🎯 Migration Guide

If you have existing scripts that create agents in multiple steps, here's how to migrate:

### Before
```php
// Step 1: Create agent
$agent = $iris->agents->create(['name' => 'Helper', 'prompt' => '...']);

// Step 2: Enable integrations (separate script)
$agent = $iris->agents->patch($agent->id, [
    'settings' => ['agentIntegrations' => ['gmail' => true]]
]);

// Step 3: Set up schedule (separate script)
// ... manually configure schedules

// Step 4: Configure voice (separate script)
// ... manually configure voice settings
```

### After
```php
// Single call - everything configured
$agent = $iris->agents->createFromTemplate('elderly-care', [
    'name' => 'Helper',
    'medication_times' => ['08:00', '20:00'],
    'timezone' => 'America/New_York',
]);
```

## 📊 Benefits

1. **50% less code** - Create fully-configured agents in one call
2. **Type-safe configuration** - Use AgentSettings and AgentScheduleConfig classes
3. **Reusable templates** - DRY principle for common agent types
4. **Easier testing** - Mock and test complete agent configurations
5. **Better maintenance** - Single source of truth for agent setup

## 🔗 Related Documentation

- [REST API Agent Configuration](https://apiv2.heyiris.io/api/documentation)
- [SDK Reference](./README.md)
- [Agent Best Practices](./AGENTS.md)

---

**Questions?** Open an issue or contact support@heyiris.io
