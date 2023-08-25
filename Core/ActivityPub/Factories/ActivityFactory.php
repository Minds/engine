<?php
namespace Minds\Core\ActivityPub\Factories;

use Minds\Core\ActivityPub\Helpers\JsonLdHelper;
use Minds\Core\ActivityPub\Types\Activity\AcceptType;
use Minds\Core\ActivityPub\Types\Activity\AnnounceType;
use Minds\Core\ActivityPub\Types\Activity\CreateType;
use Minds\Core\ActivityPub\Types\Activity\DeleteType;
use Minds\Core\ActivityPub\Types\Activity\FlagType;
use Minds\Core\ActivityPub\Types\Activity\FollowType;
use Minds\Core\ActivityPub\Types\Activity\LikeType;
use Minds\Core\ActivityPub\Types\Activity\UndoType;
use Minds\Core\ActivityPub\Types\Actor\AbstractActorType;
use Minds\Core\ActivityPub\Types\Core\ActivityType;
use NotImplementedException;

class ActivityFactory
{
    public function __construct(
        protected ActorFactory $actorFactory,
        protected ObjectFactory $objectFactory,
    ) {
        
    }

    public function fromJson(array $json, AbstractActorType $actor): ActivityType
    {
        $activity = match ($json['type']) {
            'Create' => new CreateType(),
            'Follow' => new FollowType(),
            'Like' => new LikeType(),
            'Flag' => new FlagType(),
            'Undo' => new UndoType(),
            'Accept' => new AcceptType(),
            'Announce' => new AnnounceType(),
            // 'Delete' => new DeleteType(),
            default => throw new NotImplementedException(),
        };

        // Must
        $activity->id = $json['id'];
        // if ($activity->getType() !== "Flag") {
            $activity->actor = $actor;
        // }

        $activity->object = match (get_class($activity)) {
            FollowType::class => $this->actorFactory->fromUri(JsonLdHelper::getValueOrId($json['object'])),
            UndoType::class => $this->fromJson($json['object'], $actor),
            AcceptType::class => $this->fromJson($json['object'], $actor),
            LikeType::class => $this->objectFactory->fromUri(JsonLdHelper::getValueOrId($json['object'])),
            FlagType::class => $json['object'],
            DeleteType::class => null, // TODO
            AnnounceType::class => $this->objectFactory->fromUri(JsonLdHelper::getValueOrId($json['object'])),
            default => $this->objectFactory->fromJson($json['object']),
        };

        return $activity;
    }
}
