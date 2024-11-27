<?php
/**
 * This subscription will listen for changes in entities (create, update, delete) and update elasticsearch
 * You can test by running `php cli.php EventStreams --subscription=Core\\Search\\SearchIndexerSubscription`
 */
namespace Minds\Core\Search;

use Minds\Common\Urn;
use Minds\Core\Blogs\Blog;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Ops\EntitiesOpsEvent;
use Minds\Core\Entities\Ops\EntitiesOpsTopic;
use Minds\Core\Entities\Resolver;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\Topics\TopicInterface;
use Minds\Entities\Activity;
use Minds\Entities\Group;
use Minds\Entities\Image;
use Minds\Entities\User;
use Minds\Entities\Video;

class SearchIndexerSubscription implements SubscriptionInterface
{
    public function __construct(
        protected ?Index $index = null,
        protected ?Resolver $entitiesResolver = null
    ) {
        $this->index ??= Di::_()->get(Index::class);
        $this->entitiesResolver ??= new Resolver();
    }

    /**
     * @return string
     */
    public function getSubscriptionId(): string
    {
        return 'search-indexer';
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
        if (!$event instanceof EntitiesOpsEvent) {
            return false;
        }

        // We may have a serialized entity (eg. if we no longer have the deleted record)
        if ($serializedEntity = $event->getEntitySerialized()) {
            $entity = unserialize($serializedEntity);
        } else {
            $entity = $this->entitiesResolver->setOpts([
                'cache' => false
            ])->single(new Urn($event->getEntityUrn()));
        }

        if (!$entity) {
            if ($event->getTimestamp() < time() - 300) {
                return false; // Neg ack. Retry, may be replication lag.
            }
            // Entity not found
            return true; // Awknowledge as its likely this entity has been deleted
        }

        // We are only concerned to index the following

        switch (get_class($entity)) {
            case Activity::class:
            case User::class:
            case Group::class:
            case Image::class:
            case Video::class:
            case Blog::class:
                break;
            default:
                return true; // Will not index anything else
        }

        switch ($event->getOp()) {
            case EntitiesOpsEvent::OP_CREATE:
            case EntitiesOpsEvent::OP_UPDATE:
                return $this->index->index($entity);
                break;
            case EntitiesOpsEvent::OP_DELETE:
                return $this->index->remove($entity);
                break;
        }
       
        return true; // Return true to awknowledge the event from the stream (stop it being redelivered)
    }
}
