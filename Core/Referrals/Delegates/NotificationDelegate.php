<?php
/**
 * Notification delegate for referrals
 */
namespace Minds\Core\Referrals\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\Referrals\Referral;
use Minds\Core\Events\EventsDispatcher;

class NotificationDelegate
{
    /** @var EventsDispatcher */
    protected $dispatcher;

    /** @var EntitiesBuilder $entitiesBuilder */
    protected $entitiesBuilder;

    public function __construct($dispatcher = null, $entitiesBuilder = null, $urn = null)
    {
        $this->dispatcher = $dispatcher ?: Di::_()->get('EventsDispatcher');
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
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

        $this->dispatcher->trigger('notification', 'all', [
            'to' => [$referral->getProspectGuid()],
            'entity' => $entity,
            'from' => $referral->getReferrerGuid(),
            'notification_view' => 'referral_ping',
            'params' => [],
        ]);
    }
}
