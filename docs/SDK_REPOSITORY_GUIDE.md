# IRIS SDK Repository Management Guide

This document outlines the architecture and management approach for IRIS SDK repositories across multiple programming languages.

## Repository Strategy

We use **separate repositories per language** - the same approach used by Stripe, Twilio, and OpenAI SDKs.

### Repository Structure

```
github.com/FREELABEL/
├── fl-iris-sdk-php      ← PHP SDK (Active)
├── fl-iris-sdk-python   ← Python SDK (Future)
├── fl-iris-sdk-node     ← Node.js SDK (Future)
└── fl-iris-sdk-go       ← Go SDK (Future, if needed)
```

### Local Development Structure

```
/Users/AlexMayo/Sites/freelabel/fl-docker-dev/sdk/
├── SDK_REPOSITORY_GUIDE.md    ← This file (not version controlled)
├── php/                       ← git@github.com:FREELABEL/fl-iris-sdk-php.git
├── python/                    ← git@github.com:FREELABEL/fl-iris-sdk-python.git (future)
└── node/                      ← git@github.com:FREELABEL/fl-iris-sdk-node.git (future)
```

**Important:** The parent `/sdk` directory is NOT a git repository. Each language subdirectory is its own independent git repo with its own remote.

---

## Current SDKs

### PHP SDK (`fl-iris-sdk-php`)

**Status:** Active, Production Ready

**Repository:** `git@github.com:FREELABEL/fl-iris-sdk-php.git`

**Local Path:** `/Users/AlexMayo/Sites/freelabel/fl-docker-dev/sdk/php`

**Features:**
- Full IRIS API coverage (Agents, Leads, Workflows, RAG, Integrations)
- CLI tool (`bin/iris`)
- Agent Evaluation Harness
- Laravel integration support

**Commands:**
```bash
cd /Users/AlexMayo/Sites/freelabel/fl-docker-dev/sdk/php

# Check status
git status

# Push changes
git add . && git commit -m "Your message" && git push origin main

# Pull latest
git pull origin main
```

---

## Adding a New SDK

When creating a new language SDK (e.g., Python), follow these steps:

### 1. Create the GitHub Repository

```bash
# Create repo on GitHub: fl-iris-sdk-python
# Then clone it locally:
cd /Users/AlexMayo/Sites/freelabel/fl-docker-dev/sdk
git clone git@github.com:FREELABEL/fl-iris-sdk-python.git python
```

### 2. Set Up the SDK Structure

Each SDK should follow a consistent structure:

```
sdk-name/
├── README.md              # Quick start guide
├── CHANGELOG.md           # Version history
├── LICENSE                # MIT License
├── .gitignore             # Language-specific ignores
├── src/                   # Source code
│   ├── iris.py            # Main client (or iris.js, etc.)
│   ├── resources/         # API resources (agents, leads, etc.)
│   └── exceptions/        # Custom exceptions
├── tests/                 # Test suite
├── examples/              # Usage examples
└── docs/                  # Additional documentation
```

### 3. Core Features to Implement

All SDKs should support these core features (in priority order):

1. **Authentication** - API key and OAuth token handling
2. **Agents** - CRUD, chat, multi-step workflows
3. **Leads** - CRUD, search, notes, tasks
4. **Chat** - Real-time agent conversations
5. **Integrations** - List, execute, manage
6. **RAG** - Vector search, document indexing
7. **Workflows** - V5 workflow execution
8. **Evaluation** - Agent testing harness

### 4. Naming Conventions

| Language | Package Name | Repository | Main Class |
|----------|--------------|------------|------------|
| PHP | `iris-ai/sdk` | `fl-iris-sdk-php` | `IRIS\SDK\IRIS` |
| Python | `iris-sdk` | `fl-iris-sdk-python` | `iris.IRIS` |
| Node.js | `@iris-ai/sdk` | `fl-iris-sdk-node` | `IRIS` |
| Go | `iris-sdk-go` | `fl-iris-sdk-go` | `iris.Client` |

---

## Version Management

### Semantic Versioning

All SDKs follow [Semantic Versioning](https://semver.org/):

- **MAJOR** - Breaking API changes
- **MINOR** - New features (backwards compatible)
- **PATCH** - Bug fixes (backwards compatible)

### Keeping SDKs in Sync

When adding a new feature to the IRIS API:

1. Implement in PHP SDK first (reference implementation)
2. Document the API contract
3. Implement in other SDKs following the same contract
4. Update CHANGELOG in each SDK

### Feature Parity Tracking

Maintain a feature matrix to track parity:

| Feature | PHP | Python | Node |
|---------|-----|--------|------|
| Agents CRUD | ✅ | ⬜ | ⬜ |
| Agent Chat | ✅ | ⬜ | ⬜ |
| Multi-step Workflows | ✅ | ⬜ | ⬜ |
| Leads CRUD | ✅ | ⬜ | ⬜ |
| RAG/Vector Search | ✅ | ⬜ | ⬜ |
| Integrations | ✅ | ⬜ | ⬜ |
| CLI Tool | ✅ | ⬜ | ⬜ |
| Evaluation Harness | ✅ | ⬜ | ⬜ |

---

## CI/CD Guidelines

Each SDK should have:

### GitHub Actions Workflows

```yaml
# .github/workflows/test.yml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Run tests
        run: # language-specific test command
```

### Release Process

1. Update version in package file (`composer.json`, `setup.py`, `package.json`)
2. Update CHANGELOG.md
3. Create git tag: `git tag v1.2.3`
4. Push tag: `git push origin v1.2.3`
5. Create GitHub Release with notes
6. Publish to package registry (Packagist, PyPI, npm)

---

## Documentation Standards

### README.md Requirements

Every SDK README should include:

1. **Installation** - Package manager commands
2. **Quick Start** - Minimal working example
3. **Authentication** - How to configure API keys
4. **Basic Usage** - Common operations
5. **CLI Usage** - If CLI is available
6. **API Reference** - Link to full docs
7. **Contributing** - How to contribute
8. **License** - MIT

### Code Documentation

- PHP: PHPDoc comments
- Python: Docstrings (Google style)
- Node.js: JSDoc comments
- Go: Godoc comments

---

## Testing Standards

### Unit Tests

- Mock HTTP responses
- Test all public methods
- Test error handling
- Aim for >80% coverage

### Integration Tests

- Use test API credentials
- Test against staging environment
- Mark as `@group integration`

### Evaluation Harness

Each SDK should include the Agent Evaluation Harness:

```
src/Evaluation/
├── EvaluationTest.php    # Test definition class
└── AgentEvaluator.php    # Test runner class
```

Core tests to include:
- `basic_conversation`
- `web_search_capability`
- `market_research`
- `personalization`
- `complex_reasoning`
- `tool_integration`
- `error_handling`

---

## Common Issues & Solutions

### Issue: Wrong directory when pushing

**Problem:** Accidentally pushing from parent `/sdk` directory

**Solution:** Always `cd` into the specific language directory first:
```bash
cd /Users/AlexMayo/Sites/freelabel/fl-docker-dev/sdk/php
git push origin main
```

### Issue: Feature not in all SDKs

**Problem:** New feature added to PHP but not Python/Node

**Solution:**
1. Create GitHub issue in each SDK repo
2. Link issues together
3. Track in feature parity matrix

### Issue: Breaking API change

**Problem:** IRIS API changed, SDK needs update

**Solution:**
1. Update PHP SDK first (reference)
2. Bump MAJOR version
3. Update migration guide
4. Propagate to other SDKs

---

## Quick Reference

### PHP SDK Commands

```bash
# Navigate to SDK
cd /Users/AlexMayo/Sites/freelabel/fl-docker-dev/sdk/php

# Run tests
composer test

# Run CLI
php bin/iris <command>

# Evaluate an agent
php bin/iris eval 387

# Push changes
git add . && git commit -m "message" && git push origin main
```

### Future Python SDK Commands

```bash
cd /Users/AlexMayo/Sites/freelabel/fl-docker-dev/sdk/python

# Run tests
pytest

# Run CLI
iris <command>

# Evaluate an agent
iris eval 387
```

### Future Node SDK Commands

```bash
cd /Users/AlexMayo/Sites/freelabel/fl-docker-dev/sdk/node

# Run tests
npm test

# Run CLI
npx iris <command>

# Evaluate an agent
npx iris eval 387
```

---

## Contact & Support

- **SDK Issues:** Open issue in respective GitHub repo
- **API Issues:** Open issue in `fl-api` repo
- **Documentation:** Update this guide as needed

---

*Last Updated: December 29, 2025*
