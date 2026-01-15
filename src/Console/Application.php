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
use IRIS\SDK\Console\Commands\AppCommand;
use IRIS\SDK\Console\Commands\BloqIngestCommand;
use IRIS\SDK\Console\Commands\BloqIngestionStatusCommand;
use IRIS\SDK\Console\Commands\BloqIngestionJobsCommand;
use IRIS\SDK\Console\Commands\BloqCancelIngestionCommand;
use IRIS\SDK\Console\Commands\VoiceCommand;
use IRIS\SDK\Console\Commands\PhoneCommand;
use IRIS\SDK\Console\Commands\AgentCommand;
use IRIS\SDK\Console\Commands\AutomationCommand;
use IRIS\SDK\Console\Commands\AutomationTestCommand;
use IRIS\SDK\Console\Commands\TokenCommand;
use IRIS\SDK\Console\Commands\UsersCommand;

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
            new AppCommand(),
            new BloqIngestCommand(),
            new BloqIngestionStatusCommand(),
            new BloqIngestionJobsCommand(),
            new BloqCancelIngestionCommand(),
            new VoiceCommand(),
            new PhoneCommand(),
            new AgentCommand(),
            new AutomationCommand(),
            new AutomationTestCommand(),
            new TokenCommand(),
            new UsersCommand(),
        ]);
    }
}
