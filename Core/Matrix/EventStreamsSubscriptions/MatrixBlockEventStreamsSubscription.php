<?php
/**
 * This subscription will sync a users block list
 */
namespace Minds\Core\Matrix\EventStreamsSubscriptions;

use Minds\Core\Di\Di;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\EventStreams\Topics\TopicInterface;
use Minds\Core\Matrix;

class MatrixBlockEventStreamsSubscription implements SubscriptionInterface
{
    /** @var Matrix\BlockListSync */
    protected $blockListSync;

    public function __construct(Matrix\BlockListSync $blockListSync = null)
    {
        $this->blockListSync = $blockListSync ?? Di::_()->get('Matrix\BlockListSync');
    }
 
    /**
     * @return string
     */
    public function getSubscriptionId(): string
    {
        return 'matrix-block';
    }

    /**
     * @return TopicInterface
     */
    public function getTopic(): TopicInterface
    {
        return new ActionEventsTopic();
    }

    /**
     * @return string
     */
    public function getTopicRegex(): string
    {
        return '(block|unblock)'; // Notifications want all actions
    }

    /**
     * Called when there is a new event
     * @param EventInterface $event
     * @return booo
     */
    public function consume(EventInterface $event): bool
    {
        if (!$event instanceof ActionEvent) {
            return false;
        }

        $this->blockListSync->sync($event->getUser());

        return true; // Return true to awknowledge the event from the stream (stop it being redelivered)
    }
}
