# Backwards Compatibility Fix - December 2025

## Problem
The SDK was failing with 530 errors when calling leads endpoints because the routing logic had incorrect default URLs.

## Root Cause
- **Legacy naming confusion**: `IRIS_API_URL` env variable actually points to FL-API (`apiv2.heyiris.io`)
- The Config.php was loading this value but applying it incorrectly

## Solution Applied

### 1. Updated Config.php defaults (line 27-35):
```php
// OLD (incorrect):
public string $irisUrl = 'https://iris.freelabel.net';
public string $flApiUrl = 'https://raichu.freelabel.net';

// NEW (correct):
public string $irisUrl = 'https://iris.freelabel.net';
public string $flApiUrl = 'https://apiv2.heyiris.io';
```

### 2. Updated .env loading logic (line 201-206):
```php
// Production URLs - respects legacy IRIS_API_URL naming
$config['base_url'] = $env['IRIS_API_URL'] ?? 'https://apiv2.heyiris.io';
$config['iris_url'] = $env['IRIS_API_URL'] ?? 'https://apiv2.heyiris.io';
$config['fl_api_url'] = $env['FL_API_URL'] ?? $env['IRIS_API_URL'] ?? 'https://apiv2.heyiris.io';
```

## Backwards Compatibility
✅ **Old .env files continue to work** - `IRIS_API_URL=https://apiv2.heyiris.io` still works
✅ **New .env files supported** - Can now use `FL_API_URL` separately
✅ **Defaults are correct** - If no .env, uses correct production URLs

## Testing
```bash
# All these now work:
php bin/iris sdk:call leads.search bloq_id=40
php bin/iris sdk:call leads.aggregation.statistics
php bin/iris sdk:call leads.aggregation.list sort=priority order=desc per_page=10
```

## Architecture Clarification

### Production APIs:
- **FL-API** (`apiv2.heyiris.io`): leads, deliverables, profiles, services, agents management
- **IRIS-API** (`iris-api.freelabel.net`): chat endpoints, workflows, RAG (V5 system)

### Routing Logic (Client.php line 249-274):
- `/leads`, `/deliverables`, `/profile`, `/services`, `/integrations` → FL-API
- `/iris/`, `/chat/`, `/workflows/`, `/bloqs/` → IRIS-API

## Status: ✅ FIXED AND TESTED
