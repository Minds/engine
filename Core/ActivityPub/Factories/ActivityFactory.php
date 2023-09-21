<?php
namespace Minds\Core\ActivityPub\Factories;

use Minds\Core\ActivityPub\Enums\ActivityFactoryOpEnum;
use Minds\Core\ActivityPub\Exceptions\NotImplementedException;
use Minds\Core\ActivityPub\Helpers\JsonLdHelper;
use Minds\Core\ActivityPub\Manager;
use Minds\Core\ActivityPub\Types\Activity\AcceptType;
use Minds\Core\ActivityPub\Types\Activity\AnnounceType;
use Minds\Core\ActivityPub\Types\Activity\CreateType;
use Minds\Core\ActivityPub\Types\Activity\DeleteType;
use Minds\Core\ActivityPub\Types\Activity\FlagType;
use Minds\Core\ActivityPub\Types\Activity\FollowType;
use Minds\Core\ActivityPub\Types\Activity\LikeType;
use Minds\Core\ActivityPub\Types\Activity\UndoType;
use Minds\Core\ActivityPub\Types\Activity\UpdateType;
use Minds\Core\ActivityPub\Types\Actor\AbstractActorType;
use Minds\Core\ActivityPub\Types\Core\ActivityType;
use Minds\Entities\Activity;
use Minds\Entities\EntityInterface;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;

class ActivityFactory
{
    public function __construct(
        protected Manager $manager,
        protected ActorFactory $actorFactory,
        protected ObjectFactory $objectFactory,
    ) {
        
    }

    public function fromEntity(
        ActivityFactoryOpEnum $op,
        EntityInterface $entity,
        User $actor
    ): ActivityType {
        $isRemind = $entity instanceof Activity && $entity->isRemind();

        $item = $isRemind ? new AnnounceType() : match($op) {
            ActivityFactoryOpEnum::CREATE => new CreateType(),
            ActivityFactoryOpEnum::UPDATE => new UpdateType(),
            ActivityFactoryOpEnum::DELETE => new DeleteType(),
        };
        
        // If a remind, we want to get the original entity
        if ($isRemind && $entity instanceof Activity) {
            $remind = $entity->getRemind();
            if (!$remind) {
                throw new NotFoundException("The reminded content could not be found");
            }
            $object = $this->objectFactory->fromEntity($remind);

            $item->to = [
                'https://www.w3.org/ns/activitystreams#Public',
            ];
            $item->cc = [
                $object->attributedTo,
                $this->manager->getUriFromEntity($actor) . '/followers',
            ];
        } else {
            $object = $this->objectFactory->fromEntity($entity);
        }

        $item->id = $this->manager->getUriFromEntity($entity) . '/activity';

        // If a remind has been deleted, do an Undo
        if ($op === ActivityFactoryOpEnum::DELETE && $isRemind) {
            $item = new UndoType();
            $item->id = $this->manager->getTransientId();
            $object = $this->fromEntity(ActivityFactoryOpEnum::CREATE, $entity, $actor);
        }

        $item->actor = $this->actorFactory->fromEntity($actor);
        $item->object = $object;

        return $item;
    }

    public function fromJson(array $json, AbstractActorType|string $actor): ActivityType
    {
        $activity = match ($json['type']) {
            'Create' => new CreateType(),
            'Follow' => new FollowType(),
            'Like' => new LikeType(),
            'Flag' => new FlagType(),
            'Undo' => new UndoType(),
            'Accept' => new AcceptType(),
            'Announce' => new AnnounceType(),
            'Delete' => new DeleteType(),
            default => throw new NotImplementedException(),
        };

        // Must
        $activity->id = $json['id'];
        $activity->actor = $actor;

        $activity->object = match (get_class($activity)) {
            FollowType::class => $this->actorFactory->fromUri(JsonLdHelper::getValueOrId($json['object'])),
            UndoType::class => $this->fromJson($json['object'], $actor),
            AcceptType::class => $this->fromJson($json['object'], $actor),
            LikeType::class => $this->objectFactory->fromUri(JsonLdHelper::getValueOrId($json['object'])),
            FlagType::class => is_array($json['object']) ? "" : $json['object'],
            DeleteType::class => JsonLdHelper::getValueOrId($json['object']),
            AnnounceType::class => $this->objectFactory->fromUri(JsonLdHelper::getValueOrId($json['object'])),
            default => $this->objectFactory->fromJson($json['object']),
        };

        if (is_array($json['object'])) {
            $activity->mastodonObject = $json['object'];
        }

        return $activity;
    }
}
