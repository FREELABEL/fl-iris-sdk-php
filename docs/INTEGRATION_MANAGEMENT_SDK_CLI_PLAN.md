# Integration Management in SDK & CLI - Implementation Plan

## Executive Summary

This document outlines how to add comprehensive integration management capabilities to the IRIS SDK and CLI, enabling developers to programmatically connect, configure, test, and manage third-party integrations from code or command line.

## Current State Analysis

### What Exists Today

#### Backend API (IntegrationsController.php)
The backend already has a complete integration management API:

**Core Endpoints:**
- `GET /api/v1/integrations` - List all integrations
- `POST /api/v1/integrations` - Create integration
- `GET /api/v1/integrations/{id}` - Get specific integration
- `PUT /api/v1/integrations/{id}` - Update integration
- `DELETE /api/v1/integrations/{id}` - Delete integration
- `POST /api/v1/integrations/{id}/test` - Test integration
- `GET /api/v1/integrations/types` - Get available integration types

**OAuth Flow Endpoints:**
- `GET /api/v1/integrations/oauth-url/{type}` - Get OAuth URL
- `GET /api/v1/integrations/oauth-callback/{type}` - Handle OAuth callback
- `POST /api/v1/integrations/oauth-callback/{type}` - Handle OAuth callback (POST)

**Execution Endpoints:**
- `POST /api/v1/integrations/execute` - Execute integration function
- `GET /api/v1/integrations/ai-context` - Get AI context from integrations
- `GET /api/v1/integrations/enabled` - Get enabled integrations

#### PHP SDK (IntegrationsResource.php)
The SDK already has a basic `IntegrationsResource` class with methods:
- `list()` - List integrations
- `create()` - Create integration
- `get()` - Get integration
- `update()` - Update integration
- `delete()` - Delete integration
- `test()` - Test integration
- `types()` - Get available types
- `getOAuthUrl()` - Get OAuth URL
- `execute()` - Execute integration function

#### CLI (iris command)
- Currently uses `SDKCommand` for dynamic resource/method calling
- Already supports: `iris call integrations.list`
- But no dedicated integration management commands

### What's Missing

1. **OAuth Flow Handling in CLI** - No way to complete OAuth in terminal
2. **Dedicated Integration Commands** - No `iris integrations:connect`, etc.
3. **Interactive Configuration** - No guided setup for API keys
4. **Connection Status Display** - No easy way to see what's connected
5. **Credential Storage** - No secure local storage for testing
6. **Agent Integration Mapping** - No way to enable/disable integrations per agent

## Implementation Plan

### Phase 1: Enhanced SDK Methods

#### 1.1 Add Connection Status Methods

```php
// Add to IntegrationsResource.php

/**
 * Check connection status for an integration type
 * 
 * @param string $type Integration type (e.g., 'vapi', 'servis-ai')
 * @return array{connected: bool, details: array}
 */
public function status(string $type): array
{
    $integrations = $this->list();
    $integration = $integrations->findByType($type);
    
    if (!$integration) {
        return ['connected' => false, 'details' => []];
    }
    
    return [
        'connected' => $integration->status === 'active',
        'details' => [
            'id' => $integration->id,
            'name' => $integration->name,
            'created_at' => $integration->created_at,
            'last_tested' => $integration->last_tested_at,
        ]
    ];
}

/**
 * Get all connected integrations
 * 
 * @return IntegrationCollection
 */
public function connected(): IntegrationCollection
{
    return $this->list()->filter(function($integration) {
        return $integration->status === 'active';
    });
}

/**
 * Disconnect (delete) an integration by type
 * 
 * @param string $type Integration type
 * @return bool
 */
public function disconnect(string $type): bool
{
    $integrations = $this->list();
    $integration = $integrations->findByType($type);
    
    if (!$integration) {
        return false;
    }
    
    return $this->delete($integration->id);
}
```

#### 1.2 Add API Key Authentication Methods

```php
/**
 * Connect integration using API key
 * 
 * @param string $type Integration type
 * @param array $credentials Credentials (e.g., ['apiKey' => 'xxx'])
 * @param string|null $name Optional integration name
 * @return Integration
 */
public function connectWithApiKey(string $type, array $credentials, ?string $name = null): Integration
{
    // Get integration type info to validate
    $types = $this->types();
    $typeInfo = $types[$type] ?? null;
    
    if (!$typeInfo) {
        throw new \InvalidArgumentException("Unknown integration type: {$type}");
    }
    
    // Determine category based on type
    $category = $typeInfo['category'] ?? 'automation';
    
    return $this->create([
        'name' => $name ?? $typeInfo['name'] ?? ucfirst($type),
        'type' => $type,
        'category' => $category,
        'credentials' => $credentials,
    ]);
}

/**
 * Connect Vapi Voice AI integration
 * 
 * @param string $apiKey Vapi API key
 * @param string|null $phoneNumber Optional phone number
 * @return Integration
 */
public function connectVapi(string $apiKey, ?string $phoneNumber = null): Integration
{
    $credentials = [
        'api_key' => $apiKey,
    ];
    
    if ($phoneNumber) {
        $credentials['phone_number'] = $phoneNumber;
    }
    
    return $this->connectWithApiKey('vapi', $credentials, 'Vapi Voice AI');
}

/**
 * Connect Servis.ai integration
 * 
 * @param string $clientId Servis.ai client ID
 * @param string $clientSecret Servis.ai client secret
 * @return Integration
 */
public function connectServisAi(string $clientId, string $clientSecret): Integration
{
    return $this->connectWithApiKey('servis-ai', [
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
    ], 'Servis.ai');
}

/**
 * Connect SMTP Email integration
 * 
 * @param string $host SMTP host
 * @param int $port SMTP port
 * @param string $username SMTP username
 * @param string $password SMTP password
 * @param string $fromEmail From email address
 * @param string $fromName From name
 * @param string $encryption Encryption type (tls, ssl, or empty)
 * @return Integration
 */
public function connectSmtp(
    string $host,
    int $port,
    string $username,
    string $password,
    string $fromEmail,
    string $fromName = '',
    string $encryption = 'tls'
): Integration
{
    return $this->connectWithApiKey('smtp-email', [
        'smtp_host' => $host,
        'smtp_port' => $port,
        'smtp_username' => $username,
        'smtp_password' => $password,
        'from_email' => $fromEmail,
        'from_name' => $fromName,
        'smtp_encryption' => $encryption,
    ], 'SMTP Email');
}
```

#### 1.3 Add OAuth Helper Methods

```php
/**
 * Start OAuth flow and return URL to open in browser
 * 
 * @param string $type Integration type
 * @return array{url: string, instructions: string}
 */
public function startOAuthFlow(string $type): array
{
    $url = $this->getOAuthUrl($type);
    
    return [
        'url' => $url,
        'instructions' => "Open this URL in your browser to authorize access:\n{$url}\n\nAfter authorization, the integration will be automatically connected.",
    ];
}

/**
 * Check if integration uses OAuth
 * 
 * @param string $type Integration type
 * @return bool
 */
public function usesOAuth(string $type): bool
{
    $oauthTypes = ['google-drive', 'google-calendar', 'gmail', 'slack', 'github', 'mailchimp'];
    return in_array($type, $oauthTypes);
}

/**
 * Check if integration uses API key
 * 
 * @param string $type Integration type
 * @return bool
 */
public function usesApiKey(string $type): bool
{
    $apiKeyTypes = ['vapi', 'servis-ai', 'smtp-email', 'mailjet', 'google-gemini', 'savelife-ai'];
    return in_array($apiKeyTypes, $type);
}
```

#### 1.4 Add Agent Integration Management Methods

```php
/**
 * Enable integration for an agent
 * 
 * @param int $agentId Agent ID
 * @param string $integrationType Integration type
 * @return bool
 */
public function enableForAgent(int $agentId, string $integrationType): bool
{
    // Get agent
    $agent = $this->http->get("/api/v1/agents/{$agentId}");
    
    // Get current integrations
    $agentIntegrations = $agent['settings']['agentIntegrations'] ?? [];
    
    // Enable this integration
    $agentIntegrations[$integrationType] = true;
    
    // Update agent
    $this->http->put("/api/v1/agents/{$agentId}", [
        'settings' => array_merge($agent['settings'] ?? [], [
            'agentIntegrations' => $agentIntegrations,
        ]),
    ]);
    
    return true;
}

/**
 * Disable integration for an agent
 * 
 * @param int $agentId Agent ID
 * @param string $integrationType Integration type
 * @return bool
 */
public function disableForAgent(int $agentId, string $integrationType): bool
{
    // Get agent
    $agent = $this->http->get("/api/v1/agents/{$agentId}");
    
    // Get current integrations
    $agentIntegrations = $agent['settings']['agentIntegrations'] ?? [];
    
    // Disable this integration
    $agentIntegrations[$integrationType] = false;
    
    // Update agent
    $this->http->put("/api/v1/agents/{$agentId}", [
        'settings' => array_merge($agent['settings'] ?? [], [
            'agentIntegrations' => $agentIntegrations,
        ]),
    ]);
    
    return true;
}

/**
 * Get integrations enabled for an agent
 * 
 * @param int $agentId Agent ID
 * @return array List of enabled integration types
 */
public function getAgentIntegrations(int $agentId): array
{
    $agent = $this->http->get("/api/v1/agents/{$agentId}");
    $agentIntegrations = $agent['settings']['agentIntegrations'] ?? [];
    
    // Filter to only enabled ones
    return array_keys(array_filter($agentIntegrations, fn($enabled) => $enabled === true));
}
```

### Phase 2: New CLI Commands

Create a new `IntegrationsCommand` class for dedicated integration management.

#### 2.1 Command Structure

```php
<?php

namespace IRIS\SDK\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use IRIS\SDK\IRIS;
use IRIS\SDK\Config;

class IntegrationsCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('integrations')
            ->setDescription('Manage third-party integrations')
            ->addArgument('action', InputArgument::OPTIONAL, 'Action to perform: list, connect, disconnect, test, status')
            ->addArgument('type', ArgumentInputArgument::OPTIONAL, 'Integration type')
            ->addOption('api-key', null, InputOption::VALUE_REQUIRED, 'API key for authentication')
            ->addOption('user-id', null, InputOption::VALUE_REQUIRED, 'User ID');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $action = $input->getArgument('action') ?? 'list';
        
        // Initialize SDK
        $iris = new IRIS($this->getConfigOptions($input));
        
        switch ($action) {
            case 'list':
                return $this->listIntegrations($iris, $io);
            case 'connect':
                return $this->connectIntegration($iris, $io, $input);
            case 'disconnect':
                return $this->disconnectIntegration($iris, $io, $input);
            case 'test':
                return $this->testIntegration($iris, $io, $input);
            case 'status':
                return $this->showStatus($iris, $io, $input);
            case 'types':
                return $this->showTypes($iris, $io);
            default:
                $io->error("Unknown action: {$action}");
                return Command::FAILURE;
        }
    }
}
```

#### 2.2 List Command

```php
private function listIntegrations(IRIS $iris, SymfonyStyle $io): int
{
    $io->title('Your Connected Integrations');
    
    $integrations = $iris->integrations->list();
    
    if ($integrations->isEmpty()) {
        $io->warning('No integrations connected yet.');
        $io->text('Run: iris integrations connect <type>');
        return Command::SUCCESS;
    }
    
    $rows = [];
    foreach ($integrations as $integration) {
        $status = $integration->status === 'active' ? '✓' : '✗';
        $rows[] = [
            $integration->id,
            $integration->name,
            $integration->type,
            $integration->category,
            $status,
            $integration->created_at,
        ];
    }
    
    $io->table(
        ['ID', 'Name', 'Type', 'Category', 'Status', 'Created'],
        $rows
    );
    
    return Command::SUCCESS;
}
```

#### 2.3 Connect Command

```php
private function connectIntegration(IRIS $iris, SymfonyStyle $io, InputInterface $input): int
{
    $type = $input->getArgument('type');
    
    if (!$type) {
        // Show available types and let user choose
        $types = $iris->integrations->types();
        $choices = array_map(fn($t) => $t['name'] . " ({$t['category']})", $types);
        
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion('Select integration to connect:', $choices);
        $choice = $helper->ask($input, $output, $question);
        
        // Extract type from choice
        $type = array_search($choice, $choices);
    }
    
    $io->section("Connecting {$type}");
    
    // Check if already connected
    $status = $iris->integrations->status($type);
    if ($status['connected']) {
        $io->warning("Already connected to {$type}");
        if (!$io->confirm('Disconnect and reconnect?', false)) {
            return Command::SUCCESS;
        }
        $iris->integrations->disconnect($type);
    }
    
    // Determine auth method
    if ($iris->integrations->usesOAuth($type)) {
        return $this->connectOAuth($iris, $io, $type);
    } else {
        return $this->connectApiKey($iris, $io, $input, $type);
    }
}

private function connectOAuth(IRIS $iris, SymfonyStyle $io, string $type): int
{
    $io->text("Starting OAuth flow for {$type}...");
    
    $flow = $iris->integrations->startOAuthFlow($type);
    
    $io->text($flow['instructions']);
    $io->newLine();
    $io->text("OAuth URL:");
    $io->text($flow['url']);
    $io->newLine();
    
    // Try to open in browser automatically
    if (PHP_OS_FAMILY === 'Darwin') {
        exec("open '{$flow['url']}'");
    } elseif (PHP_OS_FAMILY === 'Windows') {
        exec("start '{$flow['url']}'");
    } elseif (PHP_OS_FAMILY === 'Linux') {
        exec("xdg-open '{$flow['url']}'");
    }
    
    $io->note('After authorizing in your browser, the integration will be automatically connected.');
    $io->text('You can check the status with: iris integrations status ' . $type);
    
    return Command::SUCCESS;
}

private function connectApiKey(IRIS $iris, SymfonyStyle $io, InputInterface $input, string $type): int
{
    $helper = $this->getHelper('question');
    
    // Type-specific credential collection
    switch ($type) {
        case 'vapi':
            $io->text('Get your API key from: https://dashboard.vapi.ai');
            $question = new Question('Vapi API Key: ');
            $question->setHidden(true);
            $apiKey = $helper->ask($input, $output, $question);
            
            $phoneQuestion = new Question('Phone Number (optional, press enter to skip): ');
            $phoneNumber = $helper->ask($input, $output, $phoneQuestion);
            
            $integration = $iris->integrations->connectVapi($apiKey, $phoneNumber ?: null);
            break;
            
        case 'servis-ai':
            $io->text('Get your credentials from Servis.ai dashboard');
            $clientIdQuestion = new Question('Client ID: ');
            $clientId = $helper->ask($input, $output, $clientIdQuestion);
            
            $secretQuestion = new Question('Client Secret: ');
            $secretQuestion->setHidden(true);
            $clientSecret = $helper->ask($input, $output, $secretQuestion);
            
            $integration = $iris->integrations->connectServisAi($clientId, $clientSecret);
            break;
            
        case 'smtp-email':
            $io->text('Configure SMTP email settings');
            $host = $helper->ask($input, $output, new Question('SMTP Host: '));
            $port = $helper->ask($input, $output, new Question('SMTP Port (587): ', 587));
            $username = $helper->ask($input, $output, new Question('Username: '));
            
            $passwordQuestion = new Question('Password: ');
            $passwordQuestion->setHidden(true);
            $password = $helper->ask($input, $output, $passwordQuestion);
            
            $fromEmail = $helper->ask($input, $output, new Question('From Email: '));
            $fromName = $helper->ask($input, $output, new Question('From Name: '));
            $encryption = $helper->ask($input, $output, new ChoiceQuestion('Encryption:', ['tls', 'ssl', 'none'], 'tls'));
            
            $integration = $iris->integrations->connectSmtp(
                $host, (int)$port, $username, $password, 
                $fromEmail, $fromName, $encryption
            );
            break;
            
        default:
            // Generic API key prompt
            $question = new Question('API Key: ');
            $question->setHidden(true);
            $apiKey = $helper->ask($input, $output, $question);
            
            $integration = $iris->integrations->connectWithApiKey($type, [
                'api_key' => $apiKey,
            ]);
    }
    
    // Test the connection
    $io->text('Testing connection...');
    $testResult = $iris->integrations->test($integration->id);
    
    if ($testResult->success) {
        $io->success("✓ Successfully connected to {$type}!");
    } else {
        $io->error("✗ Connection test failed: " . $testResult->message);
        return Command::FAILURE;
    }
    
    return Command::SUCCESS;
}
```

#### 2.4 Other Commands

```php
private function disconnectIntegration(IRIS $iris, SymfonyStyle $io, InputInterface $input): int
{
    $type = $input->getArgument('type');
    
    if (!$type) {
        $io->error('Please specify integration type to disconnect');
        return Command::FAILURE;
    }
    
    if ($io->confirm("Are you sure you want to disconnect {$type}?", false)) {
        if ($iris->integrations->disconnect($type)) {
            $io->success("Disconnected from {$type}");
        } else {
            $io->warning("Integration {$type} was not connected");
        }
    }
    
    return Command::SUCCESS;
}

private function testIntegration(IRIS $iris, SymfonyStyle $io, InputInterface $input): int
{
    $type = $input->getArgument('type');
    
    if (!$type) {
        $io->error('Please specify integration type to test');
        return Command::FAILURE;
    }
    
    $integrations = $iris->integrations->list();
    $integration = $integrations->findByType($type);
    
    if (!$integration) {
        $io->error("Integration {$type} not found. Connect it first.");
        return Command::FAILURE;
    }
    
    $io->text("Testing {$type} connection...");
    $result = $iris->integrations->test($integration->id);
    
    if ($result->success) {
        $io->success("✓ Connection test successful!");
        if ($result->details) {
            $io->table(['Key', 'Value'], array_map(
                fn($k, $v) => [$k, $v],
                array_keys($result->details),
                array_values($result->details)
            ));
        }
    } else {
        $io->error("✗ Connection test failed: " . $result->message);
        return Command::FAILURE;
    }
    
    return Command::SUCCESS;
}

private function showStatus(IRIS $iris, SymfonyStyle $io, InputInterface $input): int
{
    $type = $input->getArgument('type');
    
    if ($type) {
        // Show status for specific integration
        $status = $iris->integrations->status($type);
        
        $io->title("Status: {$type}");
        
        if ($status['connected']) {
            $io->success("Connected");
            $io->definitionList(
                ['ID' => $status['details']['id']],
                ['Name' => $status['details']['name']],
                ['Created' => $status['details']['created_at']],
                ['Last Tested' => $status['details']['last_tested'] ?? 'Never']
            );
        } else {
            $io->warning("Not connected");
            $io->text("Run: iris integrations connect {$type}");
        }
    } else {
        // Show overview of all integrations
        return $this->listIntegrations($iris, $io);
    }
    
    return Command::SUCCESS;
}

private function showTypes(IRIS $iris, SymfonyStyle $io): int
{
    $io->title('Available Integration Types');
    
    $types = $iris->integrations->types();
    
    $rows = [];
    foreach ($types as $typeKey => $typeInfo) {
        $rows[] = [
            $typeKey,
            $typeInfo['name'],
            $typeInfo['category'],
            $typeInfo['description'],
        ];
    }
    
    $io->table(['Type', 'Name', 'Category', 'Description'], $rows);
    
    return Command::SUCCESS;
}
```

#### 2.5 Register Command

Add to `Application.php`:

```php
use IRIS\SDK\Console\Commands\IntegrationsCommand;

public function __construct()
{
    parent::__construct('IRIS SDK', '1.0.0');

    $this->addCommands([
        new SDKCommand(),
        new ChatCommand(),
        new ConfigCommand(),
        new ToolsCommand(),
        new IntegrationsCommand(), // Add this
    ]);
}
```

### Phase 3: Collection Helper Methods

Add to `IntegrationCollection.php`:

```php
/**
 * Find integration by type
 * 
 * @param string $type Integration type
 * @return Integration|null
 */
public function findByType(string $type): ?Integration
{
    foreach ($this->items as $integration) {
        if ($integration->type === $type) {
            return $integration;
        }
    }
    return null;
}

/**
 * Filter integrations by status
 * 
 * @param string $status Status to filter by
 * @return IntegrationCollection
 */
public function filterByStatus(string $status): IntegrationCollection
{
    return new self(array_filter($this->items, function($integration) use ($status) {
        return $integration->status === $status;
    }), $this->meta);
}

/**
 * Get integrations by category
 * 
 * @param string $category Category to filter by
 * @return IntegrationCollection
 */
public function filterByCategory(string $category): IntegrationCollection
{
    return new self(array_filter($this->items, function($integration) use ($category) {
        return $integration->category === $category;
    }), $this->meta);
}
```

## Usage Examples

### SDK Usage

```php
use IRIS\SDK\IRIS;

$iris = new IRIS(['api_key' => 'your-key']);

// List all integrations
$integrations = $iris->integrations->list();

// Check if Vapi is connected
$status = $iris->integrations->status('vapi');
if ($status['connected']) {
    echo "Vapi is connected!\n";
}

// Connect Vapi
$integration = $iris->integrations->connectVapi('vapi_key_xxx', '+15551234567');

// Test connection
$result = $iris->integrations->test($integration->id);

// Enable for an agent
$iris->integrations->enableForAgent($agentId, 'vapi');

// Disconnect
$iris->integrations->disconnect('vapi');
```

### CLI Usage

```bash
# List all connected integrations
iris integrations list

# Show available integration types
iris integrations types

# Connect Vapi (interactive prompt)
iris integrations connect vapi

# Connect SMTP email (interactive)
iris integrations connect smtp-email

# Check status
iris integrations status vapi

# Test connection
iris integrations test vapi

# Disconnect
iris integrations disconnect vapi

# Use with API key flag
iris integrations list --api-key=your-key --user-id=123
```

## Security Considerations

1. **Never Log Credentials** - Ensure all logging excludes sensitive data
2. **Hide Input** - Use `setHidden(true)` for password/API key prompts
3. **Secure Storage** - Consider using system keychain for CLI credential storage
4. **HTTPS Only** - All API calls must use HTTPS
5. **Token Expiry** - Handle OAuth token refresh properly

## Testing Strategy

### Unit Tests

```php
class IntegrationsResourceTest extends TestCase
{
    public function testListIntegrations()
    {
        $iris = new IRIS(['api_key' => 'test']);
        $integrations = $iris->integrations->list();
        $this->assertInstanceOf(IntegrationCollection::class, $integrations);
    }
    
    public function testConnectVapi()
    {
        $iris = new IRIS(['api_key' => 'test']);
        $integration = $iris->integrations->connectVapi('vapi_test_key');
        $this->assertEquals('vapi', $integration->type);
    }
    
    public function testStatus()
    {
        $iris = new IRIS(['api_key' => 'test']);
        $status = $iris->integrations->status('vapi');
        $this->assertIsArray($status);
        $this->assertArrayHasKey('connected', $status);
    }
}
```

### Integration Tests

```bash
# Test CLI commands
./vendor/bin/phpunit tests/Console/IntegrationsCommandTest.php

# Test full flow
iris integrations connect vapi --api-key=test_key
iris integrations status vapi
iris integrations test vapi
iris integrations disconnect vapi
```

## Documentation Requirements

1. **SDK Documentation** - Update `TECHNICAL.md` with new methods
2. **CLI Documentation** - Update `CLI_USAGE.md` with integration commands
3. **Integration Guide** - Create `INTEGRATION_SETUP_GUIDE.md`
4. **Video Tutorial** - Record CLI integration setup demo
5. **API Examples** - Add to `examples/integrations/` directory

## Migration Path

For existing users:

1. **Backward Compatibility** - All existing code continues to work
2. **Deprecation Warnings** - None (adding new features only)
3. **Version Bump** - Increment SDK to v1.1.0
4. **Changelog** - Document all new integration methods

## Timeline Estimate

- **Phase 1 (SDK)**: 2-3 days
- **Phase 2 (CLI)**: 3-4 days  
- **Phase 3 (Collections)**: 1 day
- **Testing**: 2 days
- **Documentation**: 2 days
- **Total**: ~10-12 days

## Success Metrics

1. **Adoption Rate** - % of users connecting integrations via CLI
2. **Error Rate** - Integration connection failure rate < 5%
3. **Support Tickets** - Reduction in integration setup questions
4. **Documentation Views** - Track integration guide page views
5. **Agent Usage** - Increase in agents with integrations enabled

## Open Questions

1. Should we support OAuth device flow for better CLI experience?
2. Do we need a GUI tool for OAuth callback handling?
3. Should credentials be stored locally for CLI usage?
4. Do we need integration templates/presets?
5. Should we support bulk integration management?

## Conclusion

Adding integration management to the SDK and CLI will significantly improve the developer experience for programmatically configuring agents with third-party services. The backend API already supports all necessary operations, so the implementation is primarily about creating clean, user-friendly SDK methods and CLI commands that wrap these existing endpoints.

The key benefit is that developers can now:
- Automate integration setup in deployment scripts
- Manage integrations from CI/CD pipelines
- Quickly test integration connections from command line
- Build tools that programmatically configure agent capabilities

This makes IRIS more suitable for enterprise deployments where manual GUI configuration isn't practical.
