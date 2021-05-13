<?php
namespace Minds\Core\Security\Block\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\Security\Block\BlockEntry;
use Minds\Entities\User;

class EventStreamsDelegate
{
    /** @var ActionEventsTopic */
    protected $actionEventsTopic;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    public function __construct(ActionEventsTopic $actionEventsTopic = null, EntitiesBuilder $entitiesBuilder = null)
    {
        $this->actionEventsTopic = $actionEventsTopic ?? Di::_()->get('EventStreams\Topics\ActionEventsTopic');
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
    }

    /**
     * @param BlockEntry $blockEntry
     * @return void
     */
    public function onAdd(BlockEntry $blockEntry): void
    {
        /** @var User */
        $user = $this->entitiesBuilder->single($blockEntry->getActorGuid());
        $entity = $this->entitiesBuilder->single($blockEntry->getSubjectGuid());
    
        $actionEvent = new ActionEvent();
        $actionEvent
            ->setAction(ActionEvent::ACTION_BLOCK)
            ->setUser($user)
            ->setEntity($entity);

        $this->actionEventsTopic->send($actionEvent);
    }

    /**
     * @param BlockEntry $blockEntry
     * @return void
     */
    public function onDelete(BlockEntry $blockEntry): void
    {
        /** @var User */
        $user = $this->entitiesBuilder->single($blockEntry->getActorGuid());
        $entity = $this->entitiesBuilder->single($blockEntry->getSubjectGuid());
    
        $actionEvent = new ActionEvent();
        $actionEvent
            ->setAction(ActionEvent::ACTION_UNBLOCK)
            ->setUser($user)
            ->setEntity($entity);

        $this->actionEventsTopic->send($actionEvent);
    }
}
