<?php
namespace Minds\Core\ActivityPub\Factories;

use Minds\Core\ActivityPub\Types\Activity\LikeType;
use Minds\Core\ActivityPub\Types\Core\OrderedCollectionPageType;
use Minds\Core\Votes\Manager as VotesManager;
use Minds\Entities\User;

class LikeFactory
{
    public function __construct(
        private readonly VotesManager $votesManager,
        private readonly ObjectFactory $objectFactory,
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

        $items = [];

        /**
         * @var array $vote
         */
        foreach ($this->votesManager->getVotesFromRelationalRepository($user) as $vote) {
            $object = $this->objectFactory->fromEntity($vote->getEntity());

            $actor = $this->objectFactory->fromEntity($user);

            $item = new LikeType();
            $item->id = "$actor->id/$object->id/like";
            $item->actor = $actor;
            $item->object = $object;
    
            $items[] = $item;
        }


        $orderedCollection->setOrderedItems($items);

        return $orderedCollection;
    }

}
