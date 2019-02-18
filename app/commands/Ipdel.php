<?php

namespace App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Ipdel extends Command
{
    protected static $defaultName = 'app:ip-del';

    protected function configure()
    {

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
    	empty($cron['ipdel_day']) && $cron['ipdel_day'] = 7;

        $deltime = gmtime() - $cron['ipdel_day'] * 3600 * 24;
        $sql = "DELETE FROM " . $GLOBALS['ecs']->table('stats') .
            "WHERE  access_time < '$deltime'";
        $GLOBALS['db']->query($sql);
    }
}
