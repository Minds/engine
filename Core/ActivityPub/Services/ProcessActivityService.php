<?php
namespace Minds\Core\ActivityPub\Services;

use Minds\Common\Access;
use Minds\Core\ActivityPub\Manager;
use Minds\Core\ActivityPub\Types\Activity\CreateType;
use Minds\Core\ActivityPub\Types\Activity\FollowType;
use Minds\Core\ActivityPub\Types\Activity\UndoType;
use Minds\Core\ActivityPub\Types\Core\ActivityType;
use Minds\Core\ActivityPub\Types\Object\NoteType;
use Minds\Core\Feeds\Activity\Manager as ActivityManager;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Security\ACL;
use Minds\Entities\Activity;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;

class ProcessActivityService
{
    protected ActivityType $activity;

    public function __construct(
        protected Manager $manager,
        protected ProcessActorService $processActorService,
        protected ACL $acl,
        protected ActivityManager $activityManager,
    )
    {
        
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

                    /** @var User */
                    $owner = $this->manager->getEntityFromUri($this->activity->actor->id);
                    if (!$owner) {
                        // The owner could not be found. Try to import a new one.

                        $this->processActorService
                            ->withActor($this->activity->actor)
                            ->process();

                        // Try to get the owner entity again
                        /** @var User */
                        $owner = $this->manager->getEntityFromUri($this->activity->actor->id);
                        if (!$owner) {
                            // Still nothing then skip
                            return;
                        }
                    }

                    $entity = new Activity();

                    $entity->setMessage($this->activity->object->content);
                    $entity->setAccessId(Access::PUBLIC);
                    $entity->setSource('activitypub');
                
                    // Requires cleanup (see TwitterSync and Nostr)
                    $entity->container_guid = $owner->guid;
                    $entity->owner_guid = $owner->guid;
                    $entity->ownerObj = $owner->export();
                
                    $ia = $this->acl->setIgnore(true); // Ignore ACL as we need to be able to act on another users behalf
                    $this->activityManager->add($entity);
                    $this->acl->setIgnore($ia); // Reset ACL state
        
                    // Save reference so we don't import this again
                    $this->manager->addUri($this->activity->id, (int) $entity->getGuid());
                }

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

                $subscriptionsManager = new \Minds\Core\Subscriptions\Manager();
                $subscriptionsManager->setSubscriber($actor)->subscribe($subject);

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

                        $subscriptionsManager = new \Minds\Core\Subscriptions\Manager();
                        $subscriptionsManager->setSubscriber($actor)->unSubscribe($subject);
                        break;
                }
        }
        
    }

}