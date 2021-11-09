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
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        Di::_()->get('Config')
            ->set('min_log_level', 'INFO');

        Core\Security\ACL::_()->setIgnore(true);
    
        $manager = Di::_()->get('Feeds\TwitterSync\Manager');

        while (true) {
            foreach ($manager->syncTweets() as $connectedAccount) {
                $this->out('checking for ' . $connectedAccount->getUserGuid());
            }
            sleep(120); // Sleep for 2 mins
        }
    }

    public function exec()
    {
    }
}
