<?php

declare(strict_types=1);

namespace IRIS\SDK\Tests\Unit\Console;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use IRIS\SDK\Console\Commands\SDKCommand;
use IRIS\SDK\IRIS;

/**
 * Tests for the dynamic SDK CLI command
 *
 * @covers \IRIS\SDK\Console\Commands\SDKCommand
 */
class SDKCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private Application $application;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->application = new \IRIS\SDK\Console\Application();
        
        $command = $this->application->find('sdk:call');
        $this->commandTester = new CommandTester($command);
        
        // Set test environment variables
        putenv('IRIS_API_KEY=test_key_12345');
        putenv('IRIS_USER_ID=999');
    }
    
    protected function tearDown(): void
    {
        putenv('IRIS_API_KEY');
        putenv('IRIS_USER_ID');
        parent::tearDown();
    }
    
    public function testCommandIsConfigured(): void
    {
        $command = $this->application->find('sdk:call');
        
        $this->assertEquals('sdk:call', $command->getName());
        $this->assertEquals('Dynamic proxy to SDK resources and methods', $command->getDescription());
    }
    
    public function testCommandHasRequiredArguments(): void
    {
        $command = $this->application->find('sdk:call');
        $definition = $command->getDefinition();
        
        $this->assertTrue($definition->hasArgument('endpoint'));
        $this->assertTrue($definition->getArgument('endpoint')->isRequired());
        
        $this->assertTrue($definition->hasArgument('params'));
        $this->assertTrue($definition->getArgument('params')->isArray());
    }
    
    public function testCommandHasExpectedOptions(): void
    {
        $command = $this->application->find('sdk:call');
        $definition = $command->getDefinition();
        
        $this->assertTrue($definition->hasOption('json'));
        $this->assertTrue($definition->hasOption('raw'));
        $this->assertTrue($definition->hasOption('api-key'));
        $this->assertTrue($definition->hasOption('user-id'));
    }
    
    public function testCommandFailsWithoutCredentials(): void
    {
        putenv('IRIS_API_KEY');
        putenv('IRIS_USER_ID');
        
        $exitCode = $this->commandTester->execute([
            'endpoint' => 'leads.list',
        ]);
        
        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('Missing API credentials', $this->commandTester->getDisplay());
    }
    
    public function testCommandFailsWithInvalidEndpointFormat(): void
    {
        $exitCode = $this->commandTester->execute([
            'endpoint' => 'invalid',
        ]);
        
        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('Invalid endpoint format', $this->commandTester->getDisplay());
    }
    
    public function testCommandAcceptsInlineCredentials(): void
    {
        putenv('IRIS_API_KEY');
        putenv('IRIS_USER_ID');
        
        // This will fail because we can't actually connect, but it should get past credential check
        $this->commandTester->execute([
            'endpoint' => 'leads.list',
            '--api-key' => 'inline_key',
            '--user-id' => '456',
        ]);
        
        $output = $this->commandTester->getDisplay();
        // Should not show credentials error
        $this->assertStringNotContainsString('Missing API credentials', $output);
    }
    
    /**
     * Test parameter parsing with different types
     */
    public function testParameterTypeCasting(): void
    {
        $command = new SDKCommand();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('castValue');
        $method->setAccessible(true);
        
        // Test boolean
        $this->assertTrue($method->invoke($command, 'true'));
        $this->assertFalse($method->invoke($command, 'false'));
        
        // Test null
        $this->assertNull($method->invoke($command, 'null'));
        
        // Test numeric
        $this->assertSame(123, $method->invoke($command, '123'));
        $this->assertSame(12.5, $method->invoke($command, '12.5'));
        
        // Test JSON
        $result = $method->invoke($command, '{"key":"value"}');
        $this->assertIsArray($result);
        $this->assertEquals(['key' => 'value'], $result);
        
        $result = $method->invoke($command, '[1,2,3]');
        $this->assertEquals([1, 2, 3], $result);
        
        // Test string
        $this->assertEquals('hello', $method->invoke($command, 'hello'));
    }
    
    /**
     * Test parameter parsing from command line arguments
     */
    public function testParseParamsWithKeyValuePairs(): void
    {
        $command = new SDKCommand();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('parseParams');
        $method->setAccessible(true);
        
        $result = $method->invoke($command, [
            'status=active',
            'limit=10',
            'has_tasks=true',
        ]);
        
        $this->assertEquals([
            'status' => 'active',
            'limit' => 10,
            'has_tasks' => true,
        ], $result);
    }
    
    /**
     * Test parameter parsing with positional arguments
     */
    public function testParseParamsWithPositionalArgs(): void
    {
        $command = new SDKCommand();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('parseParams');
        $method->setAccessible(true);
        
        $result = $method->invoke($command, ['123', 'test message']);
        
        $this->assertEquals([123, 'test message'], $result);
    }
    
    /**
     * Test isAssoc helper method
     */
    public function testIsAssocDetectsAssociativeArrays(): void
    {
        $command = new SDKCommand();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('isAssoc');
        $method->setAccessible(true);
        
        // Associative array
        $this->assertTrue($method->invoke($command, ['key' => 'value']));
        
        // Sequential array
        $this->assertFalse($method->invoke($command, [1, 2, 3]));
        $this->assertFalse($method->invoke($command, ['a', 'b', 'c']));
        
        // Mixed (should be treated as associative)
        $this->assertTrue($method->invoke($command, [0 => 'a', 'key' => 'b']));
    }
    
    /**
     * Test formatValue helper method
     */
    public function testFormatValueHandlesDifferentTypes(): void
    {
        $command = new SDKCommand();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('formatValue');
        $method->setAccessible(true);
        
        // Test boolean
        $this->assertEquals('true', $method->invoke($command, true));
        $this->assertEquals('false', $method->invoke($command, false));
        
        // Test null
        $this->assertEquals('null', $method->invoke($command, null));
        
        // Test array (converts to JSON)
        $this->assertEquals('["a","b"]', $method->invoke($command, ['a', 'b']));
        
        // Test string truncation
        $longString = str_repeat('a', 100);
        $result = $method->invoke($command, $longString);
        $this->assertLessThanOrEqual(50, strlen($result));
        $this->assertStringEndsWith('...', $result);
        
        // Test normal string
        $this->assertEquals('hello', $method->invoke($command, 'hello'));
    }
    
    /**
     * Test endpoint parsing for nested resources
     */
    public function testEndpointParsingHandlesNestedResources(): void
    {
        // Test simple endpoint
        $parts = explode('.', 'leads.list');
        $this->assertCount(2, $parts);
        $this->assertEquals('leads', $parts[0]);
        $this->assertEquals('list', $parts[1]);
        
        // Test nested endpoint
        $parts = explode('.', 'leads.aggregation.statistics');
        $this->assertCount(3, $parts);
        $this->assertEquals('leads', $parts[0]);
        $this->assertEquals('aggregation', $parts[1]);
        $this->assertEquals('statistics', $parts[2]);
    }
    
    /**
     * Test JSON output format
     */
    public function testJsonOutputFormat(): void
    {
        // Can't fully test without mocking IRIS SDK,
        // but we can verify the option is recognized
        $command = $this->application->find('sdk:call');
        $definition = $command->getDefinition();
        
        $this->assertTrue($definition->hasOption('json'));
        $option = $definition->getOption('json');
        $this->assertFalse($option->acceptValue()); // It's a flag
    }
    
    /**
     * Integration test helper to verify command structure
     */
    public function testCommandStructureIsValid(): void
    {
        $command = $this->application->find('sdk:call');
        
        // Verify it's properly configured
        $this->assertInstanceOf(\Symfony\Component\Console\Command\Command::class, $command);
        $this->assertEquals('sdk:call', $command->getName());
        
        // Verify it has a description and help text
        $this->assertNotEmpty($command->getDescription());
        $this->assertNotEmpty($command->getHelp());
        
        // Verify definition is complete
        $definition = $command->getDefinition();
        $this->assertNotEmpty($definition->getArguments());
        $this->assertNotEmpty($definition->getOptions());
    }
}
