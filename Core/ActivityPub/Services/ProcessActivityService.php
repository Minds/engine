<?php
namespace Minds\Core\ActivityPub\Services;

use Minds\Common\Access;
use Minds\Core\ActivityPub\Factories\ActorFactory;
use Minds\Core\ActivityPub\Helpers\JsonLdHelper;
use Minds\Core\ActivityPub\Manager;
use Minds\Core\ActivityPub\Types\Activity\AcceptType;
use Minds\Core\ActivityPub\Types\Activity\AnnounceType;
use Minds\Core\ActivityPub\Types\Activity\CreateType;
use Minds\Core\ActivityPub\Types\Activity\FollowType;
use Minds\Core\ActivityPub\Types\Activity\UndoType;
use Minds\Core\ActivityPub\Types\Core\ActivityType;
use Minds\Core\ActivityPub\Types\Object\NoteType;
use Minds\Core\Comments\Comment;
use Minds\Core\Feeds\Activity\Manager as ActivityManager;
use Minds\Core\Feeds\Activity\RemindIntent;
use Minds\Core\Guid;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Security\ACL;
use Minds\Core\Subscriptions;
use Minds\Entities\Activity;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;

class ProcessActivityService
{
    protected ActivityType $activity;

    public function __construct(
        protected Manager $manager,
        protected ProcessActorService $processActorService,
        protected EmitActivityService $emitActivityService,
        protected ACL $acl,
        protected ActivityManager $activityManager,
        protected Subscriptions\Manager $subscriptionsManager,
    ) {
        
    }

    public function withActivity(ActivityType $activityType): ProcessActivityService
    {
        $instance = clone $this;
        $instance->activity = $activityType;
        return $instance;
    }

    public function process(): void
    {
        $className = get_class($this->activity);


        /** @var User */
        $owner = $this->manager->getEntityFromUri($this->activity->actor->id);
        if (!$owner) {
            // The owner could not be found. The owner must be present
            return;
        }

        /**
         *  The owner and have at least one subscriber for their posts to be ingested
         */
        if ($this->subscriptionsManager->setSubscriber($owner)->getSubscriptionsCount() === 0) {
            //return;
        }
                    
        
        switch ($className) {
            case CreateType::class:
                /**
                 * Process the a Note as a Minds Activity
                 */
                if ($this->activity->object instanceof NoteType) {
                    // If activity has been previously imported, then
                    $existingActivity = $this->manager->getEntityFromUri($this->activity->object->id);
                    if ($existingActivity) {
                        // No need to import as we already have it
                        return;
                    }

                    // Is this a reply?
                    // Do we have the post that is being replied to?
                    if (isset($this->activity->object->inReplyTo)) {
                        $inReplyToEntity = $this->manager->getEntityFromUri($this->activity->object->inReplyTo);
                        if (!$inReplyToEntity) {
                            // Should we fetch a new one?
                            // For now we will not
                            return;
                        }

                        // We will always treat Fediverse replies as comments

                        $comment = new Comment();
                        
                        if ($inReplyToEntity instanceof Comment) {
                            $comment->setEntityGuid($inReplyToEntity->getEntityGuid());
                            $comment->setParentGuidL1(0);
                            $comment->setParentGuidL2(0);

                            $parentGuids = explode(':', $inReplyToEntity->getChildPath());
                            $comment->setParentGuidL1($parentGuids[0]);
                            $comment->setParentGuidL2($parentGuids[1]);
                        } else {
                            $comment->setEntityGuid($inReplyToEntity->getGuid());
                            $comment->setParentGuidL1(0);
                            $comment->setParentGuidL2(0);
                        }

                        $comment->setBody(strip_tags($this->activity->object->content));
                        $comment->setOwnerGuid($owner->getGuid());
                        $comment->setTimeCreated(time());

                        $commentsManager = new \Minds\Core\Comments\Manager();
                        $commentsManager->add($comment);
                        
                        $this->manager->addUri(
                            uri: $this->activity->object->id,
                            guid: (int) $comment->getGuid(),
                            urn: $comment->getUrn(),
                        );

                        return;
                    }

                    $entity = $this->prepareActivity($owner);

                    $entity->setMessage(strip_tags($this->activity->object->content));
                                    
                    $ia = $this->acl->setIgnore(true); // Ignore ACL as we need to be able to act on another users behalf
                    $this->activityManager->add($entity);
                    $this->acl->setIgnore($ia); // Reset ACL state
        
                    // Save reference so we don't import this again
                    $this->manager->addUri(
                        uri: $this->activity->object->id,
                        guid: (int) $entity->getGuid(),
                        urn: $entity->getUrn(),
                    );
                }

                break;
            case AnnounceType::class:
                // If activity has been previously imported, then
                $existingActivity = $this->manager->getEntityFromUri($this->activity->id);
                if ($existingActivity) {
                    // No need to import as we already have it
                    return;
                }
    
                $originalEntity = $this->manager->getEntityFromUri($this->activity->object->id);
    
                $remind = new RemindIntent();
                $remind->setGuid($originalEntity->getGuid());
                $remind->setOwnerGuid($owner->getGuid());
                $remind->setQuotedPost(false);

                $entity = $this->prepareActivity($owner);
                $entity->setRemind($remind);
                
                $ia = $this->acl->setIgnore(true); // Ignore ACL as we need to be able to act on another users behalf
                $this->activityManager->add($entity);
                $this->acl->setIgnore($ia); // Reset ACL state

                // Save reference so we don't import this again
                $this->manager->addUri(
                    uri: $this->activity->id,
                    guid: (int) $entity->getGuid(),
                    urn: $entity->getUrn(),
                );

                break;
            case FollowType::class:
                $actor = $this->manager->getEntityFromUri($this->activity->actor->id);
                if (!$actor) {
                    // The actor doesn't exist, so we wont continue
                    throw new ForbiddenException();
                }

                $subject = $this->manager->getEntityFromUri($this->activity->object->id);
                if (!$subject instanceof User) {
                    // We couldn't find the user that is trying to be subscribed to
                    throw new NotFoundException();
                }

                $this->subscriptionsManager->setSubscriber($actor);
                
                /**
                 * If not already subscribed, subscribe
                 */
                if (!$this->subscriptionsManager->isSubscribed($subject)) {
                    $this->subscriptionsManager->subscribe($subject);
                }

                // Emit out Accept activity
                $accept = new AcceptType();
                $accept->id = $this->manager->getBaseUrl() . Guid::build(); // transient id
                $accept->actor = $this->activity->object;
                $accept->object = $this->activity;

                $this->emitActivityService->emitAccept($accept, $subject);

                break;
            case AcceptType::class:
                // Nothing to do here?
                break;
            case UndoType::class:

                switch (get_class($this->activity->object)) {
                    case FollowType::class:
                        /** @var FollowType */
                        $object = $this->activity->object;
    
                        /**
                         * Unfollow
                         */
                        $actor = $this->manager->getEntityFromUri($this->activity->actor->id);
                        if (!$actor) {
                            // The actor doesn't exist, so we wont continue
                            throw new ForbiddenException();
                        }

                        $subject = $this->manager->getEntityFromUri($object->object->id);
                        if (!$subject instanceof User) {
                            // We couldn't find the user that is trying to be subscribed to
                            throw new NotFoundException();
                        }

                        $this->subscriptionsManager->setSubscriber($actor)->unSubscribe($subject);
                        break;
                }
        }
        
    }

    /**
     * Helper function to build an Activity entity with the correct attributes
     */
    private function prepareActivity(User $owner): Activity
    {
        $entity = new Activity();

        $entity->setAccessId(Access::PUBLIC);
        $entity->setSource('activitypub');
    
        // Requires cleanup (see TwitterSync and Nostr)
        $entity->container_guid = $owner->guid;
        $entity->owner_guid = $owner->guid;
        $entity->ownerObj = $owner->export();

        return $entity;
    }
}
