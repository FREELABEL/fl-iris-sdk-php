<?php

namespace IRIS\SDK\Console;

use Symfony\Component\Console\Application as BaseApplication;
use IRIS\SDK\Console\Commands\SDKCommand;
use IRIS\SDK\Console\Commands\ChatCommand;
use IRIS\SDK\Console\Commands\ConfigCommand;

class Application extends BaseApplication
{
    public function __construct()
    {
        parent::__construct('IRIS SDK', '1.0.0');

        $this->addCommands([
            new SDKCommand(),
            new ChatCommand(),
            new ConfigCommand(),
        ]);
    }
}
