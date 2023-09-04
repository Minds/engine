<?php
namespace Minds\Core\ActivityPub\Services;

use Minds\Common\Access;
use Minds\Core\ActivityPub\Helpers\ContentParserBuilder;
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
use Minds\Core\Log\Logger;
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
        protected ProcessObjectService $processObjectService,
        protected EmitActivityService $emitActivityService,
        protected ACL $acl,
        protected ActivityManager $activityManager,
        protected Subscriptions\Manager $subscriptionsManager,
        private readonly VotesManager $votesManager,
        protected ProcessExternalImageService $processExternalImageService,
        protected Config $config,
        protected Logger $logger,
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
        $logPrefix = "{$this->activity->id}: ";

        $className = get_class($this->activity);

        /** @var User $owner */
        $owner = $this->manager->getEntityFromUri($this->activity->actor->id);
        if (!$owner) {
            // Actor not found, we will try and pull in their profile

            $this->logger->info("$logPrefix Actor ({$this->activity->actor->id}) not found, fetching them");

            try {
                $owner = $this->processActorService
                    ->withActorUri($this->activity->actor->id)
                    ->process();

                if (!$owner) {
                    // The owner could not be found. The owner must be present
                    return;
                }
            } catch (\Exception $e) {
                $this->logger->error("$logPrefix Error fetching actor {$e->getMessage()}");
                return;
            }
        }
        
        switch ($className) {
            case CreateType::class:
                $this->processObjectService
                    ->withObject($this->activity->object)
                    ->process();
                break;
            case AnnounceType::class:
                // If activity has been previously imported, then
                $existingActivity = $this->manager->getEntityFromUri($this->activity->id);
                if ($existingActivity) {
                    $this->logger->info("$logPrefix The remind already exists");
                    // No need to import as we already have it
                    return;
                }
    
                $originalEntity = $this->manager->getEntityFromUri($this->activity->object->id);

                if (!$originalEntity) {
                    $this->logger->info("$logPrefix The reminded content could not be found on Minds");
                    throw new NotFoundException("The reminded content could not be found on Minds");
                }
    
                $remind = new RemindIntent();
                $remind->setGuid($originalEntity->getGuid());
                $remind->setOwnerGuid($owner->getGuid());
                $remind->setQuotedPost(false);

                $entity = $this->processObjectService->prepareActivity($owner);
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
                    $this->logger->info("$logPrefix The actor could not be found");
                    // The actor doesn't exist, so we wont continue
                    throw new ForbiddenException();
                }

                $subject = $this->manager->getEntityFromUri($this->activity->object->id);
                if (!$subject instanceof User) {
                    $this->logger->info("$logPrefix The user trying to be subscribed to ({$this->activity->object->id}) could not be found");
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
                    $this->logger->info("$logPrefix The actor could not be found");
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

}
