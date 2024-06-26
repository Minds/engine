<?php

declare(strict_types=1);

/**
 * This subscription will build emails/notifications from stream events
 */
namespace Minds\Core\Boost\V3\EventStreams;

use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Di\Di;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\EventStreams\Topics\TopicInterface;
use Minds\Core\Log\Logger;
use Minds\Core\Email\V2\Campaigns\Recurring\BoostV3\BoostEmailer;

/**
 * Event stream to handle the sending of emails for various boost action events.
 */
class BoostEmailEventStreamSubscription implements SubscriptionInterface
{
    public function __construct(
        private ?BoostEmailer $boostEmailer = null,
        private ?Logger $logger = null
    ) {
        $this->boostEmailer ??= new BoostEmailer();
        $this->logger ??= Di::_()->get('Logger');
    }

    /**
     * Gets subscription id.
     * @return string subscription id.
     */
    public function getSubscriptionId(): string
    {
        return 'boost-email';
    }

    /**
     * Get topic.
     * @return TopicInterface topic
     */
    public function getTopic(): TopicInterface
    {
        return new ActionEventsTopic();
    }

    /**
     * Returns topic regex, scoping subscription to events we want to subscribe to.
     * @return string topic regex
     */
    public function getTopicRegex(): string
    {
        return '(boost_created|boost_rejected|boost_accepted|boost_completed|boost_cancelled)';
    }

    /**
     * Called when there is a new event
     * @param EventInterface $event
     * @return bool
     */
    public function consume(EventInterface $event): bool
    {
        if (!$event instanceof ActionEvent) {
            return false;
        }

        $this->logger->info("Consuming a {$event->getAction()} action");

        if (!$boost = $event->getEntity()) {
            $this->logger->error("Boost ({$boost->getGuid()}) provided to event stream with no entity");
            return true;
        }

        if (!$boost instanceof Boost) {
            $this->logger->warning("Non boost (entity guid: {$boost->getGuid()}) provided to event stream");
            return true;
        }

        $this->logger->info("Dispatching {$event->getAction()} for {$boost->getGuid()} to BoostEmailer");
        
        try {
            $this->boostEmailer
                ->setBoost($boost)
                ->setTopic($event->getAction())
                ->send();
        } catch (\Exception $e) {
            $this->logger->error($e);
        }

        return true;
    }
}
