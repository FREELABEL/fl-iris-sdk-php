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
        
        $apiKey = $input->getOption('api-key') ?: getenv('IRIS_API_KEY');
        $userId = $input->getOption('user-id') ?: getenv('IRIS_USER_ID');
        
        if (!$apiKey || !$userId) {
            $io->error('Missing API credentials. Set IRIS_API_KEY and IRIS_USER_ID environment variables.');
            return Command::FAILURE;
        }
        
        try {
            $iris = new IRIS(['api_key' => $apiKey, 'user_id' => (int)$userId]);
            
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
                // Check if this sub-resource method needs parameters
                // Extract positional params (numeric keys) for sub-resource
                $positionalParams = [];
                foreach ($params as $key => $value) {
                    if (is_int($key)) {
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
                $target = $target->{$sub};
            }
        }
        
        // Call method with params
        if (!method_exists($target, $method)) {
            throw new \BadMethodCallException("Method '{$method}' not found on resource");
        }
        
        // For methods that expect a single array argument, pass params as-is
        // Otherwise spread the params as individual arguments
        if (empty($params)) {
            return $target->{$method}();
        }
        
        // Check if all params are named (key=value format)
        $hasNamedParams = !empty($params) && array_keys($params) !== range(0, count($params) - 1);
        
        if ($hasNamedParams) {
            // Named parameters - pass as single array argument
            return $target->{$method}($params);
        } else {
            // Positional parameters - spread as individual arguments
            return $target->{$method}(...array_values($params));
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
        foreach ($data as $index => $item) {
            $output->writeln(sprintf('<info>%d.</info> %s', $index + 1, $this->formatCompactItem($item, $keyFields)));
            
            // Show selected fields
            foreach ($keyFields as $field) {
                if (isset($item[$field]) && $item[$field] !== null && $item[$field] !== '') {
                    $value = $this->formatValue($item[$field], 100);
                    $output->writeln(sprintf('   <comment>%s:</comment> %s', $field, $value));
                }
            }
            $output->writeln('');
        }
        
        $output->writeln(sprintf('<info>Total: %d items</info>', count($data)));
        $output->writeln('<comment>Tip: Use --json flag for full data</comment>');
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
        // Create a one-line summary
        $parts = [];
        
        if (isset($item['id'])) {
            $parts[] = "ID: {$item['id']}";
        }
        
        $nameField = $item['name'] ?? $item['title'] ?? $item['nickname'] ?? null;
        if ($nameField) {
            $parts[] = $this->formatValue($nameField, 40);
        }
        
        return implode(' | ', $parts);
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
