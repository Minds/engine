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

    /** @var RelationalRepository */
    private $repository;

    public function __construct(
        Manager $manager = null,
        RelationalRepository $repository = null
    ) {
        $this->manager = $manager ?? Di::_()->get('Comments\Manager');
        $this->repository ??= new RelationalRepository();
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
        if (
            !$event instanceof EntitiesOpsEvent || // If not an an entity op event
            !str_contains($event->getEntityUrn(), "urn:comment") // Or not a comment
        ) {
            return false;
        }

        /** @var Comment **/
        $comment = $this->manager->getByUrn($event->getEntityUrn());

        if (!$comment) {
            return false;
        }

        $this->repository->add($comment); // Add comment to relational database
        error_log("Done");
        return true; // Acknowledge the event
    }
}
