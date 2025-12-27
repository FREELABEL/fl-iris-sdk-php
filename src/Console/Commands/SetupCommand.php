<?php

namespace IRIS\SDK\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Setup Command
 * 
 * Interactive authentication setup for IRIS SDK.
 * Creates .env file with API credentials.
 */
class SetupCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('setup')
            ->setDescription('Interactive setup for IRIS SDK authentication')
            ->setHelp('This command will guide you through setting up authentication for the IRIS SDK.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');

        $output->writeln('');
        $output->writeln('╔════════════════════════════════════════════════════════════╗');
        $output->writeln('║         IRIS SDK - Interactive Setup                       ║');
        $output->writeln('╚════════════════════════════════════════════════════════════╝');
        $output->writeln('');

        // Check if .env already exists
        $envPath = getcwd() . '/.env';
        if (file_exists($envPath)) {
            $output->writeln('<comment>⚠️  An .env file already exists.</comment>');
            $overwrite = new Question('Do you want to overwrite it? (yes/no) [no]: ', 'no');
            $answer = strtolower($helper->ask($input, $output, $overwrite));
            
            if ($answer !== 'yes' && $answer !== 'y') {
                $output->writeln('<info>Setup cancelled. Your existing .env file was not modified.</info>');
                return Command::SUCCESS;
            }
            
            // Backup existing .env
            copy($envPath, $envPath . '.backup.' . date('Y-m-d_H-i-s'));
            $output->writeln('<info>✓ Backed up existing .env file</info>');
            $output->writeln('');
        }

        // Step 1: Choose API URL
        $output->writeln('<fg=cyan>Step 1: API Configuration</>');
        $output->writeln('─────────────────────────────────────────────────────────────');
        
        $apiChoice = new ChoiceQuestion(
            'Select API environment:',
            [
                '1' => 'Production (https://apiv2.heyiris.io)',
                '2' => 'Local Development (http://localhost:8000)',
                '3' => 'Custom URL'
            ],
            '1'
        );
        
        $apiSelection = $helper->ask($input, $output, $apiChoice);
        
        if (strpos($apiSelection, 'Custom') !== false) {
            $customUrl = new Question('Enter custom API URL: ');
            $customUrl->setValidator(function ($answer) {
                if (!filter_var($answer, FILTER_VALIDATE_URL)) {
                    throw new \RuntimeException('Please enter a valid URL.');
                }
                return $answer;
            });
            $apiUrl = $helper->ask($input, $output, $customUrl);
        } elseif (strpos($apiSelection, 'Local') !== false) {
            $apiUrl = 'http://localhost:8000';
        } else {
            $apiUrl = 'https://apiv2.heyiris.io';
        }
        
        $output->writeln("<info>✓ API URL: {$apiUrl}</info>");
        $output->writeln('');

        // Step 2: Authentication
        $output->writeln('<fg=cyan>Step 2: Authentication</>');
        $output->writeln('─────────────────────────────────────────────────────────────');
        
        $authChoice = new ChoiceQuestion(
            'How would you like to authenticate?',
            [
                '1' => 'I have an API token',
                '2' => 'Login with email and password'
            ],
            '2'
        );
        
        $authMethod = $helper->ask($input, $output, $authChoice);
        
        $apiKey = null;
        $userId = null;
        
        if (strpos($authMethod, 'API token') !== false) {
            // Manual API token entry
            $tokenQuestion = new Question('Enter your API token: ');
            $tokenQuestion->setHidden(true);
            $tokenQuestion->setHiddenFallback(false);
            $apiKey = $helper->ask($input, $output, $tokenQuestion);
            
            $userIdQuestion = new Question('Enter your user ID: ');
            $userId = $helper->ask($input, $output, $userIdQuestion);
            
        } else {
            // Email/password login
            $emailQuestion = new Question('Email: ');
            $emailQuestion->setValidator(function ($answer) {
                if (!filter_var($answer, FILTER_VALIDATE_EMAIL)) {
                    throw new \RuntimeException('Please enter a valid email address.');
                }
                return $answer;
            });
            $email = $helper->ask($input, $output, $emailQuestion);
            
            $passwordQuestion = new Question('Password: ');
            $passwordQuestion->setHidden(true);
            $passwordQuestion->setHiddenFallback(false);
            $password = $helper->ask($input, $output, $passwordQuestion);
            
            $output->writeln('');
            $output->writeln('<info>Authenticating...</info>');
            
            // Attempt login
            try {
                $client = new Client(['base_uri' => $apiUrl, 'timeout' => 30]);
                
                // First, try to login and get OAuth token
                $response = $client->post('/api/v1/auth/login', [
                    'json' => [
                        'email' => $email,
                        'password' => $password
                    ],
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json'
                    ]
                ]);
                
                $data = json_decode($response->getBody(), true);
                
                if (isset($data['access_token'])) {
                    // Got OAuth token, now create an API token for SDK use
                    $accessToken = $data['access_token'];
                    $userId = $data['user']['id'] ?? null;
                    
                    $output->writeln('<info>✓ Login successful</info>');
                    $output->writeln('<info>Creating API token for SDK use...</info>');
                    
                    // Create API token
                    $tokenResponse = $client->post('/api/v1/user/api-tokens', [
                        'json' => [
                            'name' => 'SDK Token - ' . date('Y-m-d H:i:s'),
                            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year'))
                        ],
                        'headers' => [
                            'Accept' => 'application/json',
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $accessToken
                        ]
                    ]);
                    
                    $tokenData = json_decode($tokenResponse->getBody(), true);
                    
                    if (isset($tokenData['token']) || isset($tokenData['data']['token'])) {
                        $apiKey = $tokenData['token'] ?? $tokenData['data']['token'];
                        $output->writeln('<info>✓ API token created successfully</info>');
                    } else {
                        // Fallback: use OAuth token
                        $apiKey = $accessToken;
                        $output->writeln('<comment>⚠️  Using OAuth token (API token creation unavailable)</comment>');
                    }
                    
                } else {
                    $output->writeln('<error>✗ Login failed: Invalid response format</error>');
                    return Command::FAILURE;
                }
                
            } catch (RequestException $e) {
                $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
                $errorBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'No response';
                
                $output->writeln("<error>✗ Authentication failed (HTTP {$statusCode})</error>");
                
                try {
                    $errorData = json_decode($errorBody, true);
                    $message = $errorData['message'] ?? $errorData['error'] ?? 'Unknown error';
                    $output->writeln("<error>  {$message}</error>");
                } catch (\Exception $jsonError) {
                    $output->writeln("<error>  {$errorBody}</error>");
                }
                
                return Command::FAILURE;
                
            } catch (\Exception $e) {
                $output->writeln('<error>✗ Authentication failed: ' . $e->getMessage() . '</error>');
                return Command::FAILURE;
            }
        }
        
        $output->writeln('');

        // Step 3: Optional Settings
        $output->writeln('<fg=cyan>Step 3: Optional Settings</>');
        $output->writeln('─────────────────────────────────────────────────────────────');
        
        $defaultModel = new Question('Default AI model [gpt-4o-mini]: ', 'gpt-4o-mini');
        $model = $helper->ask($input, $output, $defaultModel);
        
        $output->writeln('');

        // Create .env file
        $envContent = <<<ENV
# IRIS SDK Configuration
# Generated by: iris setup
# Date: {date}

# API Configuration
IRIS_API_URL={api_url}

# Authentication
IRIS_API_KEY={api_key}
IRIS_USER_ID={user_id}

# AI Model Settings
IRIS_DEFAULT_MODEL={model}

# Optional: Custom settings
# IRIS_TIMEOUT=30
# IRIS_MAX_RETRIES=3
ENV;

        $envContent = str_replace(
            ['{date}', '{api_url}', '{api_key}', '{user_id}', '{model}'],
            [date('Y-m-d H:i:s'), $apiUrl, $apiKey, $userId ?? '', $model],
            $envContent
        );

        file_put_contents($envPath, $envContent);
        
        $output->writeln('');
        $output->writeln('╔════════════════════════════════════════════════════════════╗');
        $output->writeln('║                  Setup Complete! ✨                        ║');
        $output->writeln('╚════════════════════════════════════════════════════════════╝');
        $output->writeln('');
        $output->writeln('<info>Configuration saved to .env</info>');
        $output->writeln('');
        $output->writeln('<fg=cyan>Next Steps:</>');
        $output->writeln('  • Test your setup:     <comment>./iris config:show</comment>');
        $output->writeln('  • Chat with an agent:  <comment>./iris chat</comment>');
        $output->writeln('  • List agents:         <comment>./iris agents</comment>');
        $output->writeln('  • Manage memory:       <comment>./iris memory:list</comment>');
        $output->writeln('');
        $output->writeln('<fg=yellow>Security Note:</> Keep your .env file private and do not commit it to version control.');
        $output->writeln('');

        return Command::SUCCESS;
    }
}
