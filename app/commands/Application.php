<?php

namespace app\console;

use Phinx\Console\Command;
use Symfony\Component\Console\Application as Foundation;

class Application extends Foundation
{
    /**
     * Application constructor.
     * @param null $version
     */
    public function __construct($version = null)
    {
        if ($version === null) {
            $composer = file_get_contents(__DIR__ . '/../../composer.json');
            $config = json_decode($composer, true);
            $version = $config['version'];
        }

        parent::__construct('Console by ECTouch - https://www.ectouch.cn', $version);

        $this->addCommands([
            new Command\Init(),
            new Command\Create(),
            new Command\Migrate(),
            new Command\Rollback(),
            new Command\Status(),
            new Command\Breakpoint(),
            new Command\Test(),
            new Command\SeedCreate(),
            new Command\SeedRun(),
        ]);

        foreach (glob(__DIR__ . '/*.php') as $file) {
            $command = 'App\\Console\\' . basename($file, '.php');
            if (stripos($command, basename(__FILE__, '.php')) === false) {
                $this->add(new $command());
            }
        }
    }
}
