<?php

declare(strict_types=1);

namespace IRIS\SDK\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\Question;
use IRIS\SDK\Auth\CredentialStore;

/**
 * CLI command for managing SDK credentials.
 *
 * Usage:
 *   iris config                      # Show current configuration
 *   iris config set api_key <value>  # Set a credential
 *   iris config get api_key          # Get a credential
 *   iris config list                 # List all credentials (masked)
 *   iris config clear                # Clear all credentials
 *   iris config setup                # Interactive setup wizard
 */
class ConfigCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('config')
            ->setDescription('Manage SDK credentials and configuration')
            ->setHelp(<<<HELP
Manage IRIS SDK credentials stored in ~/.iris/credentials.json

<info>Commands:</info>
  iris config                      Show current configuration status
  iris config set <key> <value>    Set a credential value
  iris config get <key>            Get a credential value
  iris config list                 List all credentials (masked)
  iris config clear                Clear all stored credentials
  iris config setup                Interactive setup wizard

<info>Available credential keys:</info>
  api_key        User API token (Bearer token)
  user_id        User ID for API context
  client_id      OAuth2 Client ID
  client_secret  OAuth2 Client Secret
  base_url       Main API URL
  iris_url       IRIS API URL

<info>Examples:</info>
  iris config setup
  iris config set api_key eyJ0eXAiOiJKV1...
  iris config set user_id 193
  iris config set client_id a0a9899f-86cd-4408-9e0b-2abb0839e5c8

<info>Environment Variables (override stored credentials):</info>
  IRIS_API_KEY, IRIS_USER_ID, IRIS_CLIENT_ID, IRIS_CLIENT_SECRET
  IRIS_URL, IRIS_BASE_URL
HELP
            )
            ->addArgument('action', InputArgument::OPTIONAL, 'Action: set, get, list, clear, setup', 'status')
            ->addArgument('key', InputArgument::OPTIONAL, 'Credential key')
            ->addArgument('value', InputArgument::OPTIONAL, 'Credential value (for set action)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $store = new CredentialStore();

        $action = $input->getArgument('action');
        $key = $input->getArgument('key');
        $value = $input->getArgument('value');

        switch ($action) {
            case 'set':
                return $this->handleSet($io, $store, $key, $value);

            case 'get':
                return $this->handleGet($io, $store, $key);

            case 'list':
                return $this->handleList($io, $store);

            case 'clear':
                return $this->handleClear($io, $store, $input);

            case 'setup':
                return $this->handleSetup($io, $store, $input);

            case 'status':
            default:
                return $this->handleStatus($io, $store);
        }
    }

    /**
     * Show configuration status.
     */
    private function handleStatus(SymfonyStyle $io, CredentialStore $store): int
    {
        $io->title('IRIS SDK Configuration Status');

        // Show file location
        $io->text([
            '<fg=gray>Credentials file:</> ' . $store->getFilePath(),
            '',
        ]);

        // Check credential status
        $checks = [
            ['API Key', $store->has('api_key'), 'Required - Your user API token'],
            ['User ID', $store->has('user_id'), 'Required - Your numeric user ID'],
            ['Client ID', $store->has('client_id'), 'Optional - Rarely needed'],
            ['Client Secret', $store->has('client_secret'), 'Optional - Rarely needed'],
            ['IRIS URL', $store->has('iris_url'), 'Optional (has default)'],
            ['Base URL', $store->has('base_url'), 'Optional (has default)'],
        ];

        $tableRows = [];
        foreach ($checks as [$name, $configured, $description]) {
            $status = $configured ? '<fg=green>✓ Configured</>' : '<fg=yellow>✗ Not set</>';
            $tableRows[] = [$name, $status, $description];
        }

        $io->table(['Credential', 'Status', 'Description'], $tableRows);

        // Overall status
        if ($store->hasMinimumCredentials()) {
            $io->success('✅ SDK is ready to use!');

            if ($store->hasOAuthCredentials()) {
                $io->text('   <fg=gray>OAuth credentials configured (advanced scenarios)</>');
            } else {
                $io->text('   <fg=gray>💡 OAuth not configured (not needed for most operations)</>');
            }
        } else {
            $io->error('SDK is not configured. Run "iris config setup" to get started.');
        }

        $io->text([
            '',
            '<fg=gray>Tip: Use "iris config list" to see stored values (masked)</>',
            '<fg=gray>Tip: Use "iris config setup" for interactive configuration</>',
        ]);

        return Command::SUCCESS;
    }

    /**
     * Set a credential value.
     */
    private function handleSet(SymfonyStyle $io, CredentialStore $store, ?string $key, ?string $value): int
    {
        if (!$key) {
            $io->error('Key is required. Usage: iris config set <key> <value>');
            $io->text('Available keys: ' . implode(', ', array_keys(CredentialStore::CREDENTIAL_KEYS)));
            return Command::FAILURE;
        }

        if (!isset(CredentialStore::CREDENTIAL_KEYS[$key])) {
            $io->error("Unknown credential key: {$key}");
            $io->text('Available keys: ' . implode(', ', array_keys(CredentialStore::CREDENTIAL_KEYS)));
            return Command::FAILURE;
        }

        if (!$value) {
            $io->error('Value is required. Usage: iris config set <key> <value>');
            return Command::FAILURE;
        }

        $store->set($key, $value)->save();
        $io->success("Saved {$key} to credentials store.");

        return Command::SUCCESS;
    }

    /**
     * Get a credential value.
     */
    private function handleGet(SymfonyStyle $io, CredentialStore $store, ?string $key): int
    {
        if (!$key) {
            $io->error('Key is required. Usage: iris config get <key>');
            return Command::FAILURE;
        }

        $value = $store->get($key);

        if ($value === null) {
            $io->warning("{$key} is not set.");
            return Command::FAILURE;
        }

        // Mask sensitive values in output
        if (in_array($key, ['api_key', 'client_secret', 'webhook_secret']) && strlen($value) > 8) {
            $masked = substr($value, 0, 4) . str_repeat('*', 8) . substr($value, -4);
            $io->text("{$key}: {$masked}");
            $io->text('<fg=gray>(Full value hidden for security)</>');
        } else {
            $io->text("{$key}: {$value}");
        }

        return Command::SUCCESS;
    }

    /**
     * List all credentials (masked).
     */
    private function handleList(SymfonyStyle $io, CredentialStore $store): int
    {
        $io->title('Stored Credentials');

        $masked = $store->getMaskedCredentials();

        if (empty($masked)) {
            $io->warning('No credentials stored. Run "iris config setup" to configure.');
            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($masked as $key => $value) {
            $description = CredentialStore::CREDENTIAL_KEYS[$key] ?? 'Custom setting';
            $rows[] = [$key, $value, $description];
        }

        $io->table(['Key', 'Value (masked)', 'Description'], $rows);

        $io->text([
            '',
            '<fg=gray>File: ' . $store->getFilePath() . '</>',
        ]);

        return Command::SUCCESS;
    }

    /**
     * Clear all credentials.
     */
    private function handleClear(SymfonyStyle $io, CredentialStore $store, InputInterface $input): int
    {
        if (!$io->confirm('This will delete all stored credentials. Are you sure?', false)) {
            $io->text('Aborted.');
            return Command::SUCCESS;
        }

        $store->clear();
        $io->success('All credentials cleared.');

        return Command::SUCCESS;
    }

    /**
     * Interactive setup wizard.
     */
    private function handleSetup(SymfonyStyle $io, CredentialStore $store, InputInterface $input): int
    {
        $io->title('IRIS SDK Setup Wizard');

        $io->text([
            'This wizard will help you configure the IRIS SDK.',
            'Credentials will be saved to: ' . $store->getFilePath(),
            '',
            '<fg=yellow>Note: You can skip optional fields by pressing Enter.</>',
            '',
        ]);

        // API Key (required)
        $io->section('Step 1: Get Your API Token');
        $io->text([
            '<fg=cyan>Don\'t have an account yet?</>',
            '  1. Sign up at: <fg=green>https://heyiris.io/</>',
            '  2. After signup, click <fg=yellow>"Developer"</> in the navigation',
            '  3. Generate your API token',
            '',
            '<fg=cyan>Already have an account?</>',
            '  • Go to: <fg=green>https://app.heyiris.io/developer</> (or click "Developer" in nav)',
            '  • Copy your API token',
            '',
        ]);

        $currentApiKey = $store->get('api_key');
        $apiKeyPrompt = $currentApiKey
            ? "API Key [current: " . substr($currentApiKey, 0, 4) . "****" . substr($currentApiKey, -4) . "]"
            : "API Key";

        $apiKey = $io->ask($apiKeyPrompt, $currentApiKey, function ($value) use ($currentApiKey) {
            if (empty($value) && empty($currentApiKey)) {
                throw new \RuntimeException('API key is required');
            }
            return $value ?: $currentApiKey;
        });

        if ($apiKey) {
            $store->set('api_key', $apiKey);
        }

        // User ID (required)
        $io->section('Step 2: User ID (Required)');
        $io->text([
            'Your numeric user ID can be found:',
            '  • In the Developer section at <fg=green>https://app.heyiris.io/developer</>',
            '  • Or in your account settings',
            '',
        ]);

        $currentUserId = $store->get('user_id');
        $userIdPrompt = $currentUserId
            ? "User ID [current: {$currentUserId}]"
            : "User ID";

        $userId = $io->ask($userIdPrompt, $currentUserId, function ($value) use ($currentUserId) {
            if (empty($value) && empty($currentUserId)) {
                throw new \RuntimeException('User ID is required');
            }
            $finalValue = $value ?: $currentUserId;
            if (!is_numeric($finalValue)) {
                throw new \RuntimeException('User ID must be a number');
            }
            return $finalValue;
        });

        if ($userId) {
            $store->set('user_id', $userId);
        }

        // OAuth Credentials (optional - rarely needed!)
        $io->section('Step 3: OAuth Credentials (Optional - Advanced)');
        $io->text([
            '<fg=yellow>⚠️  Most users can skip this!</>',
            '',
            'OAuth credentials are only needed for:',
            '  • Advanced machine-to-machine scenarios',
            '  • Specific enterprise integrations',
            '',
            'Regular operations work fine with just your API key!',
            'You can always add these later if needed.',
            '',
        ]);

        if ($io->confirm('Configure OAuth credentials? (most users: No)', false)) {
            $currentClientId = $store->get('client_id');
            $clientIdPrompt = $currentClientId
                ? "Client ID [current: " . substr($currentClientId, 0, 8) . "...]"
                : "Client ID";

            $clientId = $io->ask($clientIdPrompt, $currentClientId);
            if ($clientId) {
                $store->set('client_id', $clientId);
            }

            $currentClientSecret = $store->get('client_secret');
            $clientSecretPrompt = $currentClientSecret
                ? "Client Secret [current: ****]"
                : "Client Secret";

            $clientSecret = $io->askHidden($clientSecretPrompt) ?: $currentClientSecret;
            if ($clientSecret) {
                $store->set('client_secret', $clientSecret);
            }
        }

        // Custom URLs (optional)
        $io->section('Step 4: API URLs (Optional)');
        $io->text('Leave blank to use default URLs.');

        if ($io->confirm('Configure custom API URLs?', false)) {
            $currentIrisUrl = $store->get('iris_url');
            $irisUrl = $io->ask(
                "IRIS API URL [default: https://iris.freelabel.net]",
                $currentIrisUrl
            );
            if ($irisUrl) {
                $store->set('iris_url', $irisUrl);
            }

            $currentBaseUrl = $store->get('base_url');
            $baseUrl = $io->ask(
                "Main API URL [default: https://apiv2.heyiris.io]",
                $currentBaseUrl
            );
            if ($baseUrl) {
                $store->set('base_url', $baseUrl);
            }
        }

        // Save
        $store->save();

        // Summary
        $io->success('Configuration saved!');
        $io->newLine();

        $this->handleList($io, $store);

        $io->text([
            '',
            '<fg=green>You can now use the CLI without passing credentials:</>',
            '  <fg=cyan>./bin/iris chat 11 "Hello!"</>',
            '  <fg=cyan>./bin/iris sdk:call agents list</>',
            '',
            '<fg=gray>To update credentials later, run: iris config setup</>',
        ]);

        return Command::SUCCESS;
    }
}
