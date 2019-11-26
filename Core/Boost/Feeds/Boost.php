<?php

namespace Minds\Core\Boost\Feeds;

use Minds\Common\Urn;
use Minds\Core;
use Minds\Core\Boost\Feed;

class Boost extends Feed
{
    protected function getItems(): void
    {
        /** @var Core\Boost\Network\Iterator $iterator */
        $iterator = $this->mockIterator ?: Core\Di\Di::_()->get('Boost\Network\Iterator');
        $iterator
            ->setLimit($this->limit)
            ->setOffset($this->offset)
            ->setQuality($this->quality)
            ->setType($this->type)
            ->setHydrate(false);

        if (!is_null($this->currentUser)) {
            $iterator->setUserGuid($this->currentUser->getGUID());

            if (!is_null($this->rating)) {
                $iterator->setRating($this->currentUser->getBoostRating());
            }
        }

        /** @var Core\Boost\Network\Boost $boost */
        foreach ($iterator as $boost) {
            $boostUrn = new Urn("urn:boost:{$boost->getType()}:{$boost->getGuid()}");
            $feedSyncEntity = new Core\Feeds\FeedSyncEntity();
            $feedSyncEntity
                ->setGuid((string)$boost->getGuid())
                ->setOwnerGuid((string)$boost->getOwnerGuid())
                ->setTimestamp($boost->getCreatedTimestamp())
                ->setUrn($boostUrn);

            $entity = $this->resolver->single($boostUrn);
            if (!$entity) {
                continue; // Duff entity?
            }

            $feedSyncEntity->setEntity($entity);
            $this->boosts[] = $feedSyncEntity;
        }

        $this->offset = $iterator->getOffset();

        if (isset($this->boosts[1])) { // Always offset to 2rd in list if in rotator
            $len = count($this->boosts);
            if ($this->boosts[$len - 1]) {
                $this->offset = $this->boosts[$len - 1]->getTimestamp();
            }
        }
    }

    protected function getOffsetCacheKey(): string
    {
        return $this->currentUser->guid . ':boost-offset-rotator';
    }
}
