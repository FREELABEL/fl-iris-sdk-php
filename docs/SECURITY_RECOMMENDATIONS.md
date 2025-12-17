# Security Recommendations for FL-API

This document outlines security issues discovered during SDK integration testing and provides recommendations for hardening the API.

## Critical Issues

### 1. Leads Endpoint is Public (No Authentication)

**Location:** `routes/api/lead-management-routes.php`

**Issue:** The `/api/v1/leads/*` endpoints are completely public, allowing anyone to:
- List all leads in the system
- Create new leads
- Update existing leads
- Delete leads

**Risk Level:** HIGH

**Evidence from Integration Tests:**
```
TEST: Leads
  ▸ List Leads... ✅ Found 10 leads
  ▸ Create Lead... ✅ Created lead ID: 1780
  ▸ Delete Lead... ✅ Deleted
```

**Recommended Fix:**

```php
// routes/api/lead-management-routes.php

// BEFORE (Insecure):
Route::prefix('v1')->group(function () {
    Route::get('leads', [LeadController::class, 'index']);
    Route::post('leads', [LeadController::class, 'store']);
    // ...
});

// AFTER (Secure):
Route::prefix('v1')->middleware(['auth:api'])->group(function () {
    Route::get('leads', [LeadController::class, 'index']);
    Route::post('leads', [LeadController::class, 'store']);
    // ...
});
```

### 2. Agent Chat Endpoint Lacks Owner Validation

**Location:** `routes/api.php` line ~233

**Issue:** The `/api/v1/bloqs/agents/generate-response` endpoint allows chatting with any agent by ID, regardless of ownership or public status.

**Risk Level:** MEDIUM

**Recommended Fix:**

```php
// app/Http/Controllers/Bloq/AgentController.php

public function generateResponse(Request $request)
{
    $request->validate([
        'agent_id' => 'required|integer',
        'messages' => 'required|array'
    ]);

    $agent = Agent::findOrFail($request->agent_id);

    // SECURITY CHECK: Validate access
    if (!$agent->is_public) {
        // If not public, require authentication
        if (!auth('api')->check()) {
            return response()->json([
                'error' => 'Unauthorized: This agent is private.'
            ], 401);
        }

        // If authenticated, check ownership
        if (auth('api')->id() !== $agent->user_id) {
            return response()->json([
                'error' => 'Forbidden: You do not own this agent.'
            ], 403);
        }
    }

    // Proceed with generation...
}
```

### 3. Integration List Endpoint Missing User Context

**Location:** `routes/api.php`

**Issue:** The `/api/v1/integrations` endpoint returns 401 with Bearer token but should scope results to the authenticated user.

**Risk Level:** LOW (Currently protected by auth)

**Recommended Enhancement:**

```php
// app/Http/Controllers/Api/IntegrationsController.php

public function index(Request $request)
{
    $user = $request->user();

    // Scope to user's integrations only
    $integrations = Integration::where('user_id', $user->id)->get();

    return response()->json(['data' => $integrations]);
}
```

## Middleware Architecture Review

### Current State

| Route Pattern | Current Middleware | Should Be |
|--------------|-------------------|-----------|
| `/api/v1/leads/*` | (none) | `auth:api` |
| `/api/v1/bloqs/agents/generate-response` | (none) | Ownership check |
| `/api/v1/users/{userId}/bloqs/agents/*` | `client` | `client` (correct) |
| `/api/v1/user/{userId}/bloqs/*` | `client` | `client` (correct) |
| `/api/v1/integrations` | `auth:api` | `auth:api` (correct) |

### Recommended Middleware Groups

```php
// routes/api.php

// Public endpoints (rate-limited)
Route::prefix('v1')->middleware(['throttle:public'])->group(function () {
    Route::get('integrations/types', [IntegrationsController::class, 'types']);
    Route::get('public/agent/{slug}/info', [PublicAgentController::class, 'info']);
});

// User-authenticated endpoints
Route::prefix('v1')->middleware(['auth:api'])->group(function () {
    Route::apiResource('leads', LeadController::class);
    Route::resource('integrations', IntegrationsController::class);
});

// Machine-to-machine endpoints (SDK management)
Route::prefix('v1')->middleware(['client'])->group(function () {
    Route::resource('users/{userId}/bloqs/agents', AgentController::class);
    Route::resource('user/{userId}/bloqs', BloqController::class);
});
```

## Rate Limiting Recommendations

### Current Configuration (RouteServiceProvider.php)

```php
// Authenticated: 500/min (production)
// Unauthenticated: 200/min (production)
```

### Recommended Additional Limiters

```php
// routes/api.php

// AI generation endpoints (expensive operations)
Route::middleware(['throttle:ai-generation'])->group(function () {
    Route::post('bloqs/agents/generate-response', ...);
    Route::post('bloqs/agents/multi-step-response', ...);
});

// RouteServiceProvider.php
RateLimiter::for('ai-generation', function (Request $request) {
    return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
});
```

## Environment Variable Security

Ensure these are NEVER exposed in client-side code:

```bash
# API Keys (server-side only)
OPENAI_API_KEY=
ANTHROPIC_API_KEY=
PASSPORT_CLIENT_SECRET=

# Database credentials
DB_PASSWORD=

# Encryption keys
APP_KEY=
PASSPORT_PRIVATE_KEY=
```

## Testing Security After Implementation

After implementing fixes, run these tests to verify:

```bash
# Test 1: Leads should require auth
curl -s http://localhost:8000/api/v1/leads
# Expected: {"message":"Unauthenticated."}

# Test 2: Private agent should require auth
curl -s -X POST http://localhost:8000/api/v1/bloqs/agents/generate-response \
  -H "Content-Type: application/json" \
  -d '{"agent_id": 123, "messages": [{"role": "user", "content": "test"}]}'
# Expected: {"error":"Unauthorized: This agent is private."}

# Test 3: Public agent should work without auth
curl -s -X POST http://localhost:8000/api/v1/bloqs/agents/generate-response \
  -H "Content-Type: application/json" \
  -d '{"agent_id": 456, "messages": [{"role": "user", "content": "Hello"}]}'
# Expected: {"content": "...response..."}
```

## Implementation Priority

1. **Immediate (P0):** Add `auth:api` middleware to leads routes
2. **High (P1):** Add ownership validation to agent generate endpoint
3. **Medium (P2):** Review all public endpoints for similar issues
4. **Low (P3):** Implement AI-specific rate limiting

## Contact

For security concerns or questions about this document, contact the development team.
