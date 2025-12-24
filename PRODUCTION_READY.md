# IRIS SDK - Setup Summary

## SDK is Production Ready!

The SDK uses **environment-based configuration** via `.env` file for simple, secure credential management.

### What's Included

1. **Environment-Based Authentication**
   - All credentials loaded from `.env` file
   - Switch between local/production with `IRIS_ENV`
   - No OAuth needed for 99% of operations

2. **Simple Setup**
   - Copy `.env.example` to `.env`
   - Set your API key and user ID
   - Run `./bin/iris config` to verify

3. **Multi-Environment Support**
   - `IRIS_ENV=local` → uses local development URLs
   - `IRIS_ENV=production` → uses production URLs
   - Override at runtime: `IRIS_ENV=local ./bin/iris chat ...`

---

## Quick Start

### 1. Copy Environment File
```bash
cp .env.example .env
```

### 2. Edit `.env` with Your Credentials
```bash
# Set environment (local or production)
IRIS_ENV=production

# Your user ID
IRIS_USER_ID=your_user_id

# Production API key (get from Developer Portal)
IRIS_API_KEY=your_production_api_key

# Local development key (optional)
IRIS_LOCAL_API_KEY=your_local_api_key
```

### 3. Verify Configuration
```bash
./bin/iris config
```

Expected output:
```
IRIS SDK Configuration
======================

 Environment: production

 ---------- -------- -------------------
  Setting    Status   Description
 ---------- -------- -------------------
  API Key    ✓ Set    Required
  User ID    ✓ Set    Required
  Base URL   ✓ Set    FL-API endpoint
  IRIS URL   ✓ Set    IRIS-API endpoint
 ---------- -------- -------------------

 [OK] SDK is ready to use!
```

### 4. Test API Connection
```bash
./bin/iris config test
```

### 5. Start Using!
```bash
# Chat with an agent
./bin/iris chat <agent_id> "Hello!"

# List your agents
./bin/iris sdk:call agents.list

# Search leads
./bin/iris sdk:call leads.search "email"
```

---

## Getting Your API Key

### Option 1: From Developer Portal (Recommended)
1. Sign up at https://heyiris.io/
2. Go to Developer section
3. Generate API Token
4. Copy token + user ID

### Option 2: From Laravel Tinker (Admin)
```bash
docker compose exec api php artisan tinker
>>> $user = User::find(193);
>>> $token = $user->createToken('SDK Access');
>>> echo $token->accessToken;
```

---

## What You Can Do

**With just an API token:**
- Chat with agents
- Search/update leads
- Create tasks & deliverables
- Execute workflows
- Upload files (with RAG indexing)
- Get analytics
- Create and manage agents
- Manage bloqs/projects

---

## Environment Variables Reference

| Variable | Description | Required |
|----------|-------------|----------|
| `IRIS_ENV` | Environment: `local` or `production` | Yes |
| `IRIS_USER_ID` | Your numeric user ID | Yes |
| `IRIS_API_KEY` | Production API token | For production |
| `IRIS_LOCAL_API_KEY` | Local development token | For local dev |
| `FL_API_URL` | Production FL-API URL | Auto-set |
| `FL_API_LOCAL_URL` | Local FL-API URL | Auto-set |
| `IRIS_API_URL` | Production IRIS URL | Auto-set |
| `IRIS_LOCAL_URL` | Local IRIS URL | Auto-set |

---

## Testing

SDK tested and working with production:
- Agent chat
- Lead management
- RAG file attachments
- Workflow execution
- CloudFiles upload/download
- Multi-environment switching

---

## Troubleshooting

### "SDK not configured" Error
```bash
# Check configuration
./bin/iris config

# Verify .env file exists
ls -la .env
```

### "Unauthorized" Errors
```bash
# Test API connection
./bin/iris config test

# Check API key is set correctly
./bin/iris config
```

### Switch Environments
```bash
# Edit .env
IRIS_ENV=local

# Or override at runtime
IRIS_ENV=local ./bin/iris chat 12 "Hello"
```
