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
use Minds\Core\EntitiesBuilder;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\Topics\TopicInterface;
use Minds\Core\Feeds\User\Manager as FeedsUserManager;
use Minds\Core\Log\Logger;
use Minds\Entities\Activity;
use Minds\Entities\Group;
use Minds\Entities\Image;
use Minds\Entities\User;
use Minds\Entities\Video;
use Minds\Exceptions\ServerErrorException;

class SearchIndexerSubscription implements SubscriptionInterface
{
    public function __construct(
        protected ?Index $index = null,
        protected ?Resolver $entitiesResolver = null,
        protected ?EntitiesBuilder $entitiesBuilder = null,
        protected ?FeedsUserManager $feedUserManager = null,
        protected ?Logger $logger = null
    ) {
        $this->index ??= Di::_()->get(Index::class);
        $this->entitiesResolver ??= new Resolver();
        $this->entitiesBuilder ??= new EntitiesBuilder();
        $this->feedUserManager ??= Di::_()->get('Feeds\User\Manager');
        $this->logger ??= Di::_()->get('Logger');
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
            // Entity not found
            return true; // Acknowledge as its likely this entity has been deleted
        }

        // We are only concerned to index the following
        switch (get_class($entity)) {
            case Activity::class:
                $this->patchActivity($entity, $event->getOp());
                break;
            case Image::class:
            case Blog::class:
            case Video::class:
            case User::class:
            case Group::class:
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
       
        return true; // Return true to acknowledge the event from the stream (stop it being redelivered)
    }

    /**
     * Applies patches to activity.
     * @param Activity $activity - activity to patch.
     * @param string $opsEventType - entity operation string e.g. `EntitiesOpsEvent::OP_CREATE`.
     * @return void
     */
    private function patchActivity(Activity &$activity, string $opsEventType): void
    {
        try {
            if (
                $opsEventType === EntitiesOpsEvent::OP_CREATE &&
                !$this->hasMadeActivityPosts($activity->getOwnerGuid())
            ) {
                $tags = $activity->getTags() ?? [];
                $tags[] = 'hellominds';
                $activity->setTags($tags);
            }
        } catch (\Exception $e) {
            $this->logger->error($e);
        }
    }

    /**
     * Whether user has made a single activity post.
     * @param string $ownerGuid - guid of the owner.
     * @throws ServerErrorException - if no user is found.
     * @return bool - true if user has made a single activity post.
     */
    private function hasMadeActivityPosts(string $ownerGuid): bool
    {
        if ($this->feedUserManager->getHasMadePostsFromCache($ownerGuid)) {
            return true;
        }

        $owner = $this->entitiesBuilder->single($ownerGuid);
        if (!$owner || !($owner instanceof User)) {
            throw new ServerErrorException("No user found for owner guid: $ownerGuid");
        }

        try {
            $hasMadePosts = $this->feedUserManager->setUser($owner)
                ->hasMadePosts();

            $this->feedUserManager->setHasMadePostsInCache($ownerGuid);

            return $hasMadePosts;
        } catch (\Exception $e) {
            // presume true so we don't wrongly index a users post who already has other posts.
            return true;
        };
    }
}
