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
./iris setup
```

Follow the prompts to:
1. Choose your API environment (production/local/custom)
2. Authenticate with email/password
3. Automatically generate an API token

That's it! Your `.env` file is now configured.

## Try It Out

### Chat with an Agent

```bash
./iris chat
```

### List Your Agents

```bash
./iris agents
```

### Manage Knowledge Bases

```bash
# List knowledge bases
./iris memory:list

# View details
./iris memory:show 42

# Add content
./iris memory:add 42 --text="Your content here" --title="Title"

# AI-powered setup
./iris memory:compose
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

| Command | Description |
|---------|-------------|
| `./iris setup` | Interactive authentication setup |
| `./iris chat` | Start chat session |
| `./iris agents` | List available agents |
| `./iris memory:list` | List knowledge bases |
| `./iris memory:compose` | AI-powered KB creation |
| `./iris config:show` | View configuration |

## Need Help?

- Full Documentation: See [README.md](README.md)
- Issues: https://github.com/FREELABEL/fl-iris-sdk-php/issues
- Support: support@freelabel.net

## What's Next?

1. **Create Knowledge Bases**: Use `./iris memory:compose` to let AI analyze your files
2. **Build Agents**: Visit https://app.heyiris.io to create custom AI agents
3. **Integrate**: Use the SDK in your PHP applications

Happy building! 🚀
