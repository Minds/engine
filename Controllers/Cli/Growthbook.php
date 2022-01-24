<?php

namespace Minds\Controllers\Cli;

use Minds\Cli;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Interfaces;

class Growthbook extends Cli\Controller implements Interfaces\CliControllerInterface
{
    /** @var Core\Experiments\Manager */
    protected $manager;

    public function __construct()
    {
        Di::_()->get('Config')
            ->set('min_log_level', 'INFO');
        $this->manager = Di::_()->get('Experiments\Manager');
    }

    public function help($command = null)
    {
        $this->out('TBD');
    }

    public function exec()
    {
        $this->out('See help');
    }

    public function syncCache()
    {
        $this->manager->getFeatures(useCached: false);
    }
}
