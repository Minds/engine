<?php

/**
 * Minds Groups Feed Handler
 *
 * @author emi
 */

namespace Minds\Core\Groups;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Entities;
use Minds\Core\Groups\Delegates\PropagateRejectionDelegate;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Entities\User;
use Minds\Core\Notifications\Manager as NotificationsManager;
use Minds\Core\Notifications\Notification;
use Minds\Core\Notifications\NotificationTypes;
use Minds\Core\Log\Logger;

// TODO: Migrate to new Feeds CQL (approveAll)
class Feeds
{
    /** @var Entities\Group $group */
    protected $group;

    /** @var Core\EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Delegates\PropagateRejectionDelegate */
    protected $propagateRejectionDelegate;

    /** @var ActionEventsTopic */
    protected $actionEventsTopic;

    /** @var NotificationsManager */
    protected $notificationsManager;

    /** @var Logger */
    protected $logger;
    /**
     * Feeds constructor.
     * @param null $entitiesBuilder
     */
    public function __construct($entitiesBuilder = null, $propagateRejectionDelegate = null, ActionEventsTopic $actionEventsTopic = null, NotificationsManager $notificationsManager = null, Logger $logger = null)
    {
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
        $this->propagateRejectionDelegate = $propagateRejectionDelegate ?? new PropagateRejectionDelegate();
        $this->actionEventsTopic = $actionEventsTopic ?? Di::_()->get('EventStreams\Topics\ActionEventsTopic');
        $this->notificationsManager = $notificationsManager ?? Di::_()->get('Notifications\Manager');
        $this->logger = $logger ?? Di::_()->get('Logger');
    }

    /**
     * @param Entities\Group $group
     * @return $this
     */
    public function setGroup(Entities\Group $group)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * @param array $options
     * @return array - data | next
     * @throws \Exception
     */
    public function getAll(array $options = [])
    {
        if (!$this->group) {
            throw new \Exception('Group not set');
        }

        /** @var AdminQueue $adminQueue */
        $adminQueue = Di::_()->get('Groups\AdminQueue');

        $rows = $adminQueue->getAll($this->group, $options);

        if (!$rows) {
            return [
                'data' => [],
                'next' => ''
            ];
        }

        $guids = [];

        foreach ($rows as $row) {
            $guids[] = $row['value'];
        }

        $data = [];

        if ($guids) {
            $data = Di::_()->get('Entities')->get([ 'guids' => $guids ]);
        }

        return [
            'data' => $data,
            'next' => base64_encode($rows->pagingStateToken())
        ];
    }

    public function count()
    {
        if (!$this->group) {
            throw new \Exception('Group not set');
        }

        /** @var AdminQueue $adminQueue */
        $adminQueue = Di::_()->get('Groups\AdminQueue');
        $rows = $adminQueue->count($this->group);

        if (!$rows) {
            return 0;
        }

        return (int) $rows[0]['count']->value();
    }

    /**
     * @param Entities\Activity $activity
     * @return bool
     * @throws \Exception
     */
    public function queue(Entities\Activity $activity, array $options = [])
    {
        $options = array_merge([
            'notification' => true
        ], $options);

        if (!$this->group) {
            throw new \Exception('Group not set');
        }

        if (!$activity || !$activity->guid) {
            throw new \Exception('Invalid group activity');
        }

        /** @var AdminQueue $adminQueue */
        $adminQueue = Di::_()->get('Groups\AdminQueue');
        $success = $adminQueue->add($this->group, $activity);


        if ($success && $options['notification']) {
            $this->sendNotification('add', $activity);
            $this->emitActionEvent(ActionEvent::ACTION_GROUP_QUEUE_ADD, $activity->getOwnerEntity(), $activity);
        }

        return $success;
    }

    /**
     * @param Entities\Activity $activity
     * @param array $options
     * @return bool
     * @throws \Exception
     */
    public function approve(Entities\Activity $activity, array $options = [])
    {
        $options = array_merge([
            'notification' => true
        ], $options);

        if (!$this->group) {
            throw new \Exception('Group not set');
        }

        if (!$activity || !$activity->guid) {
            throw new \Exception('Invalid group activity');
        }

        if ($activity->container_guid != $this->group->getGuid()) {
            throw new \Exception('Activity doesn\'t belong to this group');
        }

        $activity->indexes = [
            "activity:container:$activity->container_guid",
            "activity:network:$activity->owner_guid"
        ];

        $activity->setPending(false);
        $activity->save(true);

        if ($activity->entity_guid) {
            $attachment = $this->entitiesBuilder->single($activity->entity_guid);

            if ($attachment && ($attachment->subtype == 'image' || $attachment->subtype == 'video') && !$attachment->getWireThreshold()) {
                $attachment->access_id = 2;
                $attachment->save();
            }
        }

        /** @var AdminQueue $adminQueue */
        $adminQueue = Di::_()->get('Groups\AdminQueue');
        $success = $adminQueue->delete($this->group, $activity);

        if ($success && $options['notification']) {
            $this->emitActionEvent(ActionEvent::ACTION_GROUP_QUEUE_APPROVE, Core\Session::getLoggedinUser(), $activity);
            $this->sendNotification('approve', $activity);

            (new Notifications())
                ->setGroup($this->group)
                ->queue('activity');
        }

        return $success;
    }

    /**
     * @param Entities\Activity $activity
     * @param array $options
     * @return bool
     * @throws \Exception
     */
    public function reject(Entities\Activity $activity, array $options = [])
    {
        $options = array_merge([
            'notification' => true
        ], $options);

        if (!$this->group) {
            throw new \Exception('Group not set');
        }

        if (!$activity || !$activity->guid) {
            throw new \Exception('Invalid group activity');
        }

        if ($activity->container_guid != $this->group->getGuid()) {
            throw new \Exception('Activity doesn\'t belong to this group');
        }

        /** @var AdminQueue $adminQueue */
        $adminQueue = Di::_()->get('Groups\AdminQueue');

        $activityOwnerGuid= $activity->owner_guid;

        $success = $adminQueue->delete($this->group, $activity);

        if ($success && $options['notification']) {
            // ActionEvent doesn't work bc the post gets deleted on reject,
            // so we bypass pulsar and manually send notification instead

            // $this->emitActionEvent(ActionEvent::ACTION_GROUP_QUEUE_REJECT, Core\Session::getLoggedinUser(), $activity);

            $notification = new Notification();
            $notification->setType(NotificationTypes::TYPE_GROUP_QUEUE_REJECT);

            $notification->setFromGuid((string) Core\Session::getLoggedInUserGuid());
            $notification->setToGuid((string) $activityOwnerGuid);

            $notification->setEntityUrn($this->group->getUrn());

            // Save and submit
            if ($this->notificationsManager->add($notification)) {
                // Some logging
                $this->logger->info("{$notification->getUuid()} {$notification->getType()} saved");
            }


            $this->sendNotification('reject', $activity);
        }

        $this->propagateRejectionDelegate->onReject($activity);

        return $success;
    }

    /**
     * @return boolean[]
     * @throws \Exception
     */
    public function approveAll()
    {
        if (!$this->group) {
            throw new \Exception('Group not set');
        }

        // TODO: Run in a queue!

        $results = [];

        /** @var AdminQueue $adminQueue */
        $adminQueue = Di::_()->get('Groups\AdminQueue');
        $rows = $adminQueue->getAll($this->group);

        foreach ($rows as $row) {
            $activity = Di::_()->get('Entities\Factory')->build($row['value']);

            $results[$activity->guid] =
                $this->approve($activity, [ 'notification' => false ]);
        }

        return $results;
    }

    /**
     * @param string $type
     * @param Entities\Activity $activity
     */
    public function emitActionEvent($action, User $actor, Entities\Activity $activity)
    {
        $actionEvent = new ActionEvent();

        $actionEvent
            ->setAction($action)
            ->setEntity($activity)
            ->setUser($actor)
            ->setActionData([
                'group_urn' => $this->group->getUrn(),
            ]);

        $this->actionEventsTopic->send($actionEvent);
    }


    /**
     * @param string $type
     * @param Entities\Activity $activity
     */
    public function sendNotification($type, Entities\Activity $activity)
    {
        Core\Events\Dispatcher::trigger('notification', 'group', [
            'to' => [ $activity->owner_guid ],
            'from' => 100000000000000519,
            'notification_view' => "group_queue_{$type}",
            'entity' => $activity,
            'params' => [
                'group' => $this->group->export()
            ]
        ]);
    }
}
