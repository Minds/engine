<?php

namespace Minds\Controllers\Cli;

use Minds\Core\Minds;
use Minds\Cli;
use Minds\Core\Feeds\Elastic\Manager;
use Minds\Exceptions\CliException;
use Minds\Interfaces;

class Top extends Cli\Controller implements Interfaces\CliControllerInterface
{
    /** @var Manager */
    private $manager;

    public function __construct()
    {
        $minds = new Minds();
        $minds->start();
        $this->manager = new Manager();
    }

    public function help($command = null)
    {
        $this->out('Not implemeted');
    }

    public function exec()
    {
        $this->out('Not implemeted');
    }
}
