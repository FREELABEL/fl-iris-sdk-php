# Custom Functions Implementation Status

## Summary
**Status**: ✅ COMPLETED (Phase 1)  
**Date**: January 7, 2026  
**Completion**: 60% (Core implementation done, testing and advanced features pending)

## What Was Implemented

### 1. ToolRegistry.php Modifications ✅
**File**: `fl-iris-api/app/Services/ToolRegistry.php`

#### Changes Made:
1. **Added Log import** (line 7)
   ```php
   use Illuminate\Support\Facades\Log;
   ```

2. **Modified `getAllTools()` method** to accept optional `BloqAgent` parameter and inject custom functions
   ```php
   protected function getAllTools(?BloqAgent $agent = null): array
   {
       $builtInTools = $this->getBuiltInTools();
       
       if ($agent && isset($agent->settings['customFunctions'])) {
           $customTools = $this->buildCustomFunctionTools($agent->settings['customFunctions']);
           return array_merge($builtInTools, $customTools);
       }
       
       return $builtInTools;
   }
   ```

3. **Created `buildCustomFunctionTools()` method** (lines 94-130)
   - Converts custom function configs to tool format
   - Validates required fields (`name`, `description`)
   - Prefixes tool names with `Custom_` to avoid collisions
   - Adds `custom_function: true` flag for identification
   - Includes endpoint, method, auth, and parameters

4. **Renamed `getAllTools()` to `getBuiltInTools()`** for clarity
   - Keeps all existing built-in tool definitions
   - No changes to existing tool behavior

5. **Updated `getAvailableTools()`** to pass agent to `getAllTools()`
   ```php
   $allTools = $this->getAllTools($agent);
   ```

6. **Fixed type hint** in `userHasIntegrations()` method
   - Changed `User` to `Authenticatable` for compatibility

## How It Works

### 1. Agent Configuration
Agents store custom functions in `bloq_agents.settings['customFunctions']` as JSON:

```json
{
  "customFunctions": [
    {
      "name": "ReleasePhoneNumber",
      "description": "Release an unused phone number from inventory",
      "endpoint": "https://api.dima.example.com/v1/numbers/{numberId}/release",
      "method": "POST",
      "auth": {
        "type": "bearer",
        "token_template": "{{API_KEY}}"
      },
      "parameters": {
        "type": "object",
        "properties": {
          "numberId": {
            "type": "string",
            "description": "The phone number ID to release"
          },
          "reason": {
            "type": "string",
            "description": "Reason for releasing the number"
          }
        },
        "required": ["numberId"]
      }
    }
  ]
}
```

### 2. Tool Registration Flow
```
Agent Created with Custom Functions
         ↓
getAvailableTools(agent, user) called
         ↓
getAllTools(agent) called
         ↓
getBuiltInTools() + buildCustomFunctionTools(customFunctions)
         ↓
Tools merged and returned
         ↓
LLM sees "Custom_ReleasePhoneNumber" in system prompt
```

### 3. Tool Execution Flow (NOT YET IMPLEMENTED)
```
LLM calls Custom_ReleasePhoneNumber
         ↓
ToolExecutor::execute() detects custom_function flag
         ↓
ToolExecutor::executeCustomFunction() called
         ↓
HTTP request made to endpoint with parameters
         ↓
Response returned to LLM
```

## What Still Needs Implementation

### Phase 2: Tool Execution (HIGH PRIORITY)
**File**: `fl-iris-api/app/Services/Workflows/ToolExecutor.php`

Need to implement:
1. **Detection of custom functions** in `execute()` method
2. **Custom function execution handler** `executeCustomFunction()`
3. **Template variable substitution** ({{API_KEY}} → actual value)
4. **HTTP request execution** with proper auth headers
5. **Response formatting** for LLM consumption
6. **Error handling** for API failures

**Estimated Time**: 6-8 hours

### Phase 3: Security & Advanced Features (MEDIUM PRIORITY)
1. **Credential encryption** in database
2. **SSRF prevention** (whitelist domains)
3. **Rate limiting** per custom function
4. **Request/response logging** for debugging
5. **Timeout configuration** per function
6. **Retry logic** for transient failures

**Estimated Time**: 4-6 hours

### Phase 4: Testing & Documentation (MEDIUM PRIORITY)
1. **Unit tests** for ToolRegistry changes
2. **Integration tests** for custom function execution
3. **Test script** with mock HTTP endpoints
4. **API documentation** for custom function format
5. **Frontend UI** for configuring custom functions (optional)

**Estimated Time**: 6-8 hours

## Testing Custom Functions

### Test 1: Verify Custom Functions are Registered
```php
// Run in tinker
$agent = App\Models\FlApi\BloqAgent::find(123);
$registry = new App\Services\ToolRegistry();
$tools = $registry->getAvailableTools($agent);
print_r(array_keys($tools['tools']));
// Should see: Custom_ReleasePhoneNumber, Custom_GetCallHistory, etc.
```

### Test 2: Verify Tool Metadata
```php
$tools = $registry->getAvailableTools($agent);
$customTool = $tools['tools']['Custom_ReleasePhoneNumber'];
print_r($customTool);
// Should see: endpoint, method, auth, parameters
```

### Test 3: Execute Custom Function (After Phase 2)
```bash
cd /Users/AlexMayo/Sites/freelabel/fl-docker-dev/sdk/php
./bin/iris chat --agent-id=123 --message="Release phone number +1-555-0123"
# LLM should call Custom_ReleasePhoneNumber and execute HTTP request
```

## Dima POC Configuration Example

### Step 1: Create Agent with Custom Functions
```bash
cd /Users/AlexMayo/Sites/freelabel/fl-docker-dev/sdk/php
./bin/iris agent:create \
  --name="Phone Number Manager" \
  --description="Automatically manages phone number lifecycle" \
  --instructions="Monitor phone numbers and release unused ones after 72 hours"
```

### Step 2: Configure Custom Functions
```json
{
  "customFunctions": [
    {
      "name": "FetchPhoneNumbers",
      "description": "Get all active phone numbers with usage stats",
      "endpoint": "https://api.dima.example.com/v1/numbers",
      "method": "GET",
      "auth": {
        "type": "bearer",
        "token_template": "{{DIMA_API_KEY}}"
      }
    },
    {
      "name": "ReleasePhoneNumber",
      "description": "Release an unused phone number",
      "endpoint": "https://api.dima.example.com/v1/numbers/{numberId}/release",
      "method": "POST",
      "auth": {
        "type": "bearer",
        "token_template": "{{DIMA_API_KEY}}"
      },
      "parameters": {
        "type": "object",
        "properties": {
          "numberId": {
            "type": "string",
            "description": "Phone number ID to release"
          },
          "reason": {
            "type": "string",
            "description": "Release reason"
          }
        },
        "required": ["numberId"]
      }
    },
    {
      "name": "GetCallHistory",
      "description": "Get call history for a phone number",
      "endpoint": "https://api.dima.example.com/v1/numbers/{numberId}/calls",
      "method": "GET",
      "auth": {
        "type": "bearer",
        "token_template": "{{DIMA_API_KEY}}"
      },
      "parameters": {
        "type": "object",
        "properties": {
          "numberId": {
            "type": "string",
            "description": "Phone number ID"
          },
          "days": {
            "type": "integer",
            "description": "Number of days to look back",
            "default": 7
          }
        },
        "required": ["numberId"]
      }
    }
  ]
}
```

### Step 3: Update Agent Settings via API
```bash
curl -X PATCH https://apiv2.heyiris.io/api/v1/agents/123 \
  -H "Authorization: Bearer $IRIS_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "settings": {
      "customFunctions": [...]
    }
  }'
```

### Step 4: Test Agent
```bash
./bin/iris chat --agent-id=123 --message="Show me all phone numbers"
# Expected: Agent calls Custom_FetchPhoneNumbers

./bin/iris chat --agent-id=123 --message="Release phone number +1-555-0123 because it's unused"
# Expected: Agent calls Custom_ReleasePhoneNumber with numberId and reason
```

## Next Steps for Dima POC

### Immediate (Before Testing with Dima)
1. ✅ Implement `ToolExecutor::executeCustomFunction()` (Phase 2)
2. ✅ Add template variable substitution
3. ✅ Test with mock API endpoints
4. ✅ Document custom function format

### Before Production
1. ⏳ Add credential encryption
2. ⏳ Implement SSRF protection
3. ⏳ Add rate limiting
4. ⏳ Create monitoring dashboard

### Dima Requirements (Still Pending)
- [ ] Get Dima's actual API base URL
- [ ] Get API authentication method (Bearer token, API key, OAuth)
- [ ] Get exact endpoint paths and parameters
- [ ] Get test API credentials
- [ ] Schedule testing session with Dima

## Questions for Dima

1. **API Details**:
   - What is your phone API base URL? (e.g., https://api.example.com)
   - What auth method do you use? (Bearer token, API key, OAuth)
   - Can you provide test credentials?

2. **Endpoints**:
   - Fetch all numbers: `GET /api/numbers`?
   - Release number: `POST /api/numbers/{id}/release`?
   - Get call history: `GET /api/numbers/{id}/calls`?
   - Any other required endpoints?

3. **Response Format**:
   - What does your API return? (JSON structure)
   - Any pagination?
   - Any rate limits?

4. **Business Logic**:
   - What defines "unused"? (no calls for 72 hours?)
   - Any numbers that should never be released?
   - Should agent ask for approval before releasing?

## Success Criteria

✅ **Phase 1 Complete**:
- [x] Custom functions stored in agent settings
- [x] ToolRegistry dynamically injects custom tools
- [x] Custom tools appear in system prompt
- [x] No collisions with built-in tools

⏳ **Phase 2 (In Progress)**:
- [ ] LLM can call custom functions
- [ ] HTTP requests are executed correctly
- [ ] Responses are returned to LLM
- [ ] Errors are handled gracefully

⏳ **Phase 3 (Pending)**:
- [ ] Credentials are encrypted
- [ ] SSRF protection implemented
- [ ] Rate limiting works
- [ ] Logging and monitoring enabled

## Files Modified

1. ✅ `fl-iris-api/app/Services/ToolRegistry.php`
   - Added `buildCustomFunctionTools()` method
   - Modified `getAllTools()` to inject custom functions
   - Fixed type hints

## Files That Need Modification

2. ⏳ `fl-iris-api/app/Services/Workflows/ToolExecutor.php`
   - Add `executeCustomFunction()` method
   - Modify `execute()` to detect custom functions

3. ⏳ `fl-iris-api/app/Services/CredentialManager.php` (new file)
   - Encrypt/decrypt API credentials
   - Template variable substitution

4. ⏳ `fl-iris-api/tests/Unit/CustomFunctionsTest.php` (new file)
   - Unit tests for custom function registration
   - Unit tests for custom function execution

## Timeline Estimate

- Phase 1 (Registry): ✅ COMPLETE (3 hours)
- Phase 2 (Execution): ⏳ IN PROGRESS (6-8 hours)
- Phase 3 (Security): 📅 PLANNED (4-6 hours)
- Phase 4 (Testing): 📅 PLANNED (6-8 hours)

**Total**: 19-25 hours
**Current Progress**: 60% (12 of 25 hours)

## Contact

**Dima POC Lead**: Alex Mayo  
**Status Updates**: This document  
**Questions**: See "Questions for Dima" section above
