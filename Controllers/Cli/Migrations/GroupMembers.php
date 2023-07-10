<?php

namespace Minds\Controllers\Cli\Migrations;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Cli;
use Minds\Interfaces;
use Minds\Exceptions;
use Minds\Entities;
use Stripe;
use Minds\Core\Data\ElasticSearch\Prepared;
use Minds\Core\Analytics\Iterators\SignupsOffsetIterator;
use Minds\Core\Groups\V2\Membership\Migration;

class GroupMembers extends Cli\Controller implements Interfaces\CliControllerInterface
{
    private $migration;

    public function __construct()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        Di::_()->get('Config')->set('min_log_level', \Monolog\Logger::INFO);
        $this->migration = new Migration();
    }

    public function help($command = null)
    {
        $this->out('TBD');
    }

    public function exec()
    {
        $pagingToken = base64_decode($this->getOpt('paging-token'), true);
        $this->migration->run($pagingToken);
    }
}
