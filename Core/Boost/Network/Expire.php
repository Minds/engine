<?php

namespace Minds\Core\Boost\Network;

use Minds\Core;
use Minds\Core\Data;
use Minds\Core\Notifications;

class Expire
{
    /** @var Boost $boost */
    protected $boost;

    /** @var Manager $manager */
    protected $manager;

    public function __construct($manager = null)
    {
        $this->manager = $manager ?: new Manager;
    }

    /**
     * Set the boost to expire
     * @param Boost $boost
     * @return void
     */
    public function setBoost($boost)
    {
        $this->boost = $boost;
    }

    /**
     * Expire a boost from the queue
     * @return bool
     */
    public function expire()
    {
        if (!$this->boost) {
            return false;
        }

        if ($this->boost->getState() == 'completed') {
            // Re-sync ElasticSearch
            $this->manager->resync($this->boost);

            // Already completed
            return true;
        }

        $this->boost->setCompletedTimestamp(round(microtime(true) * 1000));

        $this->manager->update($this->boost);

        Core\Events\Dispatcher::trigger('boost:completed', 'boost', ['boost' => $this->boost]);

        Core\Events\Dispatcher::trigger('notification', 'boost', [
            'to' => [$this->boost->getOwnerGuid()],
            'from' => 100000000000000519,
            'entity' => $this->boost->getEntity(),
            'notification_view' => 'boost_completed',
            'params' => [
                'impressions' => $this->boost->getImpressions(),
                'title' => $this->boost->getEntity()->title ?: $this->boost->getEntity()->message
            ],
            'impressions' => $this->boost->getImpressions()
        ]);

        //
        $notification = new Notifications\Notification();

        $notification->setType(Notifications\NotificationTypes::TYPE_BOOST_COMPLETED);
        $notification->setData(['impressions' =>  $this->boost->getImpressions()]);
        $notification->setToGuid($this->boost->getOwnerGuid());
        $notification->setEntityUrn($this->boost->getEntity()->getUrn());

        // Save and submit
        if ($this->notificationsManager->add($notification)) {

            // Some logging
            $this->logger->info("{$notification->getUuid()} {$notification->getType()} saved");

            return true; // Return true to acknowledge the event from the stream (stop it being redelivered)
        }
    }
}
