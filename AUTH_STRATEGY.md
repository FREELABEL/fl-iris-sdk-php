# IRIS SDK Authentication Strategy

## TL;DR - What to Give Users

### ✅ **Recommended: Simple API Token (For Most Users)**
**Just provide:**
- User API Token (Bearer token)
- User ID

**Works for:**
- ✅ Chat with agents
- ✅ Lead management (search, update, tasks, deliverables)
- ✅ Lead aggregation & analytics
- ✅ Workflows (execute, status)
- ✅ File uploads
- ✅ 90% of SDK functionality

**Setup:**
```bash
./bin/iris config setup
# Enter API token + User ID
# Done!
```

---

### 🔧 **Optional: OAuth Client Credentials (Advanced Users)**
**Only needed for:**
- ❌ Creating/updating agents programmatically
- ❌ Managing bloqs (create/delete/share)
- ❌ Content management endpoints
- ❌ Advanced admin operations

**Most users DON'T need this!**

---

## Detailed Breakdown

### What Each Authentication Type Does

| Operation | User Token Only | + OAuth Credentials |
|-----------|----------------|---------------------|
| **Chat with agents** | ✅ | ✅ |
| **Search leads** | ✅ | ✅ |
| **Update lead status** | ✅ | ✅ |
| **Create tasks** | ✅ | ✅ |
| **Add deliverables** | ✅ | ✅ |
| **Lead analytics** | ✅ | ✅ |
| **Execute workflows** | ✅ | ✅ |
| **Upload files** | ✅ | ✅ |
| **Create agents** | ❌ | ✅ |
| **Update agents** | ❌ | ✅ |
| **Manage bloqs** | ❌ | ✅ |
| **Share knowledge base** | ❌ | ✅ |

---

## UI Implementation Strategy

### Option 1: Simple (Recommended for MVP)

**In User Settings:**
```
┌────────────────────────────────────────┐
│  API Settings                          │
├────────────────────────────────────────┤
│  Your API Token:                       │
│  ┌──────────────────────────────────┐  │
│  │ eyJ0eXAiOiJKV1QiLCJh...         │  │
│  │ [Copy]  [Regenerate]             │  │
│  └──────────────────────────────────┘  │
│                                        │
│  Your User ID: 193                     │
│                                        │
│  ℹ️  Use this token to access the SDK │
│     for chat, leads, and workflows.   │
└────────────────────────────────────────┘
```

**Benefits:**
- Simple for users
- One-click copy
- Works for 90% of use cases
- No confusion about client_id vs token

### Option 2: Advanced (If Users Need Agent Management)

**Add a separate "Developer Settings" section:**
```
┌────────────────────────────────────────┐
│  Developer Settings (Advanced)         │
├────────────────────────────────────────┤
│  ⚠️  Only needed for programmatic      │
│      agent/bloq management             │
│                                        │
│  OAuth Client Credentials:             │
│  ┌──────────────────────────────────┐  │
│  │ Client ID:                        │  │
│  │ a0a9f7d9-d97c-4b5d-a80b...       │  │
│  │                                   │  │
│  │ Client Secret:                    │  │
│  │ TQtaXZikuIwVLIfFGdKGpE9...       │  │
│  │                                   │  │
│  │ [Generate New Credentials]        │  │
│  └──────────────────────────────────┘  │
└────────────────────────────────────────┘
```

---

## Where the User Token Comes From

Looking at your working curl:
```bash
authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...
```

This is a **Laravel Passport Personal Access Token**. Generate it via:

### Backend API Endpoint (Recommended):
```php
// In your Laravel API
Route::post('/api/user/generate-token', function (Request $request) {
    $user = $request->user();
    
    // Create a personal access token
    $token = $user->createToken('SDK Access')->accessToken;
    
    return response()->json([
        'token' => $token,
        'user_id' => $user->id,
    ]);
});
```

### Or Artisan Command (for manual generation):
```bash
php artisan passport:token {user_id} --name="SDK Access"
```

---

## Implementation Checklist

### Phase 1: Simple Token (Launch First) ✅

- [ ] Add "API Token" section to user settings
- [ ] API endpoint: `POST /api/user/generate-token`
- [ ] Show user their token with copy button
- [ ] Show user their User ID
- [ ] Add regenerate button (invalidates old token)
- [ ] Documentation: "Getting Started with SDK"

### Phase 2: Advanced OAuth (If Needed) 🔧

- [ ] Add "Developer Settings" advanced section
- [ ] API endpoint: `POST /api/user/create-oauth-client`
- [ ] Create OAuth2 client via Passport
- [ ] Show client_id and client_secret (only once!)
- [ ] Add warning: "Only needed for agent management"

---

## Example UI Flow

### Simple Flow (Most Users):
1. User goes to Settings → API
2. Clicks "Generate API Token"
3. Copies token
4. Runs: `./bin/iris config setup`
5. Pastes token
6. Done! Can chat, manage leads, run workflows

### Advanced Flow (Developers):
1. User goes to Settings → Developer
2. Clicks "Generate OAuth Client"
3. Shows client_id + client_secret
4. User saves both
5. Runs: `./bin/iris config setup`
6. Enters token + client credentials
7. Can now create/manage agents programmatically

---

## Security Notes

### User Token (API Token):
- ✅ User-scoped (can only access their own data)
- ✅ Can be regenerated anytime
- ✅ Safer to share (limited permissions)
- ⚠️ Store in `~/.iris/credentials.json` (file permissions 600)

### OAuth Credentials:
- ⚠️ More powerful (can create agents/bloqs)
- ⚠️ Client secret shown ONLY ONCE
- ⚠️ Harder to regenerate
- 🔒 Use only for server-to-server

---

## Recommendation

**Start with Option 1 (Simple Token)** because:
1. ✅ Covers 90% of SDK use cases
2. ✅ Easier for users to understand
3. ✅ Faster to implement
4. ✅ More secure (fewer credentials to manage)
5. ✅ Can add OAuth later if needed

Most users want to:
- Chat with their agents ✅ (Token only)
- Manage leads ✅ (Token only)
- Run workflows ✅ (Token only)

Very few need to programmatically create agents.

---

## Code Examples

### Generate User Token (Add to Laravel API):
```php
// routes/api.php
Route::middleware('auth:api')->group(function () {
    Route::post('/user/sdk-token', function (Request $request) {
        $user = $request->user();
        
        // Revoke old SDK tokens
        $user->tokens()->where('name', 'SDK Access')->delete();
        
        // Create new token
        $token = $user->createToken('SDK Access')->accessToken;
        
        return response()->json([
            'success' => true,
            'token' => $token,
            'user_id' => $user->id,
            'instructions' => 'Run: ./bin/iris config setup',
        ]);
    });
    
    Route::post('/user/regenerate-sdk-token', function (Request $request) {
        // Same as above
    });
});
```

### Generate OAuth Client (Optional, Advanced):
```php
Route::middleware('auth:api')->post('/user/oauth-client', function (Request $request) {
    $user = $request->user();
    
    // Create OAuth2 client
    $client = Laravel\Passport\Client::create([
        'user_id' => $user->id,
        'name' => 'SDK Access - ' . $user->name,
        'secret' => Str::random(40),
        'redirect' => '',
        'personal_access_client' => false,
        'password_client' => false,
        'revoked' => false,
    ]);
    
    return response()->json([
        'success' => true,
        'client_id' => $client->id,
        'client_secret' => $client->secret,
        'warning' => 'Save this secret now - it will not be shown again!',
    ]);
});
```

---

## Final Answer

**Give users a simple API token (Bearer token).** That's it!

Add one button in settings:
```
[Generate API Token]
```

Shows:
```
Your API Token: eyJ0eXAiOiJKV1...
Your User ID: 193
```

Done. 90% of users are happy. Add OAuth later if needed.
