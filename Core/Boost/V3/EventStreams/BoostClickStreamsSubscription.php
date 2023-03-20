<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\EventStreams;

use DateTime;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Di\Di;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\Boost\V3\Summaries\Manager as SummariesManager;
use Minds\Core\Log\Logger;

/**
 * Subscribes to boost click events and calls to update Boost summary
 * when one is received.
 */
class BoostClickStreamsSubscription implements SubscriptionInterface
{
    public function __construct(
        private ?SummariesManager $summariesManager = null
    ) {
        $this->summariesManager ??= Di::_()->get(SummariesManager::class);
    }

    /**
     * Returns subscription id.
     * @return string subscription id.
     */
    public function getSubscriptionId(): string
    {
        return 'boost-clicks';
    }
    
    /**
     * Returns topic.
     * @return ActionEventsTopic - topic.
     */
    public function getTopic(): ActionEventsTopic
    {
        return new ActionEventsTopic();
    }

    /**
     * Returns topic regex, scoping subscription to metrics events we want to subscribe to.
     * @return string topic regex.
     */
    public function getTopicRegex(): string
    {
        return 'click';
    }

    /**
     * Called on event receipt.
     * @param EventInterface $event - event to be consumed.
     * @return bool
     */
    public function consume(EventInterface $event): bool
    {
        if (!$event instanceof ActionEvent) {
            return false;
        }

        $boost = $event->getEntity();

        if (!($boost instanceof Boost)) {
            return false;
        }

        $this->summariesManager->incrementClicks(
            boost: $boost,
            date: new DateTime('midnight')
        );

        return true;
    }
}
