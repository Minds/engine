<?php
namespace Minds\Core\ActivityPub\Factories;

use Minds\Core\ActivityPub\Enums\ActivityFactoryOpEnum;
use Minds\Core\ActivityPub\Manager;
use Minds\Core\ActivityPub\Types\Core\OrderedCollectionPageType;
use Minds\Core\Feeds\Elastic\V2\Manager as FeedsManager;
use Minds\Core\Feeds\Elastic\V2\QueryOpts;
use Minds\Entities\User;

class OutboxFactory
{
    public function __construct(
        protected Manager $manager,
        protected FeedsManager $feedsManager,
        protected ObjectFactory $objectFactory,
        protected ActorFactory $actorFactory,
        protected ActivityFactory $activityFactory,
    ) {
        
    }

    /**
     * Constructs an outbox for a user
     */
    public function build(string $uri, User $user): OrderedCollectionPageType
    {
        $orderedCollection = new OrderedCollectionPageType();
        $orderedCollection->setId($uri);

        $orderedCollection->setPartOf($uri . 'outbox');

        $queryOpts = new QueryOpts(
            user: $user,
            onlyOwn: true,
        );

        $items = [];

        foreach ($this->feedsManager->getLatest($queryOpts) as $entity) {
            $items[]= $this->activityFactory->fromEntity(ActivityFactoryOpEnum::CREATE, $entity, $user);
        }

        $orderedCollection->setOrderedItems($items);

        return $orderedCollection;
    }

}
