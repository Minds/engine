<?php

namespace Minds\Controllers\Cli;

use Minds\Cli;
use Minds\Core\Di\Di;
use Minds\Interfaces;

class Nostr extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function __construct()
    {
        define('__MINDS_INSTALLING__', true);
    }

    public function help($command = null)
    {
        $this->out('TBD');
    }

    public function syncChannel()
    {
        $username = $this->getOpt('username');

        Di::_()->get('Nostr\PocSync')->syncChannel($username);
    }

    
    public function exec()
    {
    }
}
