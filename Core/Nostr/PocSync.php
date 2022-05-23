<?php
namespace Minds\Core\Nostr;

use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds;
use Minds\Core\Log\Logger;
use Minds\Entities\Activity;
use Minds\Entities\User;

/**
 * Proof of concept sync
 */
class PocSync
{
    public function __construct(
        protected ?EntitiesBuilder $entitiesBuilder = null,
        protected ?Manager $nostrManager = null,
        protected ?Feeds\Elastic\Manager $feedsManager = null,
        protected ?Logger $logger = null
    ) {
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->nostrManager ??= Di::_()->get('Nostr\Manager');
        $this->feedsManager = Di::_()->get('Feeds\Elastic\Manager');
        $this->logger ??= Di::_()->get('Logger');
    }

    /**
     * @param string $username
     * @return void
     */
    public function syncChannel(string $username): void
    {
        $user = $this->entitiesBuilder->getByUserByIndex($username);

        //

        $nostrEvent = $this->nostrManager->buildNostrEvent($user);
        $this->nostrManager->emitEvent($nostrEvent);

        //

        $this->syncActivities($user);
    }

    /**
     * @param User $user
     * @return void
     */
    protected function syncActivities(User $user): void
    {
        foreach ($this->feedsManager->getList([
            'type' => 'activity',
            'container_guid' => $user->getGuid(),
            'algorithm' => 'latest',
            'period' => 'all',
            'single_owner_threshold' => 0,
        ]) as $item) {
            $activity = $this->entitiesBuilder->single($item->getGuid());

            if (!$activity instanceof Activity) {
                continue;
            }

            $nostrEvent = $this->nostrManager->buildNostrEvent($activity);
            $this->nostrManager->emitEvent($nostrEvent);
            $this->logger->info('Sync post: ' . $activity->getGuid() . ' ' . $nostrEvent->getId());
        };
    }
}
