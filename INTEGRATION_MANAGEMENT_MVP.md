# Integration Management MVP 🔗

A simple, powerful way to manage third-party integrations from your code or command line.

## What's New

This MVP adds comprehensive integration management to the IRIS SDK and CLI:

### SDK Features ✨
- **Status checking** - `$iris->integrations->status('vapi')`
- **Easy connection** - Type-specific helpers for Vapi, Servis.ai, SMTP
- **OAuth flow support** - Start OAuth flows with one method call
- **Disconnect by type** - `$iris->integrations->disconnect('vapi')`
- **Collection filters** - Filter by status, category, find by type
- **Auth detection** - Know if integration uses OAuth or API key

### CLI Features 🖥️
- **Interactive connection** - Guided prompts for credentials
- **List integrations** - See all connections at a glance
- **Test connections** - Verify integrations work
- **Status checks** - Quick connection status
- **Type discovery** - Browse available integrations

## Quick Start

### SDK Usage

```php
use IRIS\SDK\IRIS;

$iris = new IRIS(['api_key' => 'your-key', 'user_id' => 123]);

// Connect Vapi
$integration = $iris->integrations->connectVapi('vapi_key', '+15551234567');

// Check status
$status = $iris->integrations->status('vapi');
if ($status['connected']) {
    echo "Vapi is ready!";
}

// Test connection
$result = $iris->integrations->test($integration->id);
```

### CLI Usage

```bash
# List all integrations
iris integrations list

# Connect Vapi (interactive)
iris integrations connect vapi

# Check status
iris integrations status vapi

# Test connection
iris integrations test vapi

# Disconnect
iris integrations disconnect vapi
```

## Installation

The integration management features are already included in the SDK. Just update your composer dependencies:

```bash
cd sdk/php
composer install
```

## Supported Integrations

### API Key Based
- **Vapi** - Voice AI for phone calls
- **Servis.ai** - AI-powered services
- **SMTP Email** - Custom email sending
- **Mailjet** - Email service
- **Google Gemini** - Google's AI model
- **SaveLife.AI** - AI assistance

### OAuth Based
- **Gmail** - Email integration
- **Google Drive** - File storage
- **Google Calendar** - Calendar integration
- **Slack** - Team communication
- **GitHub** - Code repository
- **Mailchimp** - Email marketing

## Files Added

### SDK (4 files)
1. **IntegrationsResource.php** - Enhanced with 11 new methods
2. **IntegrationCollection.php** - Added filtering helpers
3. **IntegrationsCommand.php** - New CLI command (522 lines)
4. **Application.php** - Registered new command

### Documentation (3 files)
1. **INTEGRATION_MANAGEMENT_SDK_CLI_PLAN.md** - Full implementation plan
2. **INTEGRATION_MANAGEMENT_QUICK_REFERENCE.md** - Usage guide
3. **examples/integrations-management.php** - Code examples
4. **test-integrations-mvp.php** - Test suite

## SDK Methods Added

### Connection Management
- `status(string $type)` - Check if integration is connected
- `connected()` - Get all active integrations
- `disconnect(string $type)` - Remove integration by type

### Easy Connection Helpers
- `connectWithApiKey(string $type, array $credentials, ?string $name)`
- `connectVapi(string $apiKey, ?string $phoneNumber)`
- `connectServisAi(string $clientId, string $clientSecret)`
- `connectSmtp(...$smtpParams)`

### OAuth Support
- `startOAuthFlow(string $type)` - Get OAuth URL and instructions
- `usesOAuth(string $type)` - Check if integration uses OAuth
- `usesApiKey(string $type)` - Check if integration uses API key

### Collection Helpers
- `findByType(string $type)` - Find specific integration
- `filterByStatus(string $status)` - Filter by status
- `filterByCategory(string $category)` - Filter by category

## CLI Commands

```bash
iris integrations list              # List all integrations
iris integrations types             # Show available types
iris integrations connect <type>    # Connect integration
iris integrations disconnect <type> # Disconnect integration
iris integrations status <type>     # Check status
iris integrations test <type>       # Test connection
```

### With Options
```bash
iris integrations list --api-key=xxx --user-id=123
iris integrations connect vapi -v  # Verbose mode
```

## Examples

### Automated Setup Script
```php
// Connect all required integrations
$required = [
    'vapi' => ['api_key' => getenv('VAPI_KEY')],
    'servis-ai' => [
        'client_id' => getenv('SERVIS_ID'),
        'client_secret' => getenv('SERVIS_SECRET'),
    ],
];

foreach ($required as $type => $creds) {
    if (!$iris->integrations->status($type)['connected']) {
        $iris->integrations->connectWithApiKey($type, $creds);
    }
}
```

### Health Check
```php
$connected = $iris->integrations->connected();

foreach ($connected as $integration) {
    $result = $iris->integrations->test($integration->id);
    if (!$result->success) {
        error_log("Integration {$integration->type} failed");
    }
}
```

### Deployment Script
```bash
#!/bin/bash
iris integrations connect vapi
iris integrations test vapi
iris integrations connect servis-ai
iris integrations test servis-ai
```

## Testing

Run the test suite:

```bash
cd sdk/php
php test-integrations-mvp.php
```

Run examples:

```bash
php examples/integrations-management.php
```

Try CLI commands:

```bash
./bin/iris integrations list
./bin/iris integrations types
```

## What This Enables

### For Developers
- **Programmatic setup** - Configure integrations in code
- **CI/CD integration** - Automate integration setup in pipelines
- **Health monitoring** - Check integration status automatically
- **Quick debugging** - Test connections from command line

### For DevOps
- **Deployment scripts** - Set up integrations during deploy
- **Infrastructure as code** - Define integrations in config
- **Environment parity** - Replicate integration setup across environments
- **Troubleshooting** - Quickly diagnose integration issues

### For Support
- **User assistance** - Guide users through CLI connection
- **Status checking** - Verify integration health remotely
- **Testing** - Validate integration credentials
- **Documentation** - Clear examples and references

## Security

- **Hidden passwords** - CLI hides sensitive input
- **Secure storage** - Credentials stored server-side only
- **HTTPS only** - All API calls use HTTPS
- **OAuth support** - Standard OAuth 2.0 flows

## Architecture

```
┌─────────────────────────────────────────┐
│         CLI (IntegrationsCommand)       │
│  ┌───────────────────────────────────┐  │
│  │ list | connect | test | disconnect│  │
│  └───────────────────────────────────┘  │
└──────────────────┬──────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────┐
│      SDK (IntegrationsResource)         │
│  ┌───────────────────────────────────┐  │
│  │ status() | connectVapi() | etc    │  │
│  └───────────────────────────────────┘  │
└──────────────────┬──────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────┐
│      Backend API (IntegrationsController)│
│  POST /api/v1/integrations              │
│  GET  /api/v1/integrations              │
│  GET  /api/v1/integrations/{id}         │
│  POST /api/v1/integrations/{id}/test    │
│  DELETE /api/v1/integrations/{id}       │
└─────────────────────────────────────────┘
```

## Limitations

This is an MVP with some intentional scope limits:

- **No bulk operations** - Connect one integration at a time
- **Limited OAuth automation** - Still requires browser for OAuth
- **Basic error handling** - Verbose mode shows full errors
- **No credential validation** - Validates on test, not on input
- **No local storage** - Credentials stored server-side only

## Future Enhancements

Potential additions for v2:
- Agent integration mapping (enable/disable per agent)
- Bulk connect/disconnect operations
- OAuth device flow for CLI
- Local credential caching
- Integration templates/presets
- Webhook management
- Integration usage analytics
- Auto-retry on connection failures

## Documentation

- **Quick Reference**: `docs/INTEGRATION_MANAGEMENT_QUICK_REFERENCE.md`
- **Full Plan**: `docs/INTEGRATION_MANAGEMENT_SDK_CLI_PLAN.md`
- **Examples**: `examples/integrations-management.php`
- **Tests**: `test-integrations-mvp.php`

## Support

Need help?

1. **Check examples** - `examples/integrations-management.php`
2. **Read quick reference** - Complete SDK/CLI guide
3. **Run tests** - `php test-integrations-mvp.php`
4. **Use verbose mode** - Add `-v` to CLI commands
5. **Check error messages** - Detailed error info provided

## Contributing

This MVP is feature-complete but extensible. To add new integration types:

1. Add type to backend `IntegrationsController.php`
2. Optionally add type-specific connect method in SDK
3. Add auth detection in `usesOAuth()` or `usesApiKey()`
4. Update CLI prompts in `connectApiKey()` if needed

## License

Same as IRIS SDK license.

## Credits

Built as MVP implementation of the integration management feature request.

---

**Status**: ✅ MVP Complete and Ready for Testing

**Version**: 1.0.0

**Last Updated**: December 26, 2025
