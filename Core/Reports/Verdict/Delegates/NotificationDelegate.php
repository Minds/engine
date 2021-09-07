<?php
/**
 * Notification delegate for Verdicts
 */

namespace Minds\Core\Reports\Verdict\Delegates;

use Minds\Common\Urn;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Resolver;
use Minds\Common\SystemUser;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\Reports\Verdict\Verdict;
use Minds\Core\Plus;
use Minds\Core\Wire\Paywall\PaywallEntityInterface;
use Minds\Core\Notifications;

class NotificationDelegate
{
    /** @var EventsDispatcher */
    protected $dispatcher;

    /** @var EntitiesBuilder $entitiesBuilder */
    protected $entitiesBuilder;

    /** @var Urn $urn */
    protected $urn;

    /** @var Resolver */
    protected $entitiesResolver;

    /** @var Plus\Manager */
    protected $plusManager;

    /** @var Notifications\Manager */
    protected $notificationsManager;

    public function __construct($dispatcher = null, $entitiesBuilder = null, $urn = null, $entitiesResolver = null, $plusManager = null, Notifications\Manager $notificationsManager = null)
    {
        $this->dispatcher = $dispatcher ?: Di::_()->get('EventsDispatcher');
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
        $this->urn = $urn ?: new Urn;
        $this->entitiesResolver = $entitiesResolver ?: new Resolver();
        $this->plusManager = $plusManager ?? Di::_()->get('Plus\Manager');
        $this->notificationsManager = $notificationsManager ?? Di::_()->get('Notifications\Manager');
    }

    /**
     * Actioned notification
     * @param Verdict $verdict
     * @return void
     * @throws \Exception
     */
    public function onAction(Verdict $verdict)
    {
        $entityUrn = $verdict->getReport()->getEntityUrn();

        $entity = $this->entitiesResolver->single($this->urn->setUrn($entityUrn));

        if (!$entity) {
            $entityGuid = $this->urn->setUrn($entityUrn)->getNss();

            $entity = $this->entitiesBuilder->single($entityGuid);
        }

        if ($verdict->isUpheld()) {
            $readableAction = 'removed';

            switch ($verdict->getReport()->getReasonCode()) {
                case 2:
                    if (!($entity instanceof PaywallEntityInterface && $this->plusManager->isPlusEntity($entity))) { // We remove if its a paywalled post
                        $readableAction = 'marked as nsfw';
                    }
                    break;
            }
        } else {
            $readableAction = 'restored';
            if (!$verdict->getReport()->isAppeal()) {
                return; // Not notifiable
            }
        }

        if ($verdict->getReport()->isAppeal()) {
            $readableAction .= ' by the community appeal jury';
        } else {
            $readableAction .= '. You can appeal this decision';
        }

        $this->dispatcher->trigger('notification', 'all', [
            'to' => [$entity->getOwnerGuid()],
            'entity' => $entity,
            'from' => 100000000000000519,
            'notification_view' => 'report_actioned',
            'params' => ['action' => $readableAction],
        ]);

        // v3 notification
        $notification = new Notifications\Notification();


        $notification->setData([
                'action' => $readableAction
            ]);
        $notification->setToGuid($entity->getOwnerGuid());
        $notification->setEntityUrn($entityUrn);
        $notification->setFromGuid(SystemUser::GUID);

        $notification->setType(Notifications\NotificationTypes::TYPE_REPORT_ACTIONED);

        // Save and submit
        $this->notificationsManager->add($notification);
    }
}
