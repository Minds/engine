<?php

namespace Minds\Controllers\Cli;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Cli;
use Minds\Interfaces;
use Minds\Exceptions;
use Minds\Entities;
use Minds\Core\Data\ElasticSearch\Prepared;
use Minds\Core\Analytics\Iterators\SignupsOffsetIterator;
use Minds\Core\Boost\Network\Manager;
use Minds\Core\Util\BigNumber;
use Minds\Helpers\Counters;

class Sitemaps extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function __construct()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    }

    public function help($command = null)
    {
        $this->out('TBD');
    }

    public function exec()
    {
        $sitemaps = Di::_()->get('Sitemaps\Manager');
        $sitemaps->build();
    }
}
