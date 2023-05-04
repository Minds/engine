<?php
/**
 * This subscription is only used for testing purposes.
 * Make a new one with a unique subscription id if you wish to use this topic
 * You can test by running `php cli.php EventStreams --subscription=Core\\Entities\\Ops\\TestEntitiesOpsEventStreamsSubscription`
 */
namespace Minds\Core\Comments;

use Minds\Core\Di\Di;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\Topics\TopicInterface;

use Minds\Core\Entities\Ops\EntitiesOpsTopic;
use Minds\Core\Entities\Ops\EntitiesOpsEvent;

class CommentOpsEventStreamsSubscription implements SubscriptionInterface
{
    /** @var Manager */
    protected $manager;

    public function __construct(
        Manager $manager = null,
    ) {
        $this->manager = $manager ?? Di::_()->get('Comments\Manager');
    }

    /**
     * @return string
     */
    public function getSubscriptionId(): string
    {
        return 'comment-ops';
    }

    /**
     * @return TopicInterface
     */
    public function getTopic(): TopicInterface
    {
        return new EntitiesOpsTopic();
    }

    /**
     * @return string
     */
    public function getTopicRegex(): string
    {
        return EntitiesOpsTopic::TOPIC_NAME;
    }

    /**
     * Called when there is a new event
     * @param EventInterface $event
     * @return bool
     */
    public function consume(EventInterface $event): bool
    {
        if (!$event instanceof EntitiesOpsEvent || !str_contains($event->getEntityUrn(), "urn:comment")) {
            return false;
        }

        /** @var Comment **/
        $comment = $this->manager->getByUrn($event->getEntityUrn());

        error_log(print_r($comment->getBody(), true));
       
        return true;
    }
}
