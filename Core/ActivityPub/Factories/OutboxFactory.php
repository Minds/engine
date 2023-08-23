<?php
namespace Minds\Core\ActivityPub\Factories;

use Minds\Core\ActivityPub\Types\Activity\CreateType;
use Minds\Core\ActivityPub\Factories\ActorFactory;
use Minds\Core\ActivityPub\Types\Core\OrderedCollectionPageType;
use Minds\Core\ActivityPub\Factories\ObjectFactory;
use Minds\Core\Feeds\Elastic\V2\Manager as FeedsManager;
use Minds\Core\Feeds\Elastic\V2\QueryOpts;
use Minds\Entities\User;

class OutboxFactory
{
    public function __construct(
        protected FeedsManager $feedsManager,
        protected ObjectFactory $objectFactory,
        protected ActorFactory $actorFactory,
    ) {
        
    }

    /**
     * Constructs an outbox for a user
     */
    public function build(string $uri, User $user): OrderedCollectionPageType
    {
        $orderedCollection = new OrderedCollectionPageType();
        $orderedCollection->setId($uri);

        // $baseUrl = $this->buildBaseUrl($user);
        $orderedCollection->setPartOf($uri . 'outbox');

        $queryOpts = new QueryOpts(
            user: $user,
            onlyOwn: true,
        );

        $items = [];

        foreach ($this->feedsManager->getLatest($queryOpts) as $entity) {
            $object = $this->objectFactory->fromEntity($entity);

            $item = new CreateType();
            $item->id = $object->id . '/activity';
            $item->actor = $this->actorFactory->fromEntity($user);
            $item->object = $object;
    
            $items[] = $item;
        }


        $orderedCollection->setOrderedItems($items);

        return $orderedCollection;
    }

}
