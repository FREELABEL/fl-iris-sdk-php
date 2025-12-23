# Backend Implementation Guide

## Missing API Token Endpoint

The SDK is getting an error because the backend doesn't have an API token generation endpoint yet:

```
Table 'freelabelnet.api_tokens' doesn't exist
```

## Quick Fix - Add API Token Endpoint

### 1. Create Migration

```bash
php artisan make:migration create_api_tokens_table
```

```php
// database/migrations/xxxx_create_api_tokens_table.php
public function up()
{
    Schema::create('api_tokens', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('name'); // e.g., "SDK Access"
        $table->string('token', 80)->unique();
        $table->text('abilities')->nullable(); // JSON array of scopes
        $table->timestamp('last_used_at')->nullable();
        $table->timestamp('expires_at')->nullable();
        $table->timestamps();
        
        $table->index(['user_id', 'token']);
    });
}

public function down()
{
    Schema::dropIfExists('api_tokens');
}
```

```bash
php artisan migrate
```

### 2. Create API Controller

```php
// app/Http/Controllers/Api/DeveloperController.php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DeveloperController extends Controller
{
    /**
     * Generate a new API token for the authenticated user.
     */
    public function generateToken(Request $request)
    {
        $user = $request->user();
        
        // Revoke old SDK tokens (optional - keeps it clean)
        $user->apiTokens()->where('name', 'SDK Access')->delete();
        
        // Generate new token
        $tokenString = 'sk_' . Str::random(64);
        
        $token = $user->apiTokens()->create([
            'name' => 'SDK Access',
            'token' => hash('sha256', $tokenString),
            'abilities' => ['*'], // Full access
            'expires_at' => now()->addYear(),
        ]);
        
        return response()->json([
            'success' => true,
            'token' => $tokenString, // Show only once!
            'user_id' => $user->id,
            'expires_at' => $token->expires_at,
            'instructions' => [
                'Run in terminal: ./bin/iris config setup',
                'Paste this token when prompted',
                'Save this token - it will not be shown again!',
            ],
        ]);
    }
    
    /**
     * List user's API tokens (masked).
     */
    public function listTokens(Request $request)
    {
        $tokens = $request->user()->apiTokens()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($token) {
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'token' => 'sk_' . str_repeat('*', 60) . substr($token->token, -4),
                    'last_used' => $token->last_used_at?->diffForHumans(),
                    'expires_at' => $token->expires_at?->format('Y-m-d'),
                    'created_at' => $token->created_at->format('Y-m-d'),
                ];
            });
        
        return response()->json($tokens);
    }
    
    /**
     * Revoke an API token.
     */
    public function revokeToken(Request $request, $tokenId)
    {
        $token = $request->user()->apiTokens()->findOrFail($tokenId);
        $token->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Token revoked successfully',
        ]);
    }
}
```

### 3. Add Routes

```php
// routes/api.php
Route::middleware('auth:api')->group(function () {
    Route::prefix('developer')->group(function () {
        Route::post('/token', [DeveloperController::class, 'generateToken']);
        Route::get('/tokens', [DeveloperController::class, 'listTokens']);
        Route::delete('/tokens/{id}', [DeveloperController::class, 'revokeToken']);
    });
});
```

### 4. Add User Relationship

```php
// app/Models/User.php
public function apiTokens()
{
    return $this->hasMany(ApiToken::class);
}
```

### 5. Create ApiToken Model

```php
// app/Models/ApiToken.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiToken extends Model
{
    protected $fillable = [
        'name',
        'token',
        'abilities',
        'last_used_at',
        'expires_at',
    ];
    
    protected $casts = [
        'abilities' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
    
    protected $hidden = [
        'token', // Never expose the actual token hash
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

### 6. Create Developer Page UI

```vue
<!-- resources/js/Pages/Developer.vue -->
<template>
  <div class="max-w-4xl mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6">Developer Settings</h1>
    
    <!-- API Token Section -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
      <h2 class="text-xl font-semibold mb-4">API Token</h2>
      
      <div v-if="!showToken">
        <p class="text-gray-600 mb-4">
          Generate an API token to use with the IRIS SDK and API.
        </p>
        <button 
          @click="generateToken"
          class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
        >
          Generate New Token
        </button>
      </div>
      
      <div v-else class="space-y-4">
        <div class="bg-yellow-50 border border-yellow-200 rounded p-4">
          <p class="text-yellow-800 font-semibold mb-2">
            ⚠️ Save this token now - it won't be shown again!
          </p>
          <div class="bg-white p-3 rounded border border-gray-300 font-mono text-sm break-all">
            {{ newToken }}
          </div>
          <button 
            @click="copyToken"
            class="mt-2 px-3 py-1 bg-gray-800 text-white rounded text-sm"
          >
            📋 Copy Token
          </button>
        </div>
        
        <div class="bg-blue-50 border border-blue-200 rounded p-4">
          <p class="font-semibold text-blue-900 mb-2">Your User ID:</p>
          <div class="bg-white p-2 rounded border border-gray-300 font-mono">
            {{ userId }}
          </div>
        </div>
        
        <div class="bg-gray-50 border rounded p-4">
          <p class="font-semibold mb-2">Quick Start:</p>
          <ol class="list-decimal list-inside space-y-1 text-sm text-gray-700">
            <li>Install SDK: <code class="bg-gray-200 px-1 rounded">composer require iris-ai/sdk</code></li>
            <li>Run setup: <code class="bg-gray-200 px-1 rounded">./bin/iris config setup</code></li>
            <li>Paste your token and user ID when prompted</li>
            <li>Start building! Check the docs at <a href="https://docs.heyiris.io" class="text-blue-600">docs.heyiris.io</a></li>
          </ol>
        </div>
      </div>
    </div>
    
    <!-- Existing Tokens -->
    <div class="bg-white rounded-lg shadow p-6">
      <h2 class="text-xl font-semibold mb-4">Active Tokens</h2>
      <div v-if="tokens.length === 0" class="text-gray-500">
        No active tokens
      </div>
      <div v-else class="space-y-2">
        <div 
          v-for="token in tokens" 
          :key="token.id"
          class="flex items-center justify-between p-3 bg-gray-50 rounded"
        >
          <div>
            <div class="font-mono text-sm">{{ token.token }}</div>
            <div class="text-xs text-gray-500">
              Created {{ token.created_at }} • Last used {{ token.last_used || 'Never' }}
            </div>
          </div>
          <button 
            @click="revokeToken(token.id)"
            class="text-red-600 hover:text-red-800 text-sm"
          >
            Revoke
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const showToken = ref(false);
const newToken = ref('');
const userId = ref(null);
const tokens = ref([]);

const generateToken = async () => {
  try {
    const response = await axios.post('/api/developer/token');
    newToken.value = response.data.token;
    userId.value = response.data.user_id;
    showToken.value = true;
    loadTokens();
  } catch (error) {
    alert('Error generating token');
  }
};

const copyToken = () => {
  navigator.clipboard.writeText(newToken.value);
  alert('Token copied to clipboard!');
};

const loadTokens = async () => {
  const response = await axios.get('/api/developer/tokens');
  tokens.value = response.data;
};

const revokeToken = async (tokenId) => {
  if (!confirm('Are you sure you want to revoke this token?')) return;
  await axios.delete(`/api/developer/tokens/${tokenId}`);
  loadTokens();
};

onMounted(() => {
  loadTokens();
});
</script>
```

### 7. Add Route in Web

```php
// routes/web.php
Route::middleware('auth')->get('/developer', function () {
    return Inertia::render('Developer');
})->name('developer');
```

### 8. Add Navigation Link

```vue
<!-- Add to your navigation component -->
<NavLink href="/developer" :active="route().current('developer')">
  Developer
</NavLink>
```

## Alternative: Use Laravel Sanctum (Recommended)

If you're already using Laravel Sanctum:

```php
// Just use Sanctum's built-in token generation
Route::post('/developer/token', function (Request $request) {
    $token = $request->user()->createToken('SDK Access');
    
    return response()->json([
        'token' => $token->plainTextToken,
        'user_id' => $request->user()->id,
    ]);
});
```

That's it! The UI will now have a Developer page where users can generate their API tokens for the SDK.
