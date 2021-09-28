<?php

namespace Minds\Controllers\Cli;

use Minds\Core;
use Minds\Cli;
use Minds\Core\Di\Di;
use Minds\Core\Media\YouTubeImporter\YTSubscription;
use Minds\Core\Media\YouTubeImporter\YTVideo;
use Minds\Entities\Video;
use Minds\Interfaces;
use Minds\Exceptions;
use Minds\Exceptions\ProvisionException;

class TwitterSync extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function __construct()
    {
        define('__MINDS_INSTALLING__', true);
    }

    public function help($command = null)
    {
        $this->out('TBD');
    }

    public function sync()
    {
        Core\Security\ACL::_()->setIgnore(true);
    
        $manager = Di::_()->get('Feeds\TwitterSync\Manager');
        foreach ($manager->syncTweets() as $connectedAccount) {
            $this->out('checking for ' . $connectedAccount->getUserGuid());
        }
    }

    public function exec()
    {
    }
}
