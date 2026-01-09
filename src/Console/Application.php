<?php

namespace IRIS\SDK\Console;

use Symfony\Component\Console\Application as BaseApplication;
use IRIS\SDK\Console\Commands\SDKCommand;
use IRIS\SDK\Console\Commands\ChatCommand;
use IRIS\SDK\Console\Commands\ConfigCommand;
use IRIS\SDK\Console\Commands\ToolsCommand;
use IRIS\SDK\Console\Commands\IntegrationsCommand;
use IRIS\SDK\Console\Commands\SkillsCommand;
use IRIS\SDK\Console\Commands\MemoryComposeCommand;
use IRIS\SDK\Console\Commands\MemoryListCommand;
use IRIS\SDK\Console\Commands\MemoryShowCommand;
use IRIS\SDK\Console\Commands\MemoryAddCommand;
use IRIS\SDK\Console\Commands\SetupCommand;
use IRIS\SDK\Console\Commands\AgentCreateCommand;
use IRIS\SDK\Console\Commands\ServisAiCommand;
use IRIS\SDK\Console\Commands\EvalCommand;
use IRIS\SDK\Console\Commands\DeliverCommand;
use IRIS\SDK\Console\Commands\ScheduleCommand;
use IRIS\SDK\Console\Commands\SopCommand;
use IRIS\SDK\Console\Commands\PaymentsCommand;

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
            new SkillsCommand(),
            new MemoryComposeCommand(),
            new MemoryListCommand(),
            new MemoryShowCommand(),
            new MemoryAddCommand(),
            new AgentCreateCommand(),
            new ServisAiCommand(),
            new EvalCommand(),
            new DeliverCommand(),
            new ScheduleCommand(),
            new SopCommand(),
            new PaymentsCommand(),
        ]);
    }
}
