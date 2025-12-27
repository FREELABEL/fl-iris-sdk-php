<?php

namespace IRIS\SDK\Console;

use Symfony\Component\Console\Application as BaseApplication;
use IRIS\SDK\Console\Commands\SDKCommand;
use IRIS\SDK\Console\Commands\ChatCommand;
use IRIS\SDK\Console\Commands\ConfigCommand;
use IRIS\SDK\Console\Commands\ToolsCommand;
use IRIS\SDK\Console\Commands\IntegrationsCommand;
use IRIS\SDK\Console\Commands\MemoryComposeCommand;
use IRIS\SDK\Console\Commands\MemoryListCommand;
use IRIS\SDK\Console\Commands\MemoryShowCommand;
use IRIS\SDK\Console\Commands\MemoryAddCommand;
use IRIS\SDK\Console\Commands\SetupCommand;

class Application extends BaseApplication
{
    public function __construct()
    {
        parent::__construct('IRIS SDK', '1.0.0');

        $this->addCommands([
            new SetupCommand(),
            new SDKCommand(),
            new ChatCommand(),
            new ConfigCommand(),
            new ToolsCommand(),
            new IntegrationsCommand(),
            new MemoryComposeCommand(),
            new MemoryListCommand(),
            new MemoryShowCommand(),
            new MemoryAddCommand(),
        ]);
    }
}
