# IRIS PHP SDK - Lead Aggregation Test

Test script for the Lead Aggregation API with easy environment configuration.

## Quick Start

1. **Copy the example environment file:**
   ```bash
   cp .env.example .env
   ```

2. **Add your API key to `.env`:**
   ```bash
   IRIS_API_KEY=your_actual_api_key_here
   IRIS_USER_ID=193
   IRIS_ENV=local
   ```

3. **Run the test:**
   ```bash
   php test-lead-aggregation-user-193.php
   ```

## Configuration

The test script reads from `.env` file:

### Local Development (Default)
```env
IRIS_API_KEY=your_api_key
IRIS_USER_ID=193
IRIS_ENV=local
FL_API_LOCAL_URL=https://local.raichu.freelabel.net
```

### Production Testing
```env
IRIS_API_KEY=your_production_key
IRIS_USER_ID=193
IRIS_ENV=production
FL_API_URL=https://apiv2.heyiris.io
```

## What It Tests

1. **Lead Statistics** - Total leads, tasks, incomplete tasks, active leads
2. **Lead List** - Paginated list with priority scoring
3. **Lead Details** - Full lead information with tasks
4. **Requirements Extraction** - AI-parsed requirements from lead data

## For Developers

Just add your API token to `.env` and run! The test automatically:
- ✅ Connects to correct environment (local/production)
- ✅ Handles SSL for local development
- ✅ Shows clear output with statistics
- ✅ Gracefully handles empty results

## Environment Variables

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `IRIS_API_KEY` | Yes | - | Your API authentication key |
| `IRIS_USER_ID` | No | 193 | User ID for testing |
| `IRIS_ENV` | No | local | Environment: `local` or `production` |
| `FL_API_LOCAL_URL` | No | https://local.raichu.freelabel.net | Local FL-API URL (leads, deliverables) |
| `FL_API_URL` | No | https://apiv2.heyiris.io | Production FL-API URL |

## Output Example

```
🔧 Configuration:
   Environment: local
   Base URL: https://local.raichu.freelabel.net
   User ID: 193
   API Key: sk_test...

🔍 Fetching Lead Aggregation Data for User 193
============================================================

📊 Lead Statistics:
  ✓ Total Leads: 0
  ✓ Total Tasks: 0
  ✓ Incomplete Tasks: 0
  ✓ Active Leads: 0

✅ Test completed successfully!
```

## Security Note

**Never commit `.env` file to git!** It's already in `.gitignore`.
Always use `.env.example` as a template for other developers.
