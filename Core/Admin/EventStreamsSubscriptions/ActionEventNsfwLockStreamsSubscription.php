<?php
/**
 * Subscribes to action events for admin nsfw_lock changes.
 * Will run a batch job on a user to unmark or mark their posts.
 */
namespace Minds\Core\Admin\EventStreamsSubscriptions;

use Minds\Core\Di\Di;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\Log\Logger;
use Minds\Core\Entities;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Events\Dispatcher;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Helpers\MagicAttributes;
use Minds\Interfaces\Flaggable;
use Minds\Entities\User;

/**
 * Subscribes to action event nsfw_lock changes.
 * Will batch change nsfw_lock for a user.
 */
class ActionEventNsfwLockStreamsSubscription implements SubscriptionInterface
{
    public function __construct(
        private ?Logger $logger = null,
        private ?Save $save = null,
        private ?EntitiesBuilder $entitiesBuilder = null
    ) {
        $this->logger = $logger ?? Di::_()->get('Logger');
        $this->save = $save ?? new Save();
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
    }

    /**
     * Returns subscription id.
     * @return string subscription id.
     */
    public function getSubscriptionId(): string
    {
        return 'action-nsfw-lock';
    }
    
    /**
     * Returns topic.
     * @return ActionEvent - topic.
     */
    public function getTopic(): ActionEventsTopic
    {
        return new ActionEventsTopic();
    }

    /**
     * Returns topic regex, scoping subscription to nsfw_lock suffixed events.
     * @return string topic regex.
     */
    public function getTopicRegex(): string
    {
        return 'nsfw_lock';
    }

    /**
     * Called on event receipt.
     * @param EventInterface $event
     * @return bool
     */
    public function consume(EventInterface $event): bool
    {
        if (!$event instanceof ActionEvent) {
            return false;
        }

        $subject = $event->getEntity();
        $actor = $event->getUser();

        // batch job should only be ran for users.
        if (!$subject instanceof User) {
            $this->logger->error(
                'Tried to batch change nsfw_lock for a user but passed entity: '.
                $subject->getUrn()
            );
            return false;
        }

        if (!$actor->isAdmin()) {
            $this->logger->error('Unauthorized access to nsfw_lock event stream subscription by: ' . $actor->getGuid());
            return false;
        }

        // get data.
        $userGuid = (string) $subject->getGuid();
        $value = (array) $event->getActionData()['nsfw_lock'];

        // output receipt message.
        $valueString = json_encode($value);
        echo "Received a request to set nsfw_lock for all of the posts from user: $userGuid, to $valueString\n";

        // get entities of different types.
        $images = $this->getEntitiesByType('image', $userGuid);
        $videos = $this->getEntitiesByType('video', $userGuid);
        $blogs = $this->getEntitiesByType('blog', $userGuid);
        $activities = $this->getEntitiesByType('activity', $userGuid);

        $entities = [
            ...$images,
            ...$videos,
            ...$blogs,
            ...$activities
        ];

        $this->applyNsfwLockToEntities($entities, $value);

        echo "Finished updating nsfw_lock status of entities for user: $userGuid\n";

        return true;
    }

    /**
     * Get entities by type for a given user guid.
     * @param string $type - entity type.
     * @param string $userGuid - user guid.
     * @return array array of entities.
     */
    private function getEntitiesByType(string $type, string $userGuid): array
    {
        // set owner guid in options to users guid.
        $options = [
            'owner_guid' => $userGuid
        ];

        // if its an image, video or blog, set the appropriate type and subtype.
        if ($type == 'image' || $type == 'video' || $type == 'blog') {
            $options['subtype'] = $type;
            $type = 'object';
        }

        // get entities from database using options.
        // hard capping at 500 entities - which should cover posts made in the time a user appeals a decision.
        $entities = Entities::get(array_merge([
            'type' => $type,
            'limit' => 500,
            'offset' => '',
        ], $options));

        if (!$entities) {
            return [];
        }

        return array_filter($entities, function ($entity) {
            return !!$entity;
        });
    }

    /**
     * Apply NSFW lock to a batch of entities.
     * @param array $entities - array of entities.
     * @param array $value - array of nsfw values, such as [1, 2, 3].
     * @return void
     */
    private function applyNsfwLockToEntities(array $entities, array $value): void
    {
        foreach ($entities as $entity) {
            try {
                $this->setNsfwLock($entity, $value);
                $entityNsfwLockString = json_encode($entity->getNsfwLock());
                echo "Set nsfw_lock for post: {$entity->getGuid()}, by user: {$entity->getOwnerGuid()}, to {$entityNsfwLockString}\n";
            } catch (\Exception $e) {
                $this->logger->error($e);
                echo "Skipped {$entity->getGuid()} because of the above exception\n";
            }
        }
    }

    /**
     * Sets nsfw_lock of an entity.
     * @param Entities\Entity $entity - the entity to set / unset the locks on.
     * @param array $value - array to be set as nsfw_lock (e.g. [1, 2, 3]).
     * @return void
     */
    private function setNsfwLock($entity, array $value): void
    {
        // apply nsfw_lock to entity.
        if (MagicAttributes::setterExists($entity, 'setNsfwLock')) {
            $entity->setNsfwLock($value);
        } elseif (property_exists($entity, 'nsfw_lock')) {
            $entity->nsfw_lock = $value;
        }
        
        // apply nsfw_lock to custom data.
        if (property_exists($entity, 'custom_data')) {
            $entity->custom_data['nsfw_lock'] = $entity->getNsfwLock();
            $entity->custom_data[0]['nsfw_lock'] = $entity->getNsfwLock();
        }

        // apply to any attachments.
        if ($entity->entity_guid) {
            $attachment = $this->entitiesBuilder->single($entity->entity_guid);

            if ($attachment && $attachment->guid && $attachment instanceof Flaggable) {
                if (method_exists($attachment, 'setNsfwLock')) {
                    $attachment->setNsfwLock($value);
                } elseif (isset($attachment->nsfw_lock)) {
                    $attachment->nsfw_lock = $value;
                }
                $attachment->save();
            }
        }

        $saved = $this->save->setEntity($entity)
            ->save();

        if (!$saved) {
            $failedSaveMessage = "nsfw_lock save failed for post {$entity->getGuid()}, by user {$entity->getOwnerGuid()}";
            $this->logger->error($failedSaveMessage);
            echo $failedSaveMessage;
            return;
        }

        // dispatch to be reindexed.
        Dispatcher::trigger('search:index', 'all', [
            'entity' => $entity
        ]);
    }
}
