<?php

namespace Minds\Core\Supermind\Delegates;

use Minds\Common\SystemUser;
use Minds\Core\Di\Di;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Entities\User;

class EventsDelegate
{
    /** @var ActionEventsTopic */
    protected $actionEventsTopic;

    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    public function __construct(
        ActionEventsTopic $actionEventsTopic = null,
        $entitiesBuilder = null
    ) {
        $this->actionEventsTopic = $actionEventsTopic ?? Di::_()->get('EventStreams\Topics\ActionEventsTopic');

        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
    }

    /**
     * Submits action event when a new supermind request is created
     * @param SupermindRequest $supermindRequest
     * @return void
     */
    public function onCompleteSupermindRequestCreation(SupermindRequest $supermindRequest)
    {
        $actionEvent = new ActionEvent();
        $actionEvent
            ->setAction(ActionEvent::ACTION_SUPERMIND_REQUEST_CREATE)
            ->setEntity($supermindRequest)
            ->setUser($this->buildUser($supermindRequest->getSenderGuid()));

        $this->actionEventsTopic->send($actionEvent);
    }

    /**
     * Submits action event when a supermind request is accepted
     * @param SupermindRequest $supermindRequest
     * @return void
     */
    public function onAcceptSupermindRequest(SupermindRequest $supermindRequest)
    {
        $targetUser = $this->buildUser($supermindRequest->getReceiverGuid());

        $actionEvent = new ActionEvent();
        $actionEvent
            ->setAction(ActionEvent::ACTION_SUPERMIND_REQUEST_ACCEPT)
            ->setEntity($supermindRequest)
            ->setUser($targetUser);

        $this->actionEventsTopic->send($actionEvent);
    }

    /**
     * Submits action event when a supermind request is rejected
     * @param SupermindRequest $supermindRequest
     * @return void
     */
    public function onRejectSupermindRequest(SupermindRequest $supermindRequest)
    {
        $actionEvent = new ActionEvent();
        $actionEvent
            ->setAction(ActionEvent::ACTION_SUPERMIND_REQUEST_REJECT)
            ->setEntity($supermindRequest)
            ->setUser($this->buildUser($supermindRequest->getReceiverGuid()));

        $this->actionEventsTopic->send($actionEvent);
    }

    /**
     * Submits action event when a supermind request expires
     * @param SupermindRequest $supermindRequest
     * @return void
     */
    public function onExpireSupermindRequest(SupermindRequest $supermindRequest)
    {
        $actionEvent = new ActionEvent();
        $actionEvent
            ->setAction(ActionEvent::ACTION_SUPERMIND_REQUEST_EXPIRE)
            ->setEntity($supermindRequest)
            ->setUser(new SystemUser);

        $this->actionEventsTopic->send($actionEvent);
    }

    /**
     * Triggers event when a supermind request is expiring soon
     * @param SupermindRequest $supermindRequest
     * @return void
     */
    public function onSupermindRequestExpiringSoon(SupermindRequest $supermindRequest)
    {
        $actionEvent = new ActionEvent();
        $actionEvent
            ->setAction(ActionEvent::ACTION_SUPERMIND_REQUEST_EXPIRING_SOON)
            ->setEntity($supermindRequest)
            ->setUser($this->buildUser($supermindRequest->getSenderGuid()));
        $this->actionEventsTopic->send($actionEvent);
    }

    /**
     * Build user from user guid
     * @param string $guid
     * @return User | void
     */
    public function buildUser($guid)
    {
        $user = $this->entitiesBuilder->single($guid);
        if ($user instanceof User) {
            return $user;
        }
    }
}
