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

    /**
     * Sync entity centric metrics. Without a given task will sync all.
     * @param string task - task matching the class name of a synchroniser
     * that is to be the exclusive synchroniser ran.
     * @example
     * - php cli.php EntityCentric sync
     * - php cli.php EntityCentric sync --task=PartnerEarningsSynchroniser
     */
    public function sync()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $singleTask = $this->getOpt('task') ?? null;

        $opts = [];
        if ($singleTask) {
            $opts['singleTask'] = $singleTask;
        }

        $daysAgo = $this->getOpt('daysAgo') ?: 0;
        $from = $this->getOpt('from') ?: strtotime("midnight $daysAgo days ago");
        $manager = new Manager();
        $manager->setFrom($from);

        $i = 0;
        foreach ($manager->sync($opts) as $record) {
            $this->out(++$i .": {$record->getUrn()}");
        }
    }
}
