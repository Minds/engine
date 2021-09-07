<?php
/**
 * Notification delegate for referrals
 */
namespace Minds\Core\Referrals\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\Referrals\Referral;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;

class NotificationDelegate
{
    /** @var EventsDispatcher */
    protected $dispatcher;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var ActionEventsTopic */
    protected $actionEventTopic;

    public function __construct($dispatcher = null, $entitiesBuilder = null, ActionEventsTopic $actionEventTopic = null)
    {
        $this->dispatcher = $dispatcher ?: Di::_()->get('EventsDispatcher');
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
        $this->actionEventTopic = $actionEventTopic ?? Di::_()->get('EventStreams\Topics\ActionEventsTopic');
    }

    /**
     * Sends a notification to referrer on prospect state change
     * @param Referral $referral
     * @return void
     */
    public function notifyReferrer(Referral $referral)
    {
        $entityGuid = $referral->getProspectGuid();
        $entity = $this->entitiesBuilder->single($entityGuid);

        if (!$referral->getJoinTimestamp()) {
            $notification_view = 'referral_pending';
        } else {
            $notification_view = 'referral_complete';
        }

        $this->dispatcher->trigger('notification', 'all', [
            'to' => [$referral->getReferrerGuid()],
            'entity' => $entity,
            'from' => $referral->getProspectGuid(),
            'notification_view' => $notification_view,
            'params' => [],
        ]);
    }


    /**
     * Sends a notification to pending referral prospect to suggest they join rewards program
     * @param Referral $referral
     * @return void
     */
    public function notifyProspect(Referral $referral)
    {
        $entityGuid = $referral->getReferrerGuid();
        $entity = $this->entitiesBuilder->single($entityGuid);

        if ($referral->getJoinTimestamp()) {
            return;
        }

        $prospectGuid = $referral->getProspectGuid();
        $prospectEntity = $this->entitiesBuilder->single($prospectGuid);

        $this->dispatcher->trigger('notification', 'all', [
            'to' => [$prospectGuid],
            'entity' => $entity,
            'from' => $referral->getReferrerGuid(),
            'notification_view' => 'referral_ping',
            'params' => [],
        ]);

        $actionEvent = new ActionEvent();
        $actionEvent
            ->setAction(ActionEvent::ACTION_REFERRAL_PING)
            ->setEntity($entity)
            ->setUser($prospectEntity);

        $this->actionEventTopic->send($actionEvent);
    }
}
