<?php
namespace Minds\Core\ActivityPub\Services;

use Minds\Common\Access;
use Minds\Core\ActivityPub\Manager;
use Minds\Core\ActivityPub\Types\Activity\AcceptType;
use Minds\Core\ActivityPub\Types\Activity\AnnounceType;
use Minds\Core\ActivityPub\Types\Activity\CreateType;
use Minds\Core\ActivityPub\Types\Activity\FollowType;
use Minds\Core\ActivityPub\Types\Activity\LikeType;
use Minds\Core\ActivityPub\Types\Activity\UndoType;
use Minds\Core\ActivityPub\Types\Core\ActivityType;
use Minds\Core\ActivityPub\Types\Object\DocumentType;
use Minds\Core\ActivityPub\Types\Object\NoteType;
use Minds\Core\Comments\Comment;
use Minds\Core\Config\Config;
use Minds\Core\Feeds\Activity\Manager as ActivityManager;
use Minds\Core\Feeds\Activity\RemindIntent;
use Minds\Core\Guid;
use Minds\Core\Media\Image\ProcessExternalImageService;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Security\ACL;
use Minds\Core\Subscriptions;
use Minds\Core\Votes\Manager as VotesManager;
use Minds\Core\Votes\Vote;
use Minds\Entities\Activity;
use Minds\Entities\EntityInterface;
use Minds\Entities\Enums\FederatedEntitySourcesEnum;
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
        private readonly VotesManager $votesManager,
        protected ProcessExternalImageService $processExternalImageService,
        protected Config $config,
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

        /** @var User $owner */
        $owner = $this->manager->getEntityFromUri($this->activity->actor->id);
        if (!$owner) {
            // The owner could not be found. The owner must be present
            return;
        }

        /**
         *  The owner and have at least one subscriber for their posts to be ingested
         */
        if ($this->subscriptionsManager->setSubscriber($owner)->getSubscriptionsCount() === 0) {
            // return;
        }
                    
        
        switch ($className) {
            case CreateType::class:
                /**
                 * Process the Note as a Minds Activity
                 */
                if ($this->activity->object instanceof NoteType) {
                    // If activity has been previously imported, then
                    $existingActivity = $this->manager->getEntityFromUri($this->activity->object->id);
                    if ($existingActivity) {
                        // No need to import as we already have it
                        return;
                    }

                    // Does the post have any attachments?

                    // Is this a reply?
                    // Do we have the post that is being replied to?
                    if (isset($this->activity->object->inReplyTo)) {
                        $inReplyToEntity = $this->manager->getEntityFromUri($this->activity->object->inReplyTo);
                        if (!$inReplyToEntity) {
                            // Should we fetch a new one?
                            // For now we will not
                            return;
                        }

                        // Ignore ACL as we need to be able to act on another users behalf
                        $ia = $this->acl->setIgnore(true);

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
                        $comment->setSource(FederatedEntitySourcesEnum::ACTIVITY_PUB);

                        if (isset($this->activity->object->url)) {
                            $comment->setCanonicalUrl($this->activity->object->url);
                        }

                        /**
                         * If any images, then fetch them
                         */
                        $images = $this->processImages(
                            owner: $owner,
                            max: 1
                        );
                        
                        if (count($images)) {
                            $siteUrl = $this->config->get('site_url');
                            $comment->setAttachment('custom_type', 'image');
                            $comment->setAttachment('custom_data', [
                                'guid' => (string) $images[0]->guid,
                                'container_guid' => (string) $images[0]->container_guid,
                                'src'=> $siteUrl . 'fs/v1/thumbnail/' . $images[0]->guid,
                                'href'=> $siteUrl . 'media/' . $images[0]->container_guid . '/' . $images[0]->guid,
                                'mature' => false,
                                'width' => $images[0]->width,
                                'height' => $images[0]->height,
                            ]);
                            $comment->setAttachment('attachment_guid', $images[0]->guid);

                            // Fix the access_id on the image
                            $this->patchImages($comment, $images);
                        }

                        $commentsManager = new \Minds\Core\Comments\Manager();
                        $commentsManager->add($comment);
                        
                        // Save the comment
                        $this->manager->addUri(
                            uri: $this->activity->object->id,
                            guid: (int) $comment->getGuid(),
                            urn: $comment->getUrn(),
                        );

                        // Reset ACL state
                        $this->acl->setIgnore($ia);

                        return;
                    }

                    // Ignore ACL as we need to be able to act on another users behalf
                    $ia = $this->acl->setIgnore(true);

                    /**
                     * Create the Activity
                     */
                    $entity = $this->prepareActivity($owner);

                    if (isset($this->activity->object->url)) {
                        $entity->setCanonicalUrl($this->activity->object->url);
                    } else {
                        $entity->setCanonicalUrl($this->activity->object->id);
                    }

                    $entity->setMessage(strip_tags($this->activity->object->content));

                    // If any images, then fetch them
                    $images = $this->processImages(
                        owner: $owner,
                        max: 4
                    );

                    // Add the images as attachments
                    $entity->setAttachments($images);
                
                    // Save the activity
                    $this->activityManager->add($entity);

                    // Patch image access
                    if (count($images)) {
                        $this->patchImages($entity, $images);
                    }

                    // Reset ACL state
                    $this->acl->setIgnore($ia);
        
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

                if (!$originalEntity) {
                    throw new NotFoundException("The reminded content could not be found one Minds");
                }
    
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
            case LikeType::class:
                $actor = $this->manager->getEntityFromUri($this->activity->actor->id);
                if (!$actor) {
                    // The actor doesn't exist, so we wont continue
                    throw new ForbiddenException();
                }

                $entity = $this->manager->getEntityFromUri($this->activity->object->id);
                
                $vote = (new Vote())
                    ->setEntity($entity)
                    ->setActor($actor)
                    ->setDirection('up');

                if (
                    $this->votesManager->setUser($actor)
                        ->has($vote)) {
                    // Already voted
                    return;
                }

                $this->votesManager->cast($vote);
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
                    case LikeType::class:
                        /** @var LikeType $likeType */
                        $likeType = $this->activity->object;
                        $actor = $this->manager->getEntityFromUri($this->activity->actor->id);
                        if (!$actor) {
                            // The actor doesn't exist, so we wont continue
                            throw new ForbiddenException();
                        }

                        $subject = $this->manager->getEntityFromUri($likeType->object->id);

                        $vote = (new Vote())
                            ->setEntity($subject)
                            ->setActor($actor)
                            ->setDirection('up');
                        if (!$this->votesManager->has($vote)) {
                            // Vote already removed
                            return;
                        }

                        $this->votesManager->cancel($vote);
                        break;
                }
        }
        
    }

    /**
     * @return Image[]
     */
    private function processImages(User $owner, int $max = 4): array
    {
        $images = [];

        if (isset($this->activity->object->attachment) && count($this->activity->object->attachment)) {
            foreach ($this->activity->object->attachment as $attachment) {
                if (count($images) >= $max) {
                    break;
                }
                if (!$attachment instanceof DocumentType) {
                    continue;
                }
                if (strpos($attachment->mediaType, 'image/', 0) === false) {
                    continue; // Not a valid image
                }
                $images[] = $this->processExternalImageService->process($owner, $attachment->url);
            }
        }

        return $images;
    }

    /**
     * When we create the images, we are not aware of the GUID
     * After the Activity is saved, and we have a GUID, we can then patch the Images
     * with the correct access_id and container_guid
     */
    private function patchImages(EntityInterface $entity, array $images): void
    {
        foreach ($images as $image) {
            if ($entity instanceof Activity) {
                $image->setAccessId($entity->getGuid());
                $image->setContainerGUID($entity->getGuid());
            } elseif ($entity instanceof Comment) {
                $image->setAccessId($entity->getAccessId());
                $image->setContainerGUID($entity->getAccessId());
            } else {
                return;
            }

            // Save the image with our new parent
            $image->save();
        }
    }

    /**
     * Helper function to build an Activity entity with the correct attributes
     */
    private function prepareActivity(User $owner): Activity
    {
        $entity = new Activity();

        $entity->setAccessId(Access::PUBLIC);
        $entity->setSource(FederatedEntitySourcesEnum::ACTIVITY_PUB);
    
        // Requires cleanup (see TwitterSync and Nostr)
        $entity->container_guid = $owner->guid;
        $entity->owner_guid = $owner->guid;
        $entity->ownerObj = $owner->export();

        return $entity;
    }
}
