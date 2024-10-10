<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Delegates;

use Minds\Core\EntitiesBuilder;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Types\FeaturedEntity;
use Minds\Entities\Group;
use Minds\Entities\User;

/**
 * Featured entity added delegate.
 */
class FeaturedEntityAddedDelegate
{
    public function __construct(
        private ActionEventsTopic $actionEventsTopic,
        private EntitiesBuilder $entitiesBuilder,
        private Logger $logger
    ) {
    }

    /**
     * Called when a featured entity is added.
     * @param FeaturedEntity $featuredEntity - The featured entity to add.
     * @param User $loggedInUser - The user adding the featured entity.
     * @return void
     */
    public function onAdd(FeaturedEntity $featuredEntity, User $loggedInUser): void
    {
        $entity = $this->entitiesBuilder->single($featuredEntity->entityGuid);
        
        if (!$entity || !($entity instanceof User || $entity instanceof Group)) {
            $this->logger->error("Valid featured entity not found: {$featuredEntity->entityGuid}");
            return;
        }

        $actionEvent = new ActionEvent();
        $actionEvent->setAction(ActionEvent::ACTION_FEATURED_ENTITY_ADDED)
            ->setEntity($entity)
            ->setUser($loggedInUser)
            ->setActionData([
                // will be serialized to an array.
                'featured_entity_data' => $featuredEntity
            ]);

        $this->actionEventsTopic->send($actionEvent);
    }
}
