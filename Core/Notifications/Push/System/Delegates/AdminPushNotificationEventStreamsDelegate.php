<?php
namespace Minds\Core\Notifications\Push\System\Delegates;

use Minds\Core\EntitiesBuilder;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\Notifications\Push\System\Models\AdminPushNotificationRequest;
use Minds\Entities\User;

/**
 *
 */
class AdminPushNotificationEventStreamsDelegate
{
    public function __construct(
        private ?ActionEventsTopic $actionEventsTopic = null
    ) {
        $this->actionEventsTopic ??= new ActionEventsTopic();
    }

    /**
     * @param AdminPushNotificationRequest $notification
     * @return void
     */
    public function onAdd(AdminPushNotificationRequest $notification): void
    {
        $notificationEvent = new ActionEvent();

        /**
         * @var User
         */
        $user = (new EntitiesBuilder())->single($notification->getAuthorId());

        $notificationEvent
            ->setAction(ActionEvent::ACTION_SYSTEM_PUSH_NOTIFICATION)
            ->setActionData($notification->export())
            ->setEntity($notification)
            ->setUser($user);

        $this->getTopic()->send($notificationEvent);
    }

    /**
     * @return ActionEventsTopic
     */
    protected function getTopic(): ActionEventsTopic
    {
        return $this->actionEventsTopic;
    }
}
