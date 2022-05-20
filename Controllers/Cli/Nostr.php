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

        /** @var Core\EntitiesBuilder */
        $entitiesBuilder = Di::_()->get('EntitiesBuilder');
    
        $user = $entitiesBuilder->getByUserByIndex($username);

        /** @var Core\Nostr\Manager */
        $nostrManager = Di::_()->get('Nostr\Manager');

        $nostrEvent = $nostrManager->buildNostrEvent($user);
        $nostrManager->emitEvent($nostrEvent);

        // Sync feed?
        /** @var Core\Feeds\Elastic\Manager */
        $feedsManager = Di::_()->get('Feeds\Elastic\Manager');
        foreach ($feedsManager->getList([
            'type' => 'activity',
            'container_guid' => $user->getGuid(),
            'algorithm' => 'latest',
            'period' => 'all',
            'single_owner_threshold' => 0,
        ]) as $item) {
            $activity = $entitiesBuilder->single($item->getGuid());

            if (!$activity instanceof Activity) {
                continue;
            }

            $nostrEvent = $nostrManager->buildNostrEvent($activity);
            $nostrManager->emitEvent($nostrEvent);
            $this->out('Sync post: ' . $activity->getGuid() . ' ' . $nostrEvent->getId());
        };
    }

    
    public function exec()
    {
    }
}
