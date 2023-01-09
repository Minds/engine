<?php

declare(strict_types=1);

namespace Minds\Core\Boost\V3\Delegates;

use Minds\Common\SystemUser;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\Sessions\ActiveSession;
use Minds\Entities\User;

/**
 * Delegate to handle the dispatch of action events.
 */
class ActionEventDelegate
{
    public function __construct(
        private ?ActionEventsTopic $actionEventsTopic = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?ActiveSession $activeSession = null
    ) {
        $this->actionEventsTopic ??= Di::_()->get('EventStreams\Topics\ActionEventsTopic');
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->activeSession ??= Di::_()->get('Sessions\ActiveSession');
    }

    /**
     * On boost approval, dispatch an ActionEvent to subscribers.
     * @param Boost $boost - boost to dispatch event for.
     * @return void
     */
    public function onApprove(Boost $boost): void
    {
        $this->send($boost, ActionEvent::ACTION_BOOST_ACCEPTED);
    }

    /**
     * On boost rejection, dispatch an ActionEvent to subscribers.
     * @param Boost $boost - boost to dispatch event for.
     * @param int $reason - reason for rejection.
     * @return void
     */
    public function onReject(Boost $boost, int $reason): void
    {
        $this->send($boost, ActionEvent::ACTION_BOOST_REJECTED, [
            'boost_reject_reason' => $reason
        ]);
    }

    /**
     * On boost completion, dispatch an ActionEvent to subscribers.
     * @param Boost $boost - boost to dispatch event for.
     * @return void
     */
    public function onComplete(Boost $boost): void
    {
        $this->send($boost, ActionEvent::ACTION_BOOST_COMPLETED);
    }

    /**
     * Dispatch an ActionEvent to subscribers.
     * @param Boost $boost - boost to dispatch event for.
     * @param string $action - action to be dispatched for.
     * @param array $actionData - optional action data.
     * @return void
     */
    private function send(Boost $boost, string $action, array $actionData = []): void
    {
        $actionEvent = new ActionEvent();
        $actionEvent->setAction($action)
            ->setEntity($boost)
            ->setUser($this->getSender($action));

        $actionData['boost_location'] = $boost->getTargetLocation();
        $actionData['boost_owner_guid'] = $boost->getOwnerGuid();

        $actionEvent->setActionData($actionData);

        $this->actionEventsTopic->send($actionEvent);
    }

    /**
     * Gets sender for ActionEvent based on event type.
     * @param string $action - action.
     * @return User sender.
     */
    private function getSender(string $action): User
    {
        if ($action === ActionEvent::ACTION_BOOST_COMPLETED || php_sapi_name() === 'cli') {
            return $this->entitiesBuilder->single(SystemUser::GUID);
        }
        return $this->activeSession->getUser();
    }
}
