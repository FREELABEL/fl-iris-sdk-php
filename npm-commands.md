# IRIS SDK - NPM Commands Quick Reference

## Installation & Setup

```bash
git clone https://github.com/FREELABEL/fl-iris-sdk-php.git
cd fl-iris-sdk-php
composer install
npm run setup
```

## Available Commands

### Setup & Configuration
```bash
npm run setup          # Interactive authentication setup
npm run config:show    # Display current configuration
```

### Agent Operations
```bash
npm run agents         # List all available agents
npm run chat          # Start interactive chat with an agent
```

### Knowledge Base (Memory) Operations
```bash
npm run memory:list              # List all knowledge bases
npm run memory:show <id>         # Show details of a specific KB
npm run memory:add <id>          # Add content to a KB
npm run memory:compose           # AI-powered KB creation wizard
```

## Examples

### First Time Setup
```bash
npm run setup
# Follow the interactive prompts:
# 1. Choose API environment (production/local)
# 2. Enter email and password
# 3. Set default AI model
# Done! .env file created
```

### List Your Agents
```bash
npm run agents
# Output: List of all your AI agents with IDs and names
```

### Create a Knowledge Base
```bash
npm run memory:compose
# AI will guide you through:
# - Analyzing your files
# - Selecting relevant content
# - Creating an optimized knowledge base
```

### Add Content to Knowledge Base
```bash
npm run memory:add 42 --text="Product documentation" --title="Docs"
# Adds text content to knowledge base #42
```

## Direct PHP Commands (Alternative)

If you prefer, all commands can also be run directly:

```bash
php bin/iris setup
php bin/iris agents
php bin/iris memory:list
# etc.
```

## Tips

- Use `npm run <command>` for shorter, more memorable commands
- All commands support `--help` flag for detailed options
- Configuration is stored in `.env` file
- Keep your `.env` file private (already in .gitignore)

## Troubleshooting

**"Command not found"**
- Make sure you ran `composer install` first
- Verify you're in the SDK root directory

**"Authentication failed"**
- Run `npm run setup` to reconfigure
- Check your API URL is correct
- Verify your credentials

**"Cannot find module"**
- Run `composer install` to install PHP dependencies
- Node.js is only needed for npm scripts (not for SDK itself)

## Learn More

- Full documentation: See README.md
- Quick start: See QUICKSTART.md
- Issues: https://github.com/FREELABEL/fl-iris-sdk-php/issues
