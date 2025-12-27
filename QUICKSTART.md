# IRIS SDK - Quick Start Guide

Get up and running with the IRIS SDK in 2 minutes.

## Installation

```bash
git clone https://github.com/FREELABEL/fl-iris-sdk-php.git
cd fl-iris-sdk-php
composer install
```

## Setup

Run the interactive setup:

```bash
# Option 1: Using npm (recommended - shortest command)
npm run setup

# Option 2: Direct execution
php bin/iris setup

# Option 3: If executable bit is set
./iris setup

# Option 4: Via composer (after global install)
iris setup
```

Follow the prompts to:
1. Choose your API environment (production/local/custom)
2. Authenticate with email/password
3. Automatically generate an API token

That's it! Your `.env` file is now configured.

## Try It Out

### Chat with an Agent

```bash
# Option 1: Using npm
npm run chat

# Option 2: Direct execution
php bin/iris chat

# Option 3: If executable bit is set
./iris chat
```

### List Your Agents

```bash
# Using npm
npm run agents

# Or directly
php bin/iris agents
```

### Manage Knowledge Bases

```bash
# List knowledge bases
npm run memory:list
# or: php bin/iris memory:list

# View details
npm run memory:show 42
# or: php bin/iris memory:show 42

# Add content
npm run memory:add 42 --text="Your content here" --title="Title"
# or: php bin/iris memory:add 42 --text="Your content here" --title="Title"

# AI-powered setup
npm run memory:compose
# or: php bin/iris memory:compose
```

## Manual Setup (Alternative)

If you prefer manual configuration, create `.env`:

```bash
IRIS_API_URL=https://apiv2.heyiris.io
IRIS_API_KEY=your_api_token_here
IRIS_USER_ID=your_user_id
IRIS_DEFAULT_MODEL=gpt-4o-mini
```

## Using in Your PHP Code

```php
<?php
require_once 'vendor/autoload.php';

use IRIS\SDK\IRIS;

$iris = new IRIS([
    'api_key' => getenv('IRIS_API_KEY'),
    'user_id' => getenv('IRIS_USER_ID')
]);

// Create knowledge base
$bloq = $iris->bloqs->create('My KB');

// Add content
$iris->bloqs->addContent($bloq->id, [
    'title' => 'Getting Started',
    'content' => 'Welcome to IRIS!'
]);

// Chat with agent
$response = $iris->agents->chat(123, [
    'message' => 'Hello!',
    'bloq_id' => $bloq->id
]);

echo $response['message'];
```

## Common Commands

| NPM Command | Direct Command | Description |
|-------------|----------------|-------------|
| `npm run setup` | `php bin/iris setup` | Interactive authentication setup |
| `npm run chat` | `php bin/iris chat` | Start chat session |
| `npm run agents` | `php bin/iris agents` | List available agents |
| `npm run memory:list` | `php bin/iris memory:list` | List knowledge bases |
| `npm run memory:compose` | `php bin/iris memory:compose` | AI-powered KB creation |
| `npm run config:show` | `php bin/iris config:show` | View configuration |

**Tip:** Use `npm run <command>` for shorter commands, or `php bin/iris <command>` for direct execution.

## Need Help?

- Full Documentation: See [README.md](README.md)
- Issues: https://github.com/FREELABEL/fl-iris-sdk-php/issues
- Support: support@freelabel.net

## What's Next?

1. **Create Knowledge Bases**: Use `npm run memory:compose` to let AI analyze your files
2. **Build Agents**: Visit https://app.heyiris.io to create custom AI agents
3. **Integrate**: Use the SDK in your PHP applications

Happy building! 🚀
