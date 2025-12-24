<?php

namespace IRIS\SDK\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Style\SymfonyStyle;
use IRIS\SDK\IRIS;
use IRIS\SDK\Auth\CredentialStore;

class SDKCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('sdk:call')
            ->setDescription('Dynamic proxy to SDK resources and methods')
            ->setHelp('Usage: iris call <resource>.<method> [args] [--options]')
            ->addArgument('endpoint', InputArgument::REQUIRED, 'Resource.method (e.g., leads.list, agents.chat)')
            ->addArgument('params', InputArgument::IS_ARRAY, 'Parameters as key=value pairs')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output as JSON')
            ->addOption('raw', null, InputOption::VALUE_NONE, 'Raw output (no formatting)')
            ->addOption('api-key', null, InputOption::VALUE_REQUIRED, 'API key')
            ->addOption('user-id', null, InputOption::VALUE_REQUIRED, 'User ID');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $endpoint = $input->getArgument('endpoint');
        
        // Load credentials from store first, then override with CLI options/env vars
        $store = new CredentialStore();

        // Try to load from .env first, then check other sources
        // Priority: .env > CLI options > env vars > stored credentials
        $apiKey = $input->getOption('api-key')
            ?: getenv('IRIS_API_KEY')
            ?: $store->get('api_key');

        $userId = $input->getOption('user-id')
            ?: getenv('IRIS_USER_ID')
            ?: $store->get('user_id');
        
        // If still no credentials, try to initialize SDK to let Config load from .env
        if (!$apiKey || !$userId) {
            try {
                // Attempt to load from .env via Config
                $tempConfig = new \IRIS\SDK\Config([]);
                if (!$apiKey && isset($tempConfig->apiKey)) {
                    $apiKey = $tempConfig->apiKey;
                }
                if (!$userId && isset($tempConfig->userId)) {
                    $userId = $tempConfig->userId;
                }
            } catch (\Exception $e) {
                // Config will throw if api_key not found, that's ok
            }
        }
        
        if (!$apiKey || !$userId) {
            $io->error('Missing API credentials. Run "iris config setup" to configure credentials.');
            return Command::FAILURE;
        }
        
        try {
            // Build config array with all available credentials
            $config = [
                'api_key' => $apiKey,
                'user_id' => (int)$userId,
            ];

            // Add optional credentials from store
            if ($store->has('client_id')) {
                $config['client_id'] = $store->get('client_id');
            }
            if ($store->has('client_secret')) {
                $config['client_secret'] = $store->get('client_secret');
            }
            if ($store->has('iris_url')) {
                $config['iris_url'] = $store->get('iris_url');
            }
            if ($store->has('base_url')) {
                $config['base_url'] = $store->get('base_url');
            }

            $iris = new IRIS($config);
            
            // Parse endpoint (resource.method or resource.subresource.method)
            $parts = explode('.', $endpoint);
            if (count($parts) < 2) {
                throw new \InvalidArgumentException("Invalid endpoint format. Use: resource.method (e.g., leads.list)");
            }
            
            // Parse parameters
            $params = $this->parseParams($input->getArgument('params'));
            
            // Execute dynamic call
            $result = $this->executeDynamicCall($iris, $parts, $params);
            
            // Format output
            $this->renderOutput($result, $input, $output, $io);
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            if ($output->isVerbose()) {
                $io->text($e->getTraceAsString());
            }
            return Command::FAILURE;
        }
    }
    
    private function executeDynamicCall(IRIS $iris, array $parts, array $params)
    {
        $resource = array_shift($parts);
        $method = array_pop($parts);
        
        // Navigate to resource (handle nested resources like leads.aggregation or leads.deliverables)
        $target = $iris->{$resource};
        foreach ($parts as $sub) {
            if (method_exists($target, $sub)) {
                // Use reflection to check if this sub-resource method requires parameters
                $reflection = new \ReflectionMethod($target, $sub);
                $requiredParams = 0;
                foreach ($reflection->getParameters() as $param) {
                    if (!$param->isOptional()) {
                        $requiredParams++;
                    }
                }
                
                // Only extract positional params if the method requires them
                if ($requiredParams > 0) {
                    // Extract positional params (numeric keys) for sub-resource
                    $positionalParams = [];
                    foreach ($params as $key => $value) {
                        if (is_int($key) && count($positionalParams) < $requiredParams) {
                            $positionalParams[] = $value;
                            unset($params[$key]);
                        }
                    }
                    
                    if (!empty($positionalParams)) {
                        // Call sub-resource method with positional params
                        $target = $target->{$sub}(...$positionalParams);
                    } else {
                        // Call without params
                        $target = $target->{$sub}();
                    }
                } else {
                    // Method doesn't require params, call it directly
                    $target = $target->{$sub}();
                }
            } else {
                $target = $target->{$sub};
            }
        }
        
        // Call method with params
        if (!method_exists($target, $method)) {
            throw new \BadMethodCallException("Method '{$method}' not found on resource");
        }
        
        // Special handling for agents.create - use createFromArray for CLI
        if ($method === 'create' && get_class($target) === 'IRIS\SDK\Resources\Agents\AgentsResource') {
            if (!empty($params)) {
                // Extract positional and named params
                $positionalParams = [];
                $namedParams = [];
                
                foreach ($params as $key => $value) {
                    if (is_int($key)) {
                        $positionalParams[] = $value;
                    } else {
                        $namedParams[$key] = $value;
                    }
                }
                
                // If we have named params, use createFromArray
                if (!empty($namedParams)) {
                    return $target->createFromArray($namedParams);
                }
            }
        }
        
        // For methods that expect a single array argument, pass params as-is
        // Otherwise spread the params as individual arguments
        if (empty($params)) {
            return $target->{$method}();
        }
        
        // Separate positional and named parameters
        $positionalParams = [];
        $namedParams = [];
        
        foreach ($params as $key => $value) {
            if (is_int($key)) {
                $positionalParams[] = $value;
            } else {
                $namedParams[$key] = $value;
            }
        }
        
        // Use reflection to intelligently map parameters to method signature
        if (!empty($namedParams)) {
            try {
                $reflection = new \ReflectionMethod($target, $method);
                $methodParams = $reflection->getParameters();
                $args = $positionalParams;
                
                // Start from the position after positional params
                $startIndex = count($positionalParams);
                
                for ($i = $startIndex; $i < count($methodParams); $i++) {
                    $param = $methodParams[$i];
                    $paramName = $param->getName();
                    
                    if (isset($namedParams[$paramName])) {
                        // Found matching named parameter
                        $args[] = $namedParams[$paramName];
                        unset($namedParams[$paramName]);
                    } elseif ($param->isOptional()) {
                        // Skip optional params not provided
                        break;
                    } else {
                        // Required param not provided, pass remaining as array
                        if (!empty($namedParams)) {
                            $args[] = $namedParams;
                            $namedParams = [];
                        }
                        break;
                    }
                }
                
                // If there are leftover named params, append as array
                if (!empty($namedParams)) {
                    $args[] = $namedParams;
                }
                
                return $target->{$method}(...$args);
            } catch (\ReflectionException $e) {
                // Fallback to old behavior if reflection fails
                if (!empty($positionalParams)) {
                    $args = array_merge($positionalParams, [$namedParams]);
                    return $target->{$method}(...$args);
                } else {
                    return $target->{$method}($namedParams);
                }
            }
        } else {
            // Only positional parameters - spread as individual arguments
            return $target->{$method}(...$positionalParams);
        }
    }
    
    private function parseParams(array $params): array
    {
        $parsed = [];
        foreach ($params as $param) {
            if (strpos($param, '=') !== false) {
                [$key, $value] = explode('=', $param, 2);
                // Auto-detect type
                $parsed[$key] = $this->castValue($value);
            } else {
                $parsed[] = $this->castValue($param);
            }
        }
        return $parsed;
    }
    
    private function castValue(string $value)
    {
        if ($value === 'true') return true;
        if ($value === 'false') return false;
        if ($value === 'null') return null;
        if (is_numeric($value)) return $value + 0; // Cast to int or float
        if ($value[0] === '{' || $value[0] === '[') {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) return $decoded;
        }
        return $value;
    }
    
    private function renderOutput($result, InputInterface $input, OutputInterface $output, SymfonyStyle $io): void
    {
        // Raw output
        if ($input->getOption('raw')) {
            if (is_string($result)) {
                $output->writeln($result);
            } else {
                $output->writeln(print_r($result, true));
            }
            return;
        }
        
        // JSON output
        if ($input->getOption('json')) {
            $output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return;
        }
        
        // Smart formatting based on result type
        if (is_array($result)) {
            $this->renderArray($result, $output, $io);
        } elseif (is_object($result)) {
            $this->renderObject($result, $output, $io);
        } else {
            $io->success((string)$result);
        }
    }
    
    private function renderArray(array $data, OutputInterface $output, SymfonyStyle $io): void
    {
        // Check if it's a list of items (numeric keys) or single item (assoc keys)
        if (isset($data[0]) && is_array($data[0])) {
            // List of items - use compact format for large datasets
            $columnCount = count($data[0]);
            
            if ($columnCount > 10) {
                // Compact list format for wide tables
                $this->renderCompactList($data, $output, $io);
            } else {
                // Regular table for narrow datasets
                $table = new Table($output);
                $table->setHeaders(array_keys($data[0]));
                foreach ($data as $row) {
                    $table->addRow(array_map(fn($v) => $this->formatValue($v), array_values($row)));
                }
                $table->render();
            }
        } elseif ($this->isAssoc($data)) {
            // Single item - render as key-value
            $table = new Table($output);
            $table->setHeaders(['Key', 'Value']);
            foreach ($data as $key => $value) {
                $table->addRow([$key, $this->formatValue($value)]);
            }
            $table->render();
        } else {
            // Simple list
            $io->listing($data);
        }
    }
    
    private function renderCompactList(array $data, OutputInterface $output, SymfonyStyle $io): void
    {
        // Determine key fields to show based on available data
        $firstItem = $data[0];
        $keyFields = $this->selectKeyFields($firstItem);
        
        $output->writeln('');
        $output->writeln('<fg=cyan>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</>');
        
        foreach ($data as $index => $item) {
            $header = $this->formatCompactItem($item, $keyFields);
            $output->writeln($header);
            
            // Show selected fields with colors and icons
            foreach ($keyFields as $field) {
                if (isset($item[$field]) && $item[$field] !== null && $item[$field] !== '') {
                    $icon = $this->getFieldIcon($field);
                    $color = $this->getFieldColor($field);
                    $value = $this->formatColoredValue($item[$field], $field, 100);
                    $output->writeln(sprintf('  %s <fg=%s>%s:</> %s', $icon, $color, $field, $value));
                }
            }
            
            if ($index < count($data) - 1) {
                $output->writeln('<fg=cyan>  ┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄</>');
            }
        }
        
        $output->writeln('<fg=cyan>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</>');
        $output->writeln(sprintf('<fg=green>✓ Total: %d items</>', count($data)));
        $output->writeln('<fg=yellow>💡 Tip: Use --json flag for full data</>');
        $output->writeln('');
    }
    
    private function getFieldIcon(string $field): string
    {
        return match($field) {
            'id' => '🔑',
            'title', 'name', 'nickname' => '👤',
            'email' => '📧',
            'phone' => '📱',
            'company' => '🏢',
            'status' => '📊',
            'type', 'lead_type' => '🏷️',
            'url', 'external_url' => '🔗',
            'note_count' => '📝',
            'tasks_count' => '✅',
            'created_at' => '🕐',
            'updated_at' => '🔄',
            'contact_info' => '📇',
            default => '•'
        };
    }
    
    private function getFieldColor(string $field): string
    {
        return match($field) {
            'id' => 'cyan',
            'title', 'name', 'nickname' => 'bright-blue',
            'email', 'phone', 'contact_info' => 'magenta',
            'company' => 'blue',
            'status' => 'yellow',
            'type', 'lead_type' => 'cyan',
            'url', 'external_url' => 'green',
            'note_count', 'tasks_count' => 'yellow',
            'created_at', 'updated_at' => 'gray',
            default => 'white'
        };
    }
    
    private function formatColoredValue($value, string $field, int $maxLength = 100): string
    {
        $formatted = $this->formatValue($value, $maxLength);
        
        // Special formatting for specific fields
        if ($field === 'status') {
            return $this->colorizeStatus($formatted);
        }
        
        if ($field === 'type' || $field === 'lead_type') {
            return "<fg=bright-cyan>{$formatted}</>";
        }
        
        if ($field === 'url' || $field === 'external_url') {
            return "<fg=bright-green;options=underscore>{$formatted}</>";
        }
        
        if (($field === 'note_count' || $field === 'tasks_count') && is_numeric($value)) {
            $color = $value > 0 ? 'bright-yellow' : 'gray';
            return "<fg={$color}>{$formatted}</>";
        }
        
        return $formatted;
    }
    
    private function colorizeStatus(string $status): string
    {
        return match(strtolower($status)) {
            'won' => '<fg=bright-green;options=bold>✓ Won</>',
            'lost' => '<fg=red>✗ Lost</>',
            'negotiation' => '<fg=yellow>⚡ Negotiation</>',
            'proposal' => '<fg=cyan>📄 Proposal</>',
            'qualified' => '<fg=blue>⭐ Qualified</>',
            'interested' => '<fg=magenta>👀 Interested</>',
            'contacted' => '<fg=bright-blue>📞 Contacted</>',
            'new' => '<fg=bright-cyan>✨ New</>',
            default => "<fg=white>{$status}</>"
        };
    }
    
    private function selectKeyFields(array $item): array
    {
        // Priority fields to show (in order of preference)
        $priorityFields = [
            'id', 'title', 'name', 'nickname', 'email', 'status', 'type', 'lead_type',
            'company', 'phone', 'url', 'external_url', 
            'note_count', 'tasks_count', 'contact_info',
            'updated_at', 'created_at'
        ];
        
        $selectedFields = [];
        foreach ($priorityFields as $field) {
            if (array_key_exists($field, $item)) {
                $selectedFields[] = $field;
                if (count($selectedFields) >= 10) break; // Limit to 10 fields
            }
        }
        
        return $selectedFields;
    }
    
    private function formatCompactItem(array $item, array $keyFields): string
    {
        // Create a one-line summary with colors
        $parts = [];
        
        if (isset($item['id'])) {
            $parts[] = "<fg=bright-cyan>#{$item['id']}</>";
        }
        
        $nameField = $item['name'] ?? $item['title'] ?? $item['nickname'] ?? null;
        if ($nameField) {
            $name = $this->formatValue($nameField, 50);
            $parts[] = "<fg=bright-white;options=bold>{$name}</>";
        }
        
        if (isset($item['status'])) {
            $parts[] = $this->colorizeStatus($item['status']);
        }
        
        return '  ' . implode(' <fg=gray>│</> ', $parts);
    }
    
    private function renderObject($obj, OutputInterface $output, SymfonyStyle $io): void
    {
        if (method_exists($obj, 'toArray')) {
            $this->renderArray($obj->toArray(), $output, $io);
        } else {
            $this->renderArray((array)$obj, $output, $io);
        }
    }
    
    private function formatValue($value, int $maxLength = 50): string
    {
        if (is_array($value)) return json_encode($value);
        if (is_object($value)) return method_exists($value, '__toString') ? (string)$value : get_class($value);
        if (is_bool($value)) return $value ? 'true' : 'false';
        if (is_null($value)) return 'null';
        
        $str = (string)$value;
        return strlen($str) > $maxLength ? substr($str, 0, $maxLength - 3) . '...' : $str;
    }
    
    private function isAssoc(array $arr): bool
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
