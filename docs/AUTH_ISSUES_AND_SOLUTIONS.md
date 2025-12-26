# Authentication Issues & Solutions

## Current Authentication Problems

### Issue #1: Token Expiration
**Problem:** JWT tokens in `.env` file eventually expire, causing "Unauthenticated" errors.

**Symptoms:**
```bash
$ iris integrations list
[ERROR] Unauthenticated.
```

**Why it happens:**
- JWT tokens have expiration dates (typically 1-2 years)
- Tokens in `.env` file are static and don't auto-refresh
- Laravel Passport tokens expire and need regeneration

### Issue #2: Environment Confusion
**Problem:** SDK tries local environment but token is for production (or vice versa).

**Symptoms:**
```bash
$ iris integrations list
[ERROR] Request failed: cURL error 7: Failed to connect to local.raichu.freelabel.net
```

**Why it happens:**
- `IRIS_ENV=local` set but local API not running
- Using wrong API key for the environment
- Mismatched URLs and credentials

### Issue #3: No Automatic Token Refresh
**Problem:** SDK doesn't automatically refresh expired tokens.

**Why it happens:**
- Current implementation uses static Bearer tokens
- No refresh token flow implemented
- No OAuth client credentials fallback

## Solutions

### Solution 1: Token Generation Utility

Create a helper to generate fresh tokens on demand:

```php
<?php
// bin/auth-helper
use IRIS\SDK\Auth\TokenGenerator;

$generator = new TokenGenerator();

// Generate a new token
$token = $generator->generateUserToken(
    userId: 193,
    scopes: ['*'],
    expiresInDays: 365
);

echo "New Token:\n{$token}\n";

// Update .env file
$generator->updateEnvFile('.env', 'IRIS_LOCAL_API_KEY', $token);
echo "✓ Updated .env file\n";
```

### Solution 2: Auto-Refresh on 401

Implement automatic token refresh when authentication fails:

```php
// In Http/Client.php
protected function handleUnauthorized(string $endpoint): void
{
    // Try to refresh the token
    if ($this->auth->canRefreshToken()) {
        $this->auth->refreshToken();
        // Retry the request
        return;
    }
    
    // Fall back to client credentials if configured
    if ($this->auth->hasClientCredentials()) {
        $this->auth->useClientCredentials();
        return;
    }
    
    throw new AuthenticationException('Token expired and cannot be refreshed');
}
```

### Solution 3: Environment-Aware Configuration

Make the SDK smarter about environments:

```php
// In Config.php - enhanced environment detection
public function __construct(array $options)
{
    // Auto-detect environment based on available APIs
    if (!isset($options['environment'])) {
        $options['environment'] = $this->detectEnvironment();
    }
    
    // Load appropriate credentials
    $this->loadCredentialsForEnvironment($options['environment']);
}

private function detectEnvironment(): string
{
    // Try local first
    if ($this->canConnectTo('https://local.raichu.freelabel.net/api/health')) {
        return 'local';
    }
    
    // Fall back to production
    return 'production';
}
```

### Solution 4: Token Health Check

Add a command to check token validity:

```bash
$ iris auth check

✓ Token is valid
  User ID: 193
  Expires: 2025-12-26
  Days remaining: 365
  
✗ Token is expired
  Expired: 2024-12-26
  Run: iris auth refresh
```

## Recommended Implementation

### Step 1: Create TokenGenerator Class

```php
<?php

namespace IRIS\SDK\Auth;

use IRIS\SDK\Config;

class TokenGenerator
{
    protected Config $config;
    protected string $apiUrl;
    
    public function __construct(?Config $config = null)
    {
        $this->config = $config ?? new Config([
            'api_key' => 'temp', // Won't be used
        ]);
        
        // Use appropriate API based on environment
        $env = getenv('IRIS_ENV') ?: 'production';
        $this->apiUrl = $env === 'local' 
            ? 'https://local.raichu.freelabel.net'
            : 'https://apiv2.heyiris.io';
    }
    
    /**
     * Generate a new personal access token via API
     */
    public function generateUserToken(
        int $userId,
        string $email,
        string $password,
        array $scopes = ['*'],
        int $expiresInDays = 365
    ): string {
        // Step 1: Login to get session token
        $sessionToken = $this->login($email, $password);
        
        // Step 2: Create personal access token
        $token = $this->createPersonalAccessToken(
            $sessionToken,
            $userId,
            $scopes,
            $expiresInDays
        );
        
        return $token;
    }
    
    /**
     * Login and get session token
     */
    protected function login(string $email, string $password): string
    {
        $ch = curl_init($this->apiUrl . '/api/v1/auth/login');
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'email' => $email,
                'password' => $password,
            ]),
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new \RuntimeException('Login failed: Invalid credentials');
        }
        
        $data = json_decode($response, true);
        return $data['token'] ?? throw new \RuntimeException('No token in response');
    }
    
    /**
     * Create personal access token
     */
    protected function createPersonalAccessToken(
        string $sessionToken,
        int $userId,
        array $scopes,
        int $expiresInDays
    ): string {
        $ch = curl_init($this->apiUrl . '/api/v1/auth/tokens');
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $sessionToken,
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'name' => 'IRIS SDK Token - ' . date('Y-m-d'),
                'scopes' => $scopes,
                'expires_in_days' => $expiresInDays,
            ]),
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 201) {
            throw new \RuntimeException('Failed to create token');
        }
        
        $data = json_decode($response, true);
        return $data['token'] ?? throw new \RuntimeException('No token in response');
    }
    
    /**
     * Check if a token is valid
     */
    public function isTokenValid(string $token): array
    {
        $ch = curl_init($this->apiUrl . '/api/v1/auth/me');
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Authorization: Bearer ' . $token,
            ],
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return [
                'valid' => true,
                'user' => $data['user'] ?? null,
            ];
        }
        
        return [
            'valid' => false,
            'error' => 'Token is expired or invalid',
        ];
    }
    
    /**
     * Update .env file with new token
     */
    public function updateEnvFile(string $envPath, string $key, string $value): void
    {
        if (!file_exists($envPath)) {
            throw new \RuntimeException("File not found: {$envPath}");
        }
        
        $contents = file_get_contents($envPath);
        
        // Check if key exists
        if (preg_match("/^{$key}=.*/m", $contents)) {
            // Replace existing value
            $contents = preg_replace(
                "/^{$key}=.*/m",
                "{$key}={$value}",
                $contents
            );
        } else {
            // Append new key
            $contents .= "\n{$key}={$value}\n";
        }
        
        file_put_contents($envPath, $contents);
    }
}
```

### Step 2: Create CLI Auth Command

```php
<?php

namespace IRIS\SDK\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use IRIS\SDK\Auth\TokenGenerator;

class AuthCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('auth')
            ->setDescription('Manage authentication tokens')
            ->addArgument('action', InputArgument::OPTIONAL, 'Action: check, refresh, generate', 'check');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $action = $input->getArgument('action');
        
        $generator = new TokenGenerator();
        
        switch ($action) {
            case 'check':
                return $this->checkToken($io, $generator);
            case 'refresh':
            case 'generate':
                return $this->generateToken($io, $input, $output, $generator);
            default:
                $io->error("Unknown action: {$action}");
                return Command::FAILURE;
        }
    }
    
    private function checkToken(SymfonyStyle $io, TokenGenerator $generator): int
    {
        $io->title('🔐 Token Health Check');
        
        // Get current token from .env
        $env = parse_ini_file(__DIR__ . '/../../../.env');
        $envType = $env['IRIS_ENV'] ?? 'production';
        
        $tokenKey = $envType === 'local' ? 'IRIS_LOCAL_API_KEY' : 'IRIS_API_KEY';
        $token = $env[$tokenKey] ?? null;
        
        if (!$token) {
            $io->error("No token found in .env ({$tokenKey})");
            $io->text("Run: iris auth generate");
            return Command::FAILURE;
        }
        
        $io->text("Environment: {$envType}");
        $io->text("Token key: {$tokenKey}");
        $io->text("Token (first 20 chars): " . substr($token, 0, 20) . "...");
        $io->newLine();
        
        // Check if valid
        $result = $generator->isTokenValid($token);
        
        if ($result['valid']) {
            $io->success('✓ Token is valid');
            if (isset($result['user'])) {
                $io->definitionList(
                    ['User ID' => $result['user']['id'] ?? 'N/A'],
                    ['Email' => $result['user']['email'] ?? 'N/A'],
                    ['Name' => $result['user']['name'] ?? 'N/A']
                );
            }
            return Command::SUCCESS;
        } else {
            $io->error('✗ Token is expired or invalid');
            $io->text("Run: iris auth refresh");
            return Command::FAILURE;
        }
    }
    
    private function generateToken(
        SymfonyStyle $io,
        InputInterface $input,
        OutputInterface $output,
        TokenGenerator $generator
    ): int {
        $io->title('🔐 Generate New Token');
        
        $helper = $this->getHelper('question');
        
        // Get environment
        $envQuestion = new Question('Environment (local/production) [production]: ', 'production');
        $environment = $helper->ask($input, $output, $envQuestion);
        
        // Get credentials
        $emailQuestion = new Question('Email: ');
        $email = $helper->ask($input, $output, $emailQuestion);
        
        $passwordQuestion = new Question('Password: ');
        $passwordQuestion->setHidden(true);
        $password = $helper->ask($input, $output, $passwordQuestion);
        
        $userIdQuestion = new Question('User ID: ');
        $userId = (int)$helper->ask($input, $output, $userIdQuestion);
        
        $io->text('Generating token...');
        
        try {
            // Set environment for TokenGenerator
            putenv("IRIS_ENV={$environment}");
            
            $token = $generator->generateUserToken(
                userId: $userId,
                email: $email,
                password: $password,
                scopes: ['*'],
                expiresInDays: 365
            );
            
            // Update .env file
            $tokenKey = $environment === 'local' ? 'IRIS_LOCAL_API_KEY' : 'IRIS_API_KEY';
            $generator->updateEnvFile(__DIR__ . '/../../../.env', $tokenKey, $token);
            
            $io->success("✓ Token generated and saved to .env");
            $io->text("Key: {$tokenKey}");
            $io->text("Token (first 20 chars): " . substr($token, 0, 20) . "...");
            $io->newLine();
            $io->text("You can now use the SDK and CLI commands.");
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $io->error("Failed to generate token: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
```

### Step 3: Register Auth Command

```php
// In src/Console/Application.php
use IRIS\SDK\Console\Commands\AuthCommand;

public function __construct()
{
    parent::__construct('IRIS SDK', '1.0.0');

    $this->addCommands([
        new SDKCommand(),
        new ChatCommand(),
        new ConfigCommand(),
        new ToolsCommand(),
        new IntegrationsCommand(),
        new AuthCommand(),  // Add this
    ]);
}
```

## Usage

### Check Token Health
```bash
$ iris auth check

🔐 Token Health Check
 Environment: local
 Token key: IRIS_LOCAL_API_KEY
 Token (first 20 chars): eyJ0eXAiOiJKV1QiLCJh...

 ✓ Token is valid

 User ID:   193
 Email:     alex@freelabel.net
 Name:      Alex Mayo
```

### Generate Fresh Token
```bash
$ iris auth generate

🔐 Generate New Token
 Environment (local/production) [production]: local
 Email: alex@freelabel.net
 Password: **********
 User ID: 193
 Generating token...

 ✓ Token generated and saved to .env
 Key: IRIS_LOCAL_API_KEY
 Token (first 20 chars): eyJ0eXAiOiJKV1QiLCJh...

 You can now use the SDK and CLI commands.
```

### Quick Fix for "Unauthenticated" Errors
```bash
# 1. Check if token is expired
iris auth check

# 2. If expired, generate new one
iris auth generate

# 3. Verify it works
iris integrations list
```

## Best Practices

### 1. Environment-Specific Tokens
Keep separate tokens for each environment:
```bash
# .env
IRIS_LOCAL_API_KEY=eyJ0eXAi...  # For local development
IRIS_API_KEY=eyJ0eXAi...        # For production
```

### 2. Token Rotation
Regenerate tokens periodically:
```bash
# Every 90 days
iris auth generate
```

### 3. Secure Token Storage
Never commit tokens to git:
```bash
# .gitignore
.env
.env.local
*.env
```

### 4. Token Validation Before Operations
```php
// Check token before running long operations
$generator = new TokenGenerator();
$result = $generator->isTokenValid($config->apiKey);

if (!$result['valid']) {
    throw new \RuntimeException('Token expired. Run: iris auth generate');
}

// Proceed with operations
```

## Summary

**Problems:**
- ✗ Tokens expire and cause "Unauthenticated" errors
- ✗ No automatic token refresh
- ✗ Environment confusion (local vs production)
- ✗ Manual token management is tedious

**Solutions:**
- ✓ `iris auth check` - Check token validity
- ✓ `iris auth generate` - Generate fresh tokens
- ✓ TokenGenerator class for programmatic token management
- ✓ Environment-aware token selection
- ✓ Auto-update .env file with new tokens

**Next Steps:**
1. Implement TokenGenerator class
2. Add AuthCommand to CLI
3. Test token generation flow
4. Document in main README
5. Consider adding auto-refresh on 401 errors

This approach makes authentication much more developer-friendly and eliminates the "Unauthenticated" errors that plague SDK usage.
