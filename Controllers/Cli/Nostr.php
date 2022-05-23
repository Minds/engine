<?php

namespace Minds\Controllers\Cli;

use Minds\Core;
use Minds\Cli;
use Minds\Core\Di\Di;
use Minds\Core\Media\YouTubeImporter\YTSubscription;
use Minds\Core\Media\YouTubeImporter\YTVideo;
use Minds\Entities\Activity;
use Minds\Entities\Video;
use Minds\Interfaces;
use Minds\Exceptions;
use Minds\Exceptions\ProvisionException;

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
