<?php

namespace Minds\Controllers\Cli;

use Minds\Core;
use Minds\Core\Analytics\EntityCentric\Manager;
use Minds\Cli;
use Minds\Interfaces;
use Minds\Exceptions;
use Minds\Entities;

class EntityCentric extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function __construct()
    {
    }

    public function help($command = null)
    {
        $this->out('TBD');
    }
    
    public function exec()
    {
        $this->out('Missing subcommand');
    }

    public function sync()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $daysAgo = $this->getOpt('daysAgo') ?: 0;
        $from = $this->getOpt('from') ?: strtotime("midnight $daysAgo days ago");
        $manager = new Manager();
        $manager->setFrom($from);

        $i = 0;
        foreach ($manager->sync() as $record) {
            $this->out(++$i);
        }
    }
}
