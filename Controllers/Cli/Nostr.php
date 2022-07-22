<?php

namespace Minds\Controllers\Cli;

use Minds\Cli;
use Minds\Core\Di\Di;
use Minds\Interfaces;

class Nostr extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function help($command = null)
    {
        $this->out('TBD');
    }

    public function syncChannel()
    {
        $username = $this->getOpt('username');

        Di::_()->get('Nostr\PocSync')->syncChannel($username);
    }

    public function whitelist()
    {
        $pubKey = $this->getOpt('pubkey');

        $manager = Di::_()->get('Nostr\Manager');
        
        if ($manager->addToWhitelist($pubKey)) {
            $this->out('Success');
        } else {
            $this->out('There was an error');
        }
    }

    
    public function exec()
    {
    }
}
